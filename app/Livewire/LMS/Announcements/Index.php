<?php

namespace App\Livewire\LMS\Announcements;

use App\Models\{Announcement, School, SchoolClass, Subject};
use App\Support\LmsNotifier;
use App\Support\ContentSanitizer;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\{Rule, ValidationException};
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
    public bool $showFormModal = false, $showDeleteModal = false;
    public ?int $editingId = null, $deletingId = null;
    public string $title = '', $content = '', $audience = 'school', $classId = '', $subjectId = '', $publishedAt = '', $expiresAt = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Announcement::class);
    }
    public function create(): void
    {
        $this->authorize('create', Announcement::class);
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(Announcement $announcement): void
    {
        $this->authorize('update', $announcement);
        $this->editingId = $announcement->id;
        $this->title = $announcement->title;
        $this->content = $announcement->content;
        $this->audience = $announcement->audience;
        $this->classId = (string) ($announcement->school_class_id ?? '');
        $this->subjectId = (string) ($announcement->subject_id ?? '');
        $this->publishedAt = $announcement->published_at?->format('Y-m-d\TH:i') ?? '';
        $this->expiresAt = $announcement->expires_at?->format('Y-m-d\TH:i') ?? '';
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $item = $this->editingId ? Announcement::findOrFail($this->editingId) : null;
        $this->authorize($item ? 'update' : 'create', $item ?? Announcement::class);
        try {
            $data = $this->validate(['title' => ['required', 'string', 'max:255'], 'content' => ['required', 'string', 'max:50000'], 'audience' => ['required', Rule::in(['school', 'class', 'subject'])], 'classId' => ['nullable', 'integer'], 'subjectId' => ['nullable', 'integer'], 'publishedAt' => ['nullable', 'date'], 'expiresAt' => ['nullable', 'date', 'after:publishedAt']]);
            $schoolId = (int) School::query()->value('id');
            $classId = $data['audience'] === 'class' ? (int) $data['classId'] : null;
            $subjectId = $data['audience'] === 'subject' ? (int) $data['subjectId'] : null;
            if ($data['audience'] === 'class') abort_unless($classId && SchoolClass::whereKey($classId)->whereHas('academicYear', fn($q) => $q->where('school_id', $schoolId))->exists(), 422, 'Choose a class belonging to this school.');
            if ($data['audience'] === 'subject') abort_unless($subjectId && Subject::whereKey($subjectId)->where('school_id', $schoolId)->exists(), 422, 'Choose a subject belonging to this school.');
            $wasCreated = ! $item;
            $announcement = Announcement::updateOrCreate(['id' => $item?->id], ['school_id' => $schoolId, 'school_class_id' => $classId, 'subject_id' => $subjectId, 'created_by' => $item?->created_by ?? auth()->id(), 'title' => $data['title'], 'content' => app(ContentSanitizer::class)->clean($data['content']), 'audience' => $data['audience'], 'published_at' => filled($data['publishedAt']) ? Carbon::parse($data['publishedAt']) : now(), 'expires_at' => filled($data['expiresAt']) ? Carbon::parse($data['expiresAt']) : null]);
            if ($wasCreated && $announcement->published_at?->isPast()) LmsNotifier::send(LmsNotifier::announcementAudience($announcement), 'School announcement', $announcement->title, null, 'announcement');
            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title($item ? 'Announcement updated' : 'Announcement published')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the announcement')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save announcement')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(Announcement $item): void
    {
        $this->authorize('delete', $item);
        $this->deletingId = $item->id;
        $this->showDeleteModal = true;
    }
    public function delete(): void
    {
        $item = Announcement::findOrFail($this->deletingId);
        $this->authorize('delete', $item);
        try {
            $item->delete();
            $this->showDeleteModal = false;
            LivewireAlert::title('Announcement archived')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to archive announcement')->error()->asToast()->position('top-end')->show();
        }
    }
    public function closeModals(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
        $this->resetErrorBag();
    }
    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'title', 'content', 'audience', 'classId', 'subjectId', 'publishedAt', 'expiresAt']);
        $this->audience = 'school';
        $this->resetValidation();
    }
    protected function schoolId(): int
    {
        return (int) School::query()->value('id');
    }
    public function render()
    {
        $schoolId = $this->schoolId();
        return view('livewire.lms.announcements.index', ['announcements' => Announcement::where('school_id', $schoolId)->latest('published_at')->paginate(15), 'classes' => SchoolClass::whereHas('academicYear', fn($q) => $q->where('school_id', $schoolId))->get(), 'subjects' => Subject::where('school_id', $schoolId)->get()]);
    }
}
