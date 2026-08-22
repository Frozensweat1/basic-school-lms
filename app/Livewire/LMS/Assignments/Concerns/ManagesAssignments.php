<?php

namespace App\Livewire\LMS\Assignments\Concerns;

use App\Support\ContentSanitizer;
use App\Models\Assignment;
use App\Models\AssignmentAttachment;
use App\Models\ClassSubject;
use App\Models\Lesson;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Topic;
use App\Support\LmsNotifier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

abstract class ManagesAssignments extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;
    public string $search = '';
    public string $filterClassSubjectId = '';
    public string $filterStatus = '';
    public string $filterDueState = '';
    public string $classSubjectId = '';
    public string $topicId = '';
    public string $lessonId = '';
    public string $teacherId = '';
    public string $title = '';
    public string $instructions = '';
    public string $maxScore = '100';
    public string $opensAt = '';
    public string $dueAt = '';
    public string $status = 'draft';
    public bool $allowLateSubmission = false;
    public array $attachmentFiles = [];

    abstract protected function classSubjects(): Builder;

    abstract protected function teacherIdFor(array $data, ClassSubject $classSubject): int;

    abstract protected function componentView(): string;

    public function create(): void
    {
        $this->authorize('create', Assignment::class);
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterClassSubjectId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDueState(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterClassSubjectId', 'filterStatus', 'filterDueState']);
        $this->resetPage();
    }

    public function edit(Assignment $assignment): void
    {
        $this->authorize('update', $assignment);
        $this->managedClassSubject($assignment->class_subject_id);

        $this->editingId = $assignment->id;
        $this->classSubjectId = (string) $assignment->class_subject_id;
        $this->topicId = (string) ($assignment->topic_id ?? '');
        $this->lessonId = (string) ($assignment->lesson_id ?? '');
        $this->teacherId = (string) $assignment->teacher_id;
        $this->title = $assignment->title;
        $this->instructions = $assignment->instructions;
        $this->maxScore = (string) $assignment->max_score;
        $this->opensAt = $assignment->opens_at?->format('Y-m-d\TH:i') ?? '';
        $this->dueAt = $assignment->due_at->format('Y-m-d\TH:i');
        $this->allowLateSubmission = $assignment->allow_late_submission;
        $this->status = $assignment->status;
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $assignment = $this->editingId ? Assignment::findOrFail($this->editingId) : null;
        $this->authorize($assignment ? 'update' : 'create', $assignment ?? Assignment::class);

        try {
            if ($assignment) {
                $this->managedClassSubject($assignment->class_subject_id);
            }

            $data = $this->validate([
                'classSubjectId' => ['required', 'integer', Rule::exists('class_subjects', 'id')],
                'topicId' => ['nullable', 'integer', Rule::exists('topics', 'id')],
                'lessonId' => ['nullable', 'integer', Rule::exists('lessons', 'id')],
                'teacherId' => ['nullable', 'integer', Rule::exists('teachers', 'id')],
                'title' => ['required', 'string', 'max:255'],
                'instructions' => ['required', 'string', 'max:50000'],
                'maxScore' => ['required', 'numeric', 'min:0.01', 'max:999999'],
                'opensAt' => ['nullable', 'date'],
                'dueAt' => ['required', 'date', 'after:opensAt'],
                'status' => ['required', Rule::in(['draft', 'published', 'closed'])],
                'allowLateSubmission' => ['boolean'],
                'attachmentFiles' => ['array'],
                'attachmentFiles.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,zip'],
            ]);

            $classSubject = $this->managedClassSubject((int) $data['classSubjectId']);
            $topic = filled($data['topicId']) ? Topic::findOrFail($data['topicId']) : null;
            $lesson = filled($data['lessonId']) ? Lesson::findOrFail($data['lessonId']) : null;

            abort_unless(! $topic || $topic->class_subject_id === $classSubject->id, 422, 'The selected topic does not belong to this class subject.');
            abort_unless(! $lesson || ($topic && $lesson->topic_id === $topic->id), 422, 'The selected lesson does not belong to the selected topic.');

            $teacherId = $this->teacherIdFor($data, $classSubject);
            abort_unless(Teacher::whereKey($teacherId)->where('school_id', $this->schoolId())->exists(), 422, 'Choose a teacher belonging to this school.');

            $wasPublished = $assignment?->status === 'published';
            $savedAssignment = Assignment::updateOrCreate(
                ['id' => $assignment?->id],
                [
                    'class_subject_id' => $classSubject->id,
                    'topic_id' => $topic?->id,
                    'lesson_id' => $lesson?->id,
                    'teacher_id' => $teacherId,
                    'title' => $data['title'],
                    'instructions' => app(ContentSanitizer::class)->clean($data['instructions']),
                    'max_score' => $data['maxScore'],
                    'opens_at' => filled($data['opensAt']) ? Carbon::parse($data['opensAt']) : null,
                    'due_at' => Carbon::parse($data['dueAt']),
                    'allow_late_submission' => $data['allowLateSubmission'],
                    'status' => $data['status'],
                ],
            );

            foreach ($data['attachmentFiles'] ?? [] as $file) {
                $path = $file->store('assignments/attachments/'.$savedAssignment->id, 'local');

                AssignmentAttachment::create([
                    'assignment_id' => $savedAssignment->id,
                    'name' => $file->getClientOriginalName(),
                    'disk' => 'local',
                    'path' => $path,
                    'size' => $file->getSize(),
                ]);
            }

            if ($savedAssignment->status === 'published' && ! $wasPublished) {
                LmsNotifier::send(
                    LmsNotifier::classAudience($classSubject->schoolClass),
                    'New assignment available',
                    $savedAssignment->title.' is now available for your class.',
                    null,
                    'assignment',
                );
            }

            $this->showFormModal = false;
            $this->resetForm();
            $this->resetPage();
            LivewireAlert::title($assignment ? 'Assignment updated' : 'Assignment created')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the form')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save assignment')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(Assignment $assignment): void
    {
        $this->authorize('delete', $assignment);
        $this->managedClassSubject($assignment->class_subject_id);
        $this->deletingId = $assignment->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $assignment = Assignment::findOrFail($this->deletingId);
        $this->authorize('delete', $assignment);
        $this->managedClassSubject($assignment->class_subject_id);

        try {
            $assignment->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Assignment archived')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to archive assignment')->error()->asToast()->position('top-end')->show();
        }
    }

    public function closeModals(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function downloadAttachment(int $attachmentId)
    {
        $attachment = AssignmentAttachment::with('assignment')
            ->whereKey($attachmentId)
            ->firstOrFail();

        $this->authorize('view', $attachment->assignment);
        $this->managedClassSubject($attachment->assignment->class_subject_id);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->name);
    }

    public function render()
    {
        $classSubjects = $this->classSubjects()->get();
        $classSubjectIds = $classSubjects->pluck('id');
        $topics = Topic::whereIn('class_subject_id', $classSubjectIds)->orderBy('sequence')->get();
        $search = trim($this->search);

        return view($this->componentView(), [
            'assignments' => Assignment::query()
                ->with(['classSubject.schoolClass', 'classSubject.subject', 'topic', 'lesson', 'teacher'])
                ->whereIn('class_subject_id', $classSubjectIds)
                ->withCount(['submissions', 'attachments'])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($assignments) use ($search): void {
                        $assignments->where('title', 'like', "%{$search}%")
                            ->orWhere('instructions', 'like', "%{$search}%")
                            ->orWhereHas('topic', fn ($topics) => $topics->where('title', 'like', "%{$search}%"))
                            ->orWhereHas('lesson', fn ($lessons) => $lessons->where('title', 'like', "%{$search}%"))
                            ->orWhereHas('classSubject.subject', fn ($subjects) => $subjects->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('classSubject.schoolClass', fn ($classes) => $classes->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('teacher', function ($teachers) use ($search): void {
                                $teachers->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('employee_id', 'like', "%{$search}%");
                            });
                    });
                })
                ->when(filled($this->filterClassSubjectId), fn ($query) => $query->where('class_subject_id', $this->filterClassSubjectId))
                ->when(filled($this->filterStatus), fn ($query) => $query->where('status', $this->filterStatus))
                ->when($this->filterDueState === 'upcoming', fn ($query) => $query->whereNotNull('opens_at')->where('opens_at', '>', now()))
                ->when($this->filterDueState === 'open', fn ($query) => $query->where(fn ($assignments) => $assignments->whereNull('opens_at')->orWhere('opens_at', '<=', now()))->where('due_at', '>=', now()))
                ->when($this->filterDueState === 'overdue', fn ($query) => $query->where('due_at', '<', now()))
                ->latest()
                ->paginate(15),
            'classSubjects' => $classSubjects,
            'topics' => $topics,
            'lessons' => Lesson::whereIn('topic_id', $topics->pluck('id'))->orderBy('sequence')->get(),
            'teachers' => Teacher::where('school_id', $this->schoolId())->where('status', 'active')->orderBy('last_name')->get(),
            'assignmentAttachments' => $this->editingId
                ? AssignmentAttachment::where('assignment_id', $this->editingId)->latest()->get()
                : collect(),
        ]);
    }

    protected function schoolId(): int
    {
        return (int) School::query()->value('id');
    }

    protected function managedClassSubject(int $classSubjectId): ClassSubject
    {
        return $this->classSubjects()->whereKey($classSubjectId)->firstOrFail();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'classSubjectId', 'topicId', 'lessonId', 'teacherId', 'title', 'instructions', 'maxScore', 'opensAt', 'dueAt', 'status', 'allowLateSubmission', 'attachmentFiles']);
        $this->maxScore = '100';
        $this->status = 'draft';
        $this->resetValidation();
    }
}
