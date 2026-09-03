<?php

namespace App\Livewire\LMS\Sms;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SmsCampaign;
use App\Services\Sms\SmsCampaignService;
use App\Services\Sms\SmsRecipientResolver;
use App\Services\Sms\SmsMessageMetrics;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $mode = 'bulk';

    public array $audiences = ['staff'];

    public string $classId = '';

    public string $recipientSearch = '';

    public string $singleRecipientKey = '';

    public string $message = '';

    public bool $showReviewModal = false;

    public int $previewRecipientCount = 0;

    public int $previewSkippedCount = 0;

    public int $previewMissingCount = 0;

    public int $previewDuplicateCount = 0;

    public array $previewSample = [];

    public string $historySearch = '';

    public string $historyStatus = '';

    public bool $showDetailsModal = false;

    public ?int $detailsCampaignId = null;

    public bool $showRetryModal = false;

    public ?int $retryCampaignId = null;

    public function mount(SmsRecipientResolver $resolver): void
    {
        $this->authorize('viewAny', SmsCampaign::class);

        $audience = (string) request()->query('audience', '');
        if (in_array($audience, SmsRecipientResolver::AUDIENCES, true)) {
            $this->audiences = [$audience];
        }

        $type = (string) request()->query('recipient_type', '');
        $id = (string) request()->query('recipient_id', '');
        if ($type !== '' && ctype_digit($id)) {
            $candidate = $resolver->resolveSingle($this->schoolId(), $type.':'.$id);
            if ($candidate) {
                $this->mode = 'single';
                $this->singleRecipientKey = $candidate['key'];
                $this->recipientSearch = $candidate['name'];
            }
        }
    }

    public function updatedHistorySearch(): void
    {
        $this->resetPage();
    }

    public function updatedHistoryStatus(): void
    {
        $this->resetPage();
    }

    public function updatedRecipientSearch(): void
    {
        if ($this->singleRecipientKey !== '') {
            $this->singleRecipientKey = '';
        }
    }

    public function updatedAudiences(): void
    {
        $this->clearPreview();
    }

    public function updatedClassId(): void
    {
        $this->clearPreview();
    }

    public function updatedSingleRecipientKey(): void
    {
        $this->clearPreview();
    }

    public function updatedMessage(): void
    {
        $this->clearPreview();
    }

    public function setMode(string $mode): void
    {
        if (! in_array($mode, ['single', 'bulk'], true)) {
            return;
        }

        $this->mode = $mode;
        $this->resetErrorBag();
        $this->clearPreview();
    }

    public function clearComposer(): void
    {
        $this->reset(['classId', 'recipientSearch', 'singleRecipientKey', 'message']);
        $this->mode = 'bulk';
        $this->audiences = ['staff'];
        $this->resetErrorBag();
        $this->clearPreview();
    }

    public function reviewRecipients(SmsCampaignService $service, SmsMessageMetrics $metrics): void
    {
        $this->authorize('create', SmsCampaign::class);

        try {
            $data = $this->validateComposer($metrics);
            $resolution = $service->preview(
                $this->schoolId(),
                $data['mode'],
                $data['audiences'] ?? [],
                $this->nullableClassId(),
                $data['singleRecipientKey'] ?: null,
            );

            if ($resolution['recipients']->isEmpty()) {
                $field = $this->mode === 'single' ? 'singleRecipientKey' : 'audiences';
                throw ValidationException::withMessages([
                    $field => $this->mode === 'single'
                        ? 'The selected recipient does not have a deliverable mobile number.'
                        : 'No deliverable mobile numbers match this audience.',
                ]);
            }

            $this->previewRecipientCount = $resolution['recipients']->count();
            $this->previewSkippedCount = $resolution['skipped']->count();
            $this->previewMissingCount = $resolution['missing_count'];
            $this->previewDuplicateCount = $resolution['duplicate_count'];
            $this->previewSample = $resolution['recipients']->take(8)->map(fn (array $recipient): array => [
                'name' => $recipient['name'],
                'phone' => $recipient['phone'],
                'audience' => $recipient['audience'],
            ])->values()->all();
            $this->showReviewModal = true;
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the SMS details')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to prepare the recipient list')->error()->asToast()->position('top-end')->show();
        }
    }

    public function queueSms(SmsCampaignService $service, SmsMessageMetrics $metrics): void
    {
        $this->authorize('create', SmsCampaign::class);

        try {
            $data = $this->validateComposer($metrics);
            $campaign = $service->queueCampaign(
                schoolId: $this->schoolId(),
                createdBy: (int) Auth::id(),
                mode: $data['mode'],
                audiences: $data['audiences'] ?? [],
                schoolClassId: $this->nullableClassId(),
                singleRecipientKey: $data['singleRecipientKey'] ?: null,
                message: $data['message'],
            );

            $this->closeModals();
            $this->clearComposer();
            $this->resetPage();

            LivewireAlert::title('SMS queued for '.number_format($campaign->recipient_count).' recipient'.($campaign->recipient_count === 1 ? '' : 's'))
                ->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            $this->showReviewModal = false;
            LivewireAlert::title('Recipient details changed — review the SMS again')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to queue the SMS')->error()->asToast()->position('top-end')->show();
        }
    }

    public function viewCampaign(int $id): void
    {
        $campaign = $this->campaignQuery()->findOrFail($id);
        $this->authorize('view', $campaign);
        $this->detailsCampaignId = $campaign->id;
        $this->showDetailsModal = true;
    }

    public function confirmRetry(int $id): void
    {
        $campaign = $this->campaignQuery()->findOrFail($id);
        $this->authorize('update', $campaign);

        if ($campaign->failed_count < 1) {
            LivewireAlert::title('There are no failed deliveries to retry')->info()->asToast()->position('top-end')->show();

            return;
        }

        $this->retryCampaignId = $campaign->id;
        $this->detailsCampaignId = null;
        $this->showDetailsModal = false;
        $this->showRetryModal = true;
    }

    public function retryFailed(SmsCampaignService $service): void
    {
        if (! $this->retryCampaignId) {
            return;
        }

        try {
            $campaign = $this->campaignQuery()->findOrFail($this->retryCampaignId);
            $this->authorize('update', $campaign);
            $count = $service->retryFailed($campaign);
            $this->closeModals();

            LivewireAlert::title($count > 0 ? number_format($count).' failed '.Str::plural('delivery', $count).' queued again' : 'No failed deliveries remain')
                ->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to retry failed deliveries')->error()->asToast()->position('top-end')->show();
        }
    }

    public function clearHistoryFilters(): void
    {
        $this->reset(['historySearch', 'historyStatus']);
        $this->resetPage();
    }

    public function closeModals(): void
    {
        $this->reset(['showReviewModal', 'showDetailsModal', 'showRetryModal', 'detailsCampaignId', 'retryCampaignId']);
    }

    public function render(SmsRecipientResolver $resolver, SmsMessageMetrics $metrics)
    {
        $schoolId = $this->schoolId();
        $campaigns = $this->campaignQuery()
            ->with(['creator:id,name', 'schoolClass.academicYear:id,name', 'schoolClass.stream:id,name'])
            ->when($this->historySearch !== '', function (Builder $query): void {
                $like = '%'.trim($this->historySearch).'%';
                $query->where(function (Builder $matches) use ($like): void {
                    $matches->where('message', 'like', $like)
                        ->orWhereHas('creator', fn (Builder $creator) => $creator->where('name', 'like', $like));
                });
            })
            ->when($this->historyStatus !== '', fn (Builder $query) => $query->where('status', $this->historyStatus))
            ->latest()
            ->paginate(10);

        $classes = SchoolClass::query()
            ->with(['academicYear:id,name', 'stream:id,name'])
            ->where('status', 'active')
            ->whereHas('academicYear', fn (Builder $years) => $years->where('school_id', $schoolId)->where('is_active', true))
            ->orderBy('name')
            ->get();

        $candidates = $this->mode === 'single'
            ? $resolver->searchCandidates($schoolId, $this->recipientSearch)
            : collect();

        $detailsCampaign = $this->detailsCampaignId
            ? $this->campaignQuery()->with(['creator:id,name', 'schoolClass.academicYear:id,name'])->find($this->detailsCampaignId)
            : null;
        $detailsRecipients = $detailsCampaign
            ? $detailsCampaign->recipients()->orderBy('status')->orderBy('recipient_name')->limit(100)->get()
            : collect();

        $stats = [
            'campaigns' => $this->campaignQuery()->count(),
            'delivered' => (int) $this->campaignQuery()->sum('sent_count'),
            'processing' => $this->campaignQuery()->whereIn('status', [SmsCampaign::STATUS_QUEUED, SmsCampaign::STATUS_PROCESSING])->count(),
            'failed' => (int) $this->campaignQuery()->sum('failed_count'),
        ];

        return view('livewire.lms.sms.index', [
            'campaigns' => $campaigns,
            'classes' => $classes,
            'candidates' => $candidates,
            'detailsCampaign' => $detailsCampaign,
            'detailsRecipients' => $detailsRecipients,
            'stats' => $stats,
            'messageMetrics' => $this->messageMetrics($metrics),
        ]);
    }

    /** @return array<string, mixed> */
    private function validateComposer(SmsMessageMetrics $metrics): array
    {
        $data = $this->validate([
            'mode' => ['required', Rule::in(['single', 'bulk'])],
            'audiences' => [Rule::requiredIf($this->mode === 'bulk'), 'array', 'max:3'],
            'audiences.*' => [Rule::in(SmsRecipientResolver::AUDIENCES)],
            'classId' => ['nullable', 'integer', 'exists:school_classes,id'],
            'singleRecipientKey' => [Rule::requiredIf($this->mode === 'single'), 'nullable', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:1530'],
        ]);

        $maxSegments = (int) config('sms.max_segments', 3);
        if ((int) $this->messageMetrics($metrics)['segments'] > $maxSegments) {
            throw ValidationException::withMessages(['message' => 'Keep the message within '.$maxSegments.' SMS segments.']);
        }

        return $data;
    }

    /** @return array{characters:int, encoding:string, segments:int, remaining:int} */
    private function messageMetrics(SmsMessageMetrics $metrics): array
    {
        $result = null;
        foreach (['analyse', 'analyze', 'calculate', 'metrics', 'for'] as $method) {
            if (method_exists($metrics, $method)) {
                $result = $metrics->{$method}($this->message);
                break;
            }
        }

        $values = is_array($result) ? $result : (is_object($result) ? get_object_vars($result) : []);
        $characters = (int) ($values['characters'] ?? $values['character_count'] ?? mb_strlen($this->message));
        $encoding = (string) ($values['encoding'] ?? 'GSM-7');
        $segments = (int) ($values['segments'] ?? $values['segment_count'] ?? ($characters === 0 ? 0 : (int) ceil($characters / 160)));
        $segmentLimit = str_contains(strtoupper($encoding), 'UCS') ? ($segments > 1 ? 67 : 70) : ($segments > 1 ? 153 : 160);

        return [
            'characters' => $characters,
            'encoding' => $encoding,
            'segments' => $segments,
            'remaining' => (int) ($values['remaining'] ?? $values['remaining_characters'] ?? max(0, ($segmentLimit * max(1, $segments)) - $characters)),
        ];
    }

    private function campaignQuery(): Builder
    {
        return SmsCampaign::query()->where('school_id', $this->schoolId());
    }

    private function nullableClassId(): ?int
    {
        return $this->classId !== '' ? (int) $this->classId : null;
    }

    private function clearPreview(): void
    {
        $this->showReviewModal = false;
        $this->previewRecipientCount = 0;
        $this->previewSkippedCount = 0;
        $this->previewMissingCount = 0;
        $this->previewDuplicateCount = 0;
        $this->previewSample = [];
    }

    private function schoolId(): int
    {
        $schoolId = School::query()->value('id');
        abort_unless($schoolId, 404, 'School setup is required before sending SMS messages.');

        return (int) $schoolId;
    }
}
