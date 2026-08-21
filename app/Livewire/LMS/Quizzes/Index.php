<?php

namespace App\Livewire\LMS\Quizzes;

use App\Support\ContentSanitizer;
use App\Models\{ClassSubject, Lesson, Quiz, School, Teacher, Topic};
use App\Support\LmsNotifier;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\{Rule, ValidationException};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public bool $randomizeQuestions = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;
    public string $classSubjectId = '';
    public string $topicId = '';
    public string $lessonId = '';
    public string $teacherId = '';
    public string $title = '';
    public string $instructions = '';
    public string $timeLimitMinutes = '';
    public string $passMark = '';
    public string $maxAttempts = '1';
    public string $opensAt = '';
    public string $closesAt = '';
    public string $status = 'draft';

    public function mount(): void
    {
        $this->authorize('viewAny', Quiz::class);
    }

    public function create(): void
    {
        $this->authorize('create', Quiz::class);
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(Quiz $quiz): void
    {
        $this->authorize('update', $quiz);
        $this->editingId = $quiz->id;
        $this->classSubjectId = (string) $quiz->class_subject_id;
        $this->topicId = (string) ($quiz->topic_id ?? '');
        $this->lessonId = (string) ($quiz->lesson_id ?? '');
        $this->teacherId = (string) $quiz->teacher_id;
        $this->title = $quiz->title;
        $this->instructions = $quiz->instructions ?? '';
        $this->timeLimitMinutes = (string) ($quiz->time_limit_minutes ?? '');
        $this->passMark = (string) ($quiz->pass_mark ?? '');
        $this->maxAttempts = (string) $quiz->max_attempts;
        $this->randomizeQuestions = $quiz->randomize_questions;
        $this->opensAt = $quiz->opens_at?->format('Y-m-d\TH:i') ?? '';
        $this->closesAt = $quiz->closes_at?->format('Y-m-d\TH:i') ?? '';
        $this->status = $quiz->status;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $quiz = $this->editingId ? Quiz::findOrFail($this->editingId) : null;
        $this->authorize($quiz ? 'update' : 'create', $quiz ?? Quiz::class);

        try {
            $data = $this->validate([
                'classSubjectId' => ['required', 'integer', Rule::exists('class_subjects', 'id')],
                'topicId' => ['nullable', 'integer', Rule::exists('topics', 'id')],
                'lessonId' => ['nullable', 'integer', Rule::exists('lessons', 'id')],
                'teacherId' => ['nullable', 'integer', Rule::exists('teachers', 'id')],
                'title' => ['required', 'string', 'max:255'],
                'instructions' => ['nullable', 'string', 'max:50000'],
                'timeLimitMinutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
                'passMark' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'maxAttempts' => ['required', 'integer', 'min:1', 'max:50'],
                'opensAt' => ['nullable', 'date'],
                'closesAt' => ['nullable', 'date', 'after:opensAt'],
                'status' => ['required', Rule::in(['draft', 'published', 'closed'])],
                'randomizeQuestions' => ['boolean'],
            ]);

            $classSubject = ClassSubject::with('schoolClass.academicYear')->findOrFail($data['classSubjectId']);
            $schoolId = School::query()->value('id');
            abort_unless($classSubject->schoolClass->academicYear->school_id === $schoolId, 422, 'Choose a class subject belonging to this school.');
            $topic = filled($data['topicId']) ? Topic::findOrFail($data['topicId']) : null;
            $lesson = filled($data['lessonId']) ? Lesson::findOrFail($data['lessonId']) : null;
            abort_unless(! $topic || $topic->class_subject_id === $classSubject->id, 422, 'The selected topic does not belong to this class subject.');
            abort_unless(! $lesson || ($topic && $lesson->topic_id === $topic->id), 422, 'The selected lesson does not belong to the selected topic.');

            $teacherId = auth()->user()->hasRole('teacher')
                ? auth()->user()->teacher?->id
                : (filled($data['teacherId']) ? (int) $data['teacherId'] : $classSubject->teacher_id);
            abort_unless($teacherId && Teacher::whereKey($teacherId)->where('school_id', $schoolId)->exists(), 422, 'Assign a teacher belonging to this school.');
            if (auth()->user()->hasRole('teacher')) {
                abort_unless($classSubject->teacher_id === $teacherId, 403, 'You can only manage quizzes for your assigned class subjects.');
            }

            $wasPublished = $quiz?->status === 'published';
            $savedQuiz = Quiz::updateOrCreate(['id' => $quiz?->id], [
                'class_subject_id' => $classSubject->id,
                'topic_id' => $topic?->id,
                'lesson_id' => $lesson?->id,
                'teacher_id' => $teacherId,
                'title' => $data['title'],
                'instructions' => filled($data['instructions']) ? app(ContentSanitizer::class)->clean($data['instructions']) : null,
                'time_limit_minutes' => filled($data['timeLimitMinutes']) ? $data['timeLimitMinutes'] : null,
                'pass_mark' => filled($data['passMark']) ? $data['passMark'] : null,
                'max_attempts' => $data['maxAttempts'],
                'randomize_questions' => $data['randomizeQuestions'],
                'opens_at' => filled($data['opensAt']) ? Carbon::parse($data['opensAt']) : null,
                'closes_at' => filled($data['closesAt']) ? Carbon::parse($data['closesAt']) : null,
                'status' => $data['status'],
            ]);

            if ($savedQuiz->status === 'published' && ! $wasPublished) {
                LmsNotifier::send(LmsNotifier::classAudience($classSubject->schoolClass), 'New quiz available', $savedQuiz->title.' is now available for your class.', null, 'quiz');
            }

            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title($quiz ? 'Quiz updated' : 'Quiz created')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $e) {
            LivewireAlert::title('Check the form')->error()->asToast()->position('top-end')->show();
            throw $e;
        } catch (Throwable $e) {
            report($e);
            LivewireAlert::title('Unable to save quiz')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(Quiz $quiz): void
    {
        $this->authorize('delete', $quiz);
        $this->deletingId = $quiz->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $quiz = Quiz::findOrFail($this->deletingId);
        $this->authorize('delete', $quiz);
        try {
            $quiz->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            LivewireAlert::title('Quiz archived')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $e) {
            report($e);
            LivewireAlert::title('Unable to archive quiz')->error()->asToast()->position('top-end')->show();
        }
    }

    public function closeModals(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function render()
    {
        $schoolId = School::query()->value('id');
        $teacherId = auth()->user()->hasRole('teacher') ? auth()->user()->teacher?->id : null;
        $classSubjects = ClassSubject::with(['schoolClass', 'subject'])
            ->whereHas('schoolClass.academicYear', fn ($q) => $q->where('school_id', $schoolId))
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))->get();
        $topics = Topic::whereIn('class_subject_id', $classSubjects->pluck('id'))->orderBy('sequence')->get();

        return view('livewire.lms.quizzes.index', [
            'quizzes' => Quiz::with(['classSubject.schoolClass', 'classSubject.subject', 'teacher'])
                ->when($classSubjects->isNotEmpty(), fn ($q) => $q->whereIn('class_subject_id', $classSubjects->pluck('id')))
                ->when($classSubjects->isEmpty(), fn ($q) => $q->whereRaw('1=0'))->withCount('quizQuestions')->latest()->get(),
            'classSubjects' => $classSubjects,
            'topics' => $topics,
            'lessons' => Lesson::whereIn('topic_id', $topics->pluck('id'))->orderBy('sequence')->get(),
            'teachers' => Teacher::where('school_id', $schoolId)->where('status', 'active')->orderBy('last_name')->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'classSubjectId', 'topicId', 'lessonId', 'teacherId', 'title', 'instructions', 'timeLimitMinutes', 'passMark', 'maxAttempts', 'randomizeQuestions', 'opensAt', 'closesAt', 'status']);
        $this->maxAttempts = '1';
        $this->status = 'draft';
        $this->resetValidation();
    }
}
