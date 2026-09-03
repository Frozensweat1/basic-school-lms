<?php

namespace App\Services\Sms;

use App\Jobs\DispatchSmsCampaignJob;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SmsCampaign;
use App\Models\SmsRecipient;
use App\Support\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SmsCampaignService
{
    public function __construct(
        private readonly SmsRecipientResolver $resolver,
        private readonly SmsMessageMetrics $metrics,
        private readonly AuditLogger $auditLogger,
    ) {}

    /** @return array{recipients: Collection, skipped: Collection, missing_count: int, duplicate_count: int} */
    public function preview(int $schoolId, string $mode, array $audiences, ?int $schoolClassId, ?string $singleRecipientKey): array
    {
        $this->validateSchoolAndClass($schoolId, $schoolClassId);

        if ($mode === 'single') {
            $candidate = $this->resolver->resolveSingle($schoolId, (string) $singleRecipientKey);

            return [
                'recipients' => collect($candidate && $candidate['sendable'] ? [$candidate] : []),
                'skipped' => collect($candidate && ! $candidate['sendable'] ? [[...$candidate, 'skip_reason' => 'No valid phone number']] : []),
                'missing_count' => $candidate && ! $candidate['sendable'] ? 1 : 0,
                'duplicate_count' => 0,
            ];
        }

        if ($mode !== 'bulk') {
            throw ValidationException::withMessages(['mode' => 'Choose single or bulk SMS mode.']);
        }

        $audiences = $this->normalizeAudiences($audiences);
        if ($audiences === []) {
            throw ValidationException::withMessages(['audiences' => 'Choose at least one recipient group.']);
        }

        return $this->resolver->resolveBulk($schoolId, $audiences, $schoolClassId);
    }

    public function queueCampaign(int $schoolId, int $createdBy, string $mode, array $audiences, ?int $schoolClassId, ?string $singleRecipientKey, string $message): SmsCampaign
    {
        $message = $this->metrics->clean($message);
        $analysis = $this->metrics->analyse($message);
        if ($message === '') {
            throw ValidationException::withMessages(['message' => 'Enter an SMS message.']);
        }
        if ($analysis['segment_count'] > (int) config('sms.max_segments', 3)) {
            throw ValidationException::withMessages(['message' => 'Keep the message within '.config('sms.max_segments', 3).' SMS segments.']);
        }

        $resolution = $this->preview($schoolId, $mode, $audiences, $schoolClassId, $singleRecipientKey);
        if ($resolution['recipients']->isEmpty()) {
            $field = $mode === 'single' ? 'singleRecipientKey' : 'audiences';
            throw ValidationException::withMessages([$field => $mode === 'single' ? 'The selected recipient does not have a deliverable mobile number.' : 'No deliverable mobile numbers match the selected audience.']);
        }
        $maxRecipients = (int) config('sms.max_recipients', 5000);
        if ($resolution['recipients']->count() > $maxRecipients) {
            throw ValidationException::withMessages(['audiences' => 'This audience exceeds the '.number_format($maxRecipients).'-recipient safety limit. Narrow it with a class filter.']);
        }

        $normalizedAudiences = $mode === 'single'
            ? $resolution['recipients']->pluck('audience')->unique()->values()->all()
            : $this->normalizeAudiences($audiences);

        $campaign = DB::transaction(function () use ($schoolId, $createdBy, $schoolClassId, $mode, $normalizedAudiences, $singleRecipientKey, $message, $analysis, $resolution): SmsCampaign {
            $campaign = SmsCampaign::query()->create([
                'school_id' => $schoolId,
                'created_by' => $createdBy,
                'school_class_id' => $schoolClassId,
                'mode' => $mode,
                'audiences' => $normalizedAudiences,
                'filters' => array_filter(['school_class_id' => $schoolClassId, 'single_recipient_key' => $mode === 'single' ? $singleRecipientKey : null], fn (mixed $value): bool => $value !== null && $value !== ''),
                'message' => Str::limit($message, 65535, ''),
                'sender_id' => config('sms.sender_id'),
                'provider' => config('sms.default'),
                'encoding' => $analysis['encoding'],
                'character_count' => $analysis['character_count'],
                'segment_count' => $analysis['segment_count'],
                'status' => SmsCampaign::STATUS_QUEUED,
                'recipient_count' => $resolution['recipients']->count(),
                'skipped_count' => $resolution['skipped']->count(),
                'queued_at' => now(),
            ]);

            $now = now();
            $rows = $resolution['recipients']->map(fn (array $recipient): array => $this->recipientRow($campaign->id, $recipient, SmsRecipient::STATUS_QUEUED, null, $now))
                ->concat($resolution['skipped']->map(fn (array $recipient): array => $this->recipientRow($campaign->id, $recipient, SmsRecipient::STATUS_SKIPPED, $recipient['skip_reason'] ?? 'No valid phone number', $now)));
            $rows->chunk(250)->each(fn ($chunk) => SmsRecipient::query()->insert($chunk->all()));

            return $campaign;
        });

        DispatchSmsCampaignJob::dispatch($campaign->id)->onQueue('default')->afterCommit();
        $this->auditLogger->record('sms.campaign_queued', $campaign, newValues: ['mode' => $campaign->mode, 'audiences' => $campaign->audiences, 'recipient_count' => $campaign->recipient_count, 'skipped_count' => $campaign->skipped_count]);

        return $campaign->fresh(['creator', 'schoolClass']);
    }

    public function retryFailed(SmsCampaign $campaign): int
    {
        $count = DB::transaction(function () use ($campaign): int {
            $locked = SmsCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            $count = $locked->recipients()->where('status', SmsRecipient::STATUS_FAILED)->count();
            if ($count === 0) return 0;
            $locked->recipients()->where('status', SmsRecipient::STATUS_FAILED)->update(['status' => SmsRecipient::STATUS_QUEUED, 'last_error' => null, 'failed_at' => null, 'updated_at' => now()]);
            $locked->forceFill(['status' => SmsCampaign::STATUS_QUEUED, 'failed_count' => 0, 'completed_at' => null, 'queued_at' => now()])->save();
            return $count;
        });
        if ($count > 0) {
            DispatchSmsCampaignJob::dispatch($campaign->id)->onQueue('default')->afterCommit();
            $this->auditLogger->record('sms.campaign_retried', $campaign, newValues: ['retry_count' => $count]);
        }
        return $count;
    }

    public function markProcessing(int $campaignId): void
    {
        SmsCampaign::query()->whereKey($campaignId)->where('status', SmsCampaign::STATUS_QUEUED)->update(['status' => SmsCampaign::STATUS_PROCESSING, 'updated_at' => now()]);
    }

    public function refreshStatus(int $campaignId): void
    {
        DB::transaction(function () use ($campaignId): void {
            $campaign = SmsCampaign::query()->lockForUpdate()->find($campaignId);
            if (! $campaign) return;
            $counts = $campaign->recipients()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
            $sent = (int) ($counts[SmsRecipient::STATUS_SENT] ?? 0);
            $failed = (int) ($counts[SmsRecipient::STATUS_FAILED] ?? 0);
            $skipped = (int) ($counts[SmsRecipient::STATUS_SKIPPED] ?? 0);
            $queued = (int) ($counts[SmsRecipient::STATUS_QUEUED] ?? 0);
            $sending = (int) ($counts[SmsRecipient::STATUS_SENDING] ?? 0);
            $deliverable = $sent + $failed + $queued + $sending;
            $status = match (true) {
                $queued > 0 || $sending > 0 => SmsCampaign::STATUS_PROCESSING,
                $deliverable > 0 && $sent === $deliverable => SmsCampaign::STATUS_COMPLETED,
                $deliverable > 0 && $failed === $deliverable => SmsCampaign::STATUS_FAILED,
                $failed > 0 => SmsCampaign::STATUS_PARTIAL,
                default => SmsCampaign::STATUS_FAILED,
            };
            $campaign->forceFill(['status' => $status, 'recipient_count' => $deliverable, 'sent_count' => $sent, 'failed_count' => $failed, 'skipped_count' => $skipped, 'completed_at' => in_array($status, [SmsCampaign::STATUS_COMPLETED, SmsCampaign::STATUS_PARTIAL, SmsCampaign::STATUS_FAILED], true) ? now() : null])->save();
        });
    }

    private function normalizeAudiences(array $audiences): array
    {
        return collect(SmsRecipientResolver::AUDIENCES)->filter(fn (string $audience): bool => in_array($audience, $audiences, true))->values()->all();
    }

    private function validateSchoolAndClass(int $schoolId, ?int $schoolClassId): void
    {
        if (! School::query()->whereKey($schoolId)->exists()) throw ValidationException::withMessages(['school' => 'The school context is unavailable.']);
        if ($schoolClassId && ! SchoolClass::query()->whereKey($schoolClassId)->whereHas('academicYear', fn ($years) => $years->where('school_id', $schoolId))->exists()) throw ValidationException::withMessages(['classId' => 'Choose a class that belongs to this school.']);
    }

    private function recipientRow(int $campaignId, array $recipient, string $status, ?string $skipReason, mixed $now): array
    {
        return ['sms_campaign_id' => $campaignId, 'audience' => $recipient['audience'], 'recipient_type' => $recipient['recipient_type'], 'recipient_id' => $recipient['recipient_id'], 'user_id' => $recipient['user_id'], 'recipient_name' => Str::limit($recipient['name'], 255, ''), 'phone' => $recipient['phone'], 'normalized_phone' => $status === SmsRecipient::STATUS_SKIPPED ? null : $recipient['normalized_phone'], 'phone_source' => $recipient['phone_source'], 'status' => $status, 'attempts' => 0, 'last_error' => null, 'skip_reason' => $skipReason, 'provider_message_id' => null, 'sent_at' => null, 'failed_at' => null, 'created_at' => $now, 'updated_at' => $now];
    }
}
