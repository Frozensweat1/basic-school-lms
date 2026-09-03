<?php

namespace App\Services\Emails;

use App\Jobs\DispatchEmailCampaignJob;
use App\Models\EmailCampaign;
use App\Models\EmailRecipient;
use App\Models\School;
use App\Models\SchoolClass;
use App\Support\AuditLogger;
use App\Support\ContentSanitizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailCampaignService
{
    public const MAX_RECIPIENTS = 5000;

    public function __construct(
        private readonly EmailRecipientResolver $resolver,
        private readonly ContentSanitizer $sanitizer,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return array{recipients: Collection, skipped: Collection, missing_count: int, duplicate_count: int}
     */
    public function preview(int $schoolId, string $mode, array $audiences, ?int $schoolClassId, ?string $singleRecipientKey): array
    {
        $this->validateSchoolAndClass($schoolId, $schoolClassId);

        if ($mode === 'single') {
            $candidate = $this->resolver->resolveSingle($schoolId, (string) $singleRecipientKey);

            return [
                'recipients' => collect($candidate && $candidate['sendable'] ? [$candidate] : []),
                'skipped' => collect($candidate && ! $candidate['sendable'] ? [[...$candidate, 'skip_reason' => 'No valid email address']] : []),
                'missing_count' => $candidate && ! $candidate['sendable'] ? 1 : 0,
                'duplicate_count' => 0,
            ];
        }

        if ($mode !== 'bulk') {
            throw ValidationException::withMessages(['mode' => 'Choose single or bulk email mode.']);
        }

        $audiences = $this->normalizeAudiences($audiences);
        if ($audiences === []) {
            throw ValidationException::withMessages(['audiences' => 'Choose at least one recipient group.']);
        }

        return $this->resolver->resolveBulk($schoolId, $audiences, $schoolClassId);
    }

    public function queueCampaign(
        int $schoolId,
        int $createdBy,
        string $mode,
        array $audiences,
        ?int $schoolClassId,
        ?string $singleRecipientKey,
        string $subject,
        string $body,
    ): EmailCampaign {
        $subject = trim((string) preg_replace('/[\r\n]+/', ' ', $subject));
        $body = $this->sanitizer->clean($body);
        $plainBody = trim(html_entity_decode(strip_tags(str_replace('&nbsp;', ' ', $body))));

        if ($subject === '') {
            throw ValidationException::withMessages(['subject' => 'Enter an email subject.']);
        }

        if ($plainBody === '') {
            throw ValidationException::withMessages(['body' => 'Enter an email message.']);
        }

        $resolution = $this->preview($schoolId, $mode, $audiences, $schoolClassId, $singleRecipientKey);
        if ($resolution['recipients']->isEmpty()) {
            $field = $mode === 'single' ? 'singleRecipientKey' : 'audiences';
            $message = $mode === 'single'
                ? 'The selected recipient does not have a valid email address.'
                : 'No deliverable email addresses match the selected audience.';

            throw ValidationException::withMessages([$field => $message]);
        }

        if ($resolution['recipients']->count() > self::MAX_RECIPIENTS) {
            throw ValidationException::withMessages([
                'audiences' => 'This audience exceeds the '.number_format(self::MAX_RECIPIENTS).'-recipient safety limit. Narrow it with a class filter.',
            ]);
        }

        $normalizedAudiences = $mode === 'single'
            ? $resolution['recipients']->pluck('audience')->unique()->values()->all()
            : $this->normalizeAudiences($audiences);

        $campaign = DB::transaction(function () use (
            $schoolId,
            $createdBy,
            $schoolClassId,
            $mode,
            $normalizedAudiences,
            $singleRecipientKey,
            $subject,
            $body,
            $resolution,
        ): EmailCampaign {
            $campaign = EmailCampaign::query()->create([
                'school_id' => $schoolId,
                'created_by' => $createdBy,
                'school_class_id' => $schoolClassId,
                'mode' => $mode,
                'audiences' => $normalizedAudiences,
                'filters' => array_filter([
                    'school_class_id' => $schoolClassId,
                    'single_recipient_key' => $mode === 'single' ? $singleRecipientKey : null,
                ], fn (mixed $value): bool => $value !== null && $value !== ''),
                'subject' => Str::limit($subject, 255, ''),
                'body' => $body,
                'status' => EmailCampaign::STATUS_QUEUED,
                'recipient_count' => $resolution['recipients']->count(),
                'skipped_count' => $resolution['skipped']->count(),
                'queued_at' => now(),
            ]);

            $now = now();
            $rows = $resolution['recipients']->map(fn (array $recipient): array => $this->recipientRow(
                $campaign->id,
                $recipient,
                EmailRecipient::STATUS_QUEUED,
                null,
                $now,
            ))->concat(
                $resolution['skipped']->map(fn (array $recipient): array => $this->recipientRow(
                    $campaign->id,
                    $recipient,
                    EmailRecipient::STATUS_SKIPPED,
                    $recipient['skip_reason'] ?? 'No valid email address',
                    $now,
                )),
            );

            $rows->chunk(250)->each(fn ($chunk) => EmailRecipient::query()->insert($chunk->all()));

            return $campaign;
        });

        DispatchEmailCampaignJob::dispatch($campaign->id)->onQueue('default')->afterCommit();

        $this->auditLogger->record('email.campaign_queued', $campaign, newValues: [
            'mode' => $campaign->mode,
            'audiences' => $campaign->audiences,
            'recipient_count' => $campaign->recipient_count,
            'skipped_count' => $campaign->skipped_count,
        ]);

        return $campaign->fresh(['creator', 'schoolClass']);
    }

    public function retryFailed(EmailCampaign $campaign): int
    {
        $retryCount = DB::transaction(function () use ($campaign): int {
            $lockedCampaign = EmailCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            $count = $lockedCampaign->recipients()->where('status', EmailRecipient::STATUS_FAILED)->count();

            if ($count === 0) {
                return 0;
            }

            $lockedCampaign->recipients()
                ->where('status', EmailRecipient::STATUS_FAILED)
                ->update([
                    'status' => EmailRecipient::STATUS_QUEUED,
                    'last_error' => null,
                    'failed_at' => null,
                    'updated_at' => now(),
                ]);

            $lockedCampaign->forceFill([
                'status' => EmailCampaign::STATUS_QUEUED,
                'failed_count' => 0,
                'completed_at' => null,
                'queued_at' => now(),
            ])->save();

            return $count;
        });

        if ($retryCount > 0) {
            DispatchEmailCampaignJob::dispatch($campaign->id)->onQueue('default')->afterCommit();
            $this->auditLogger->record('email.campaign_retried', $campaign, newValues: ['retry_count' => $retryCount]);
        }

        return $retryCount;
    }

    public function markProcessing(int $campaignId): void
    {
        EmailCampaign::query()
            ->whereKey($campaignId)
            ->where('status', EmailCampaign::STATUS_QUEUED)
            ->update(['status' => EmailCampaign::STATUS_PROCESSING, 'updated_at' => now()]);
    }

    public function refreshStatus(int $campaignId): void
    {
        DB::transaction(function () use ($campaignId): void {
            $campaign = EmailCampaign::query()->lockForUpdate()->find($campaignId);
            if (! $campaign) {
                return;
            }

            $counts = $campaign->recipients()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $sent = (int) ($counts[EmailRecipient::STATUS_SENT] ?? 0);
            $failed = (int) ($counts[EmailRecipient::STATUS_FAILED] ?? 0);
            $skipped = (int) ($counts[EmailRecipient::STATUS_SKIPPED] ?? 0);
            $queued = (int) ($counts[EmailRecipient::STATUS_QUEUED] ?? 0);
            $sending = (int) ($counts[EmailRecipient::STATUS_SENDING] ?? 0);
            $deliverable = $sent + $failed + $queued + $sending;

            $status = match (true) {
                $queued > 0 || $sending > 0 => EmailCampaign::STATUS_PROCESSING,
                $deliverable > 0 && $sent === $deliverable => EmailCampaign::STATUS_COMPLETED,
                $deliverable > 0 && $failed === $deliverable => EmailCampaign::STATUS_FAILED,
                $failed > 0 => EmailCampaign::STATUS_PARTIAL,
                default => EmailCampaign::STATUS_FAILED,
            };

            $campaign->forceFill([
                'status' => $status,
                'recipient_count' => $deliverable,
                'sent_count' => $sent,
                'failed_count' => $failed,
                'skipped_count' => $skipped,
                'completed_at' => in_array($status, [
                    EmailCampaign::STATUS_COMPLETED,
                    EmailCampaign::STATUS_PARTIAL,
                    EmailCampaign::STATUS_FAILED,
                ], true) ? now() : null,
            ])->save();
        });
    }

    /** @return list<string> */
    private function normalizeAudiences(array $audiences): array
    {
        return collect(EmailRecipientResolver::AUDIENCES)
            ->filter(fn (string $audience): bool => in_array($audience, $audiences, true))
            ->values()
            ->all();
    }

    private function validateSchoolAndClass(int $schoolId, ?int $schoolClassId): void
    {
        if (! School::query()->whereKey($schoolId)->exists()) {
            throw ValidationException::withMessages(['school' => 'The school context is unavailable.']);
        }

        if ($schoolClassId && ! SchoolClass::query()
            ->whereKey($schoolClassId)
            ->whereHas('academicYear', fn ($years) => $years->where('school_id', $schoolId))
            ->exists()) {
            throw ValidationException::withMessages(['classId' => 'Choose a class that belongs to this school.']);
        }
    }

    /** @param array<string, mixed> $recipient */
    private function recipientRow(int $campaignId, array $recipient, string $status, ?string $skipReason, mixed $now): array
    {
        return [
            'email_campaign_id' => $campaignId,
            'audience' => $recipient['audience'],
            'recipient_type' => $recipient['recipient_type'],
            'recipient_id' => $recipient['recipient_id'],
            'user_id' => $recipient['user_id'],
            'recipient_name' => Str::limit($recipient['name'], 255, ''),
            'email' => $recipient['email'],
            'normalized_email' => $status === EmailRecipient::STATUS_SKIPPED ? null : $recipient['normalized_email'],
            'status' => $status,
            'attempts' => 0,
            'last_error' => null,
            'skip_reason' => $skipReason,
            'message_id' => null,
            'sent_at' => null,
            'failed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
