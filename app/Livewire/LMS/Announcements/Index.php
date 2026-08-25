<?php

namespace App\Livewire\LMS\Announcements;

use App\Jobs\SendAnnouncementNotificationsJob;
use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Support\ContentSanitizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $filterAudience = '';

    public string $filterState = '';

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public bool $showPublishModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public ?int $publishingId = null;

    public string $title = '';

    public string $content = '';

    public string $audience = 'school';

    public string $classId = '';

    public string $subjectId = '';

    public string $publicationMode = 'publish_now';

    public string $publishedAt = '';

    public string $expiresAt = '';

    public array $attachmentFiles = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Announcement::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterAudience(): void
    {
        $this->resetPage();
    }

    public function updatedFilterState(): void
    {
        $this->resetPage();
    }

    public function updatedAudience(): void
    {
        $this->reset(['classId', 'subjectId']);
    }

    public function updatedPublicationMode(): void
    {
        if ($this->publicationMode !== 'schedule') {
            $this->publishedAt = '';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterAudience', 'filterState']);
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Announcement::class);
        $this->resetForm();
        $this->audience = $this->defaultAudience();
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $announcement = $this->announcementQuery()->findOrFail($id);
        $this->authorize('update', $announcement);

        $this->editingId = $announcement->id;
        $this->title = $announcement->title;
        $this->content = $announcement->content;
        $this->audience = $announcement->audience;
        $this->classId = (string) ($announcement->school_class_id ?? '');
        $this->subjectId = (string) ($announcement->subject_id ?? '');
        $this->publicationMode = ! $announcement->published_at
            ? 'draft'
            : ($announcement->published_at->isFuture() ? 'schedule' : 'publish_now');
        $this->publishedAt = $announcement->published_at?->isFuture()
            ? $announcement->published_at->format('Y-m-d\TH:i')
            : '';
        $this->expiresAt = $announcement->expires_at?->format('Y-m-d\TH:i') ?? '';
        $this->attachmentFiles = [];
        $this->resetErrorBag();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $announcement = $this->editingId ? $this->announcementQuery()->findOrFail($this->editingId) : null;
        $this->authorize($announcement ? 'update' : 'create', $announcement ?? Announcement::class);

        try {
            $data = $this->validate([
                'title' => ['required', 'string', 'max:255'],
                'content' => ['required', 'string', 'max:50000'],
                'audience' => ['required', Rule::in($this->allowedAudiences())],
                'classId' => [Rule::requiredIf($this->audience === 'class'), 'nullable', 'integer'],
                'subjectId' => [Rule::requiredIf($this->audience === 'subject'), 'nullable', 'integer'],
                'publicationMode' => ['required', Rule::in(['draft', 'publish_now', 'schedule'])],
                'publishedAt' => [Rule::requiredIf($this->publicationMode === 'schedule'), 'nullable', 'date'],
                'expiresAt' => ['nullable', 'date'],
                'attachmentFiles' => ['array', 'max:5'],
                'attachmentFiles.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp'],
            ]);

            $schoolId = $this->schoolId();
            $classId = $data['audience'] === 'class' ? (int) $data['classId'] : null;
            $subjectId = $data['audience'] === 'subject' ? (int) $data['subjectId'] : null;
            if ($classId) {
                $this->availableClasses()->whereKey($classId)->firstOrFail();
            }
            if ($subjectId) {
                $this->availableSubjects()->whereKey($subjectId)->firstOrFail();
            }

            $publishedAt = match ($data['publicationMode']) {
                'draft' => null,
                'publish_now' => now(),
                default => Carbon::parse($data['publishedAt']),
            };
            if ($data['publicationMode'] === 'schedule' && $publishedAt?->isPast()) {
                throw ValidationException::withMessages(['publishedAt' => 'Choose a future publication date and time.']);
            }
            $expiresAt = filled($data['expiresAt']) ? Carbon::parse($data['expiresAt']) : null;
            if ($expiresAt && $expiresAt->lte($publishedAt ?? now())) {
                throw ValidationException::withMessages(['expiresAt' => 'Expiration must be later than publication.']);
            }

            $cleanContent = app(ContentSanitizer::class)->clean($data['content']);
            if (blank(strip_tags($cleanContent))) {
                throw ValidationException::withMessages(['content' => 'Enter meaningful announcement content.']);
            }

            $saved = Announcement::updateOrCreate(
                ['id' => $announcement?->id],
                [
                    'school_id' => $schoolId,
                    'school_class_id' => $classId,
                    'subject_id' => $subjectId,
                    'created_by' => $announcement?->created_by ?? auth()->id(),
                    'title' => trim($data['title']),
                    'content' => $cleanContent,
                    'audience' => $data['audience'],
                    'published_at' => $publishedAt,
                    'expires_at' => $expiresAt,
                    'notified_at' => in_array($data['publicationMode'], ['draft', 'schedule'], true)
                        ? null
                        : $announcement?->notified_at,
                ],
            );

            foreach ($this->attachmentFiles as $file) {
                $path = $file->store('announcements/'.$saved->id, 'local');
                AnnouncementAttachment::create([
                    'announcement_id' => $saved->id,
                    'name' => $file->getClientOriginalName(),
                    'disk' => 'local',
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            if ($saved->published_at && ! $saved->notified_at) {
                $dispatch = SendAnnouncementNotificationsJob::dispatch($saved->id);
                if ($saved->published_at->isFuture()) {
                    $dispatch->delay($saved->published_at);
                }
            }

            $state = $saved->publicationState();
            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title($announcement ? 'Announcement updated' : match ($state) {
                'draft' => 'Announcement saved as draft',
                'scheduled' => 'Announcement scheduled',
                default => 'Announcement published',
            })->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the announcement form')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save announcement')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmPublish(int $id): void
    {
        $announcement = $this->announcementQuery()->findOrFail($id);
        $this->authorize('update', $announcement);
        $this->publishingId = $id;
        $this->showPublishModal = true;
    }

    public function publishNow(): void
    {
        $announcement = $this->announcementQuery()->findOrFail($this->publishingId);
        $this->authorize('update', $announcement);

        try {
            $announcement->forceFill(['published_at' => now(), 'notified_at' => null])->save();
            SendAnnouncementNotificationsJob::dispatch($announcement->id);
            $this->showPublishModal = false;
            $this->publishingId = null;
            LivewireAlert::title('Announcement published')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to publish announcement')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(int $id): void
    {
        $announcement = $this->announcementQuery()->findOrFail($id);
        $this->authorize('delete', $announcement);
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $announcement = $this->announcementQuery()->findOrFail($this->deletingId);
        $this->authorize('delete', $announcement);

        try {
            $announcement->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Announcement archived')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to archive announcement')->error()->asToast()->position('top-end')->show();
        }
    }

    public function removeAttachment(int $attachmentId): void
    {
        $attachment = AnnouncementAttachment::query()->with('announcement')->findOrFail($attachmentId);
        abort_unless($this->announcementQuery()->whereKey($attachment->announcement_id)->exists(), 404);
        $this->authorize('update', $attachment->announcement);

        try {
            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->delete();
            LivewireAlert::title('Attachment removed')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to remove attachment')->error()->asToast()->position('top-end')->show();
        }
    }

    public function downloadAttachment(int $attachmentId)
    {
        $attachment = AnnouncementAttachment::query()->with('announcement')->findOrFail($attachmentId);
        abort_unless($this->announcementQuery()->whereKey($attachment->announcement_id)->exists(), 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->name);
    }

    public function closeModals(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
        $this->showPublishModal = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function render()
    {
        $filtered = $this->announcementQuery()
            ->with(['author', 'schoolClass', 'subject', 'attachments'])
            ->withCount('attachments')
            ->when(filled($this->search), function (Builder $query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn (Builder $items) => $items
                    ->where('title', 'like', $term)
                    ->orWhere('content', 'like', $term)
                    ->orWhereHas('author', fn (Builder $authors) => $authors->where('name', 'like', $term)));
            })
            ->when(filled($this->filterAudience), fn (Builder $query) => $query->where('audience', $this->filterAudience));
        $this->applyStateFilter($filtered, $this->filterState);

        $base = $this->announcementQuery();

        return view('livewire.lms.announcements.index', [
            'announcements' => (clone $filtered)->latest('published_at')->latest()->paginate(12),
            'classes' => $this->availableClasses()->orderBy('name')->get(),
            'subjects' => $this->availableSubjects()->orderBy('name')->get(),
            'allowedAudiences' => $this->allowedAudiences(),
            'existingAttachments' => $this->editingId
                ? AnnouncementAttachment::query()->where('announcement_id', $this->editingId)->latest()->get()
                : collect(),
            'totalCount' => (clone $base)->count(),
            'publishedCount' => $this->applyStateFilter(clone $base, 'published')->count(),
            'scheduledCount' => $this->applyStateFilter(clone $base, 'scheduled')->count(),
            'draftCount' => $this->applyStateFilter(clone $base, 'draft')->count(),
        ]);
    }

    protected function announcementQuery(): Builder
    {
        return Announcement::query()->where('school_id', $this->schoolId());
    }

    protected function availableClasses(): Builder
    {
        return SchoolClass::query()->whereHas('academicYear', fn (Builder $query) => $query->where('school_id', $this->schoolId()));
    }

    protected function availableSubjects(): Builder
    {
        return Subject::query()->where('school_id', $this->schoolId())->where('is_active', true);
    }

    protected function allowedAudiences(): array
    {
        return Announcement::AUDIENCES;
    }

    protected function defaultAudience(): string
    {
        return 'school';
    }

    protected function schoolId(): int
    {
        $schoolId = School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school before managing announcements.');

        return (int) $schoolId;
    }

    private function applyStateFilter(Builder $query, string $state): Builder
    {
        return match ($state) {
            'draft' => $query->whereNull('published_at'),
            'scheduled' => $query->where('published_at', '>', now()),
            'expired' => $query->whereNotNull('expires_at')->where('expires_at', '<=', now()),
            'published' => $query->published(),
            default => $query,
        };
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'deletingId', 'publishingId', 'title', 'content', 'audience', 'classId', 'subjectId',
            'publicationMode', 'publishedAt', 'expiresAt', 'attachmentFiles',
        ]);
        $this->audience = 'school';
        $this->publicationMode = 'publish_now';
        $this->resetValidation();
    }
}
