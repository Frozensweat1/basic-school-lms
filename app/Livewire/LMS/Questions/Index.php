<?php

namespace App\Livewire\LMS\Questions;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\School;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;

    public ?int $editingId = null;
    public ?int $deletingId = null;

    public string $search = '';
    public string $filterSubjectId = '';
    public string $filterTopicId = '';
    public string $filterLessonId = '';
    public string $filterType = '';

    public string $subjectId = '';
    public string $topicId = '';
    public string $lessonId = '';
    public string $type = 'multiple_choice';
    public string $prompt = '';
    public string $maxScore = '1';
    public string $optionsText = '';
    public string $correctAnswer = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Question::class);
    }

    public function create(): void
    {
        $this->authorize('create', Question::class);
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSubjectId(): void
    {
        $this->filterTopicId = '';
        $this->filterLessonId = '';
        $this->resetPage();
    }

    public function updatedFilterTopicId(): void
    {
        $this->filterLessonId = '';
        $this->resetPage();
    }

    public function updatedFilterLessonId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedSubjectId(): void
    {
        $this->topicId = '';
        $this->lessonId = '';
    }

    public function updatedTopicId(): void
    {
        $this->lessonId = '';
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterSubjectId', 'filterTopicId', 'filterLessonId', 'filterType']);
        $this->resetPage();
    }

    public function edit(Question $question): void
    {
        $question = $this->scopedQuestions()->with('options')->findOrFail($question->id);
        $this->authorize('update', $question);

        $this->editingId = $question->id;
        $this->subjectId = (string) $question->subject_id;
        $this->topicId = (string) ($question->topic_id ?? '');
        $this->lessonId = (string) ($question->lesson_id ?? '');
        $this->type = $question->type;
        $this->prompt = $question->prompt;
        $this->maxScore = (string) $question->max_score;
        $this->optionsText = $question->options->pluck('label')->implode(PHP_EOL);
        $this->correctAnswer = (string) ($question->grading_key['answer'] ?? '');
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $question = $this->editingId ? $this->scopedQuestions()->findOrFail($this->editingId) : null;
        $this->authorize($question ? 'update' : 'create', $question ?? Question::class);

        try {
            $data = $this->validate([
                'subjectId' => ['required', 'integer', Rule::exists('subjects', 'id')],
                'topicId' => ['required', 'integer', Rule::exists('topics', 'id')],
                'lessonId' => ['required', 'integer', Rule::exists('lessons', 'id')],
                'type' => ['required', Rule::in(['multiple_choice', 'true_false', 'short_answer', 'essay'])],
                'prompt' => ['required', 'string', 'max:10000'],
                'maxScore' => ['required', 'numeric', 'min:0.01', 'max:999999'],
                'optionsText' => ['nullable', 'string', 'max:10000'],
                'correctAnswer' => ['nullable', 'string', 'max:5000'],
            ]);

            $subject = Subject::query()
                ->whereKey($data['subjectId'])
                ->where('school_id', $this->schoolId())
                ->firstOrFail();
            $topic = $this->subjectTopics($subject->id)->findOrFail($data['topicId']);
            $lesson = $this->topicLessons($topic->id)->findOrFail($data['lessonId']);

            $options = collect(preg_split('/\r\n|\r|\n/', $data['optionsText'] ?? ''))
                ->map(fn (string $item) => trim($item))
                ->filter()
                ->values();

            if ($data['type'] === 'true_false') {
                $options = collect(['True', 'False']);
            }

            if ($data['type'] === 'multiple_choice' && $options->count() < 2) {
                $this->addError('optionsText', 'Add at least two options.');

                return;
            }

            if ($data['type'] !== 'essay' && blank($data['correctAnswer'])) {
                $this->addError('correctAnswer', 'Provide the correct answer.');

                return;
            }

            if ($data['type'] === 'multiple_choice' && ! $options->contains(fn (string $option) => mb_strtolower($option) === mb_strtolower(trim($data['correctAnswer'])))) {
                $this->addError('correctAnswer', 'The correct answer must match one of the options.');

                return;
            }

            if ($data['type'] === 'true_false' && ! in_array(mb_strtolower(trim($data['correctAnswer'])), ['true', 'false'], true)) {
                $this->addError('correctAnswer', 'Use True or False as the correct answer.');

                return;
            }

            $record = Question::updateOrCreate(
                ['id' => $question?->id],
                [
                    'school_id' => $this->schoolId(),
                    'subject_id' => $subject->id,
                    'topic_id' => $topic->id,
                    'lesson_id' => $lesson->id,
                    'created_by' => $question?->created_by ?? auth()->id(),
                    'type' => $data['type'],
                    'prompt' => strip_tags($data['prompt'], '<p><br><strong><em><u><ol><ul><li>'),
                    'max_score' => $data['maxScore'],
                    'grading_key' => $data['type'] === 'essay' ? null : ['answer' => trim($data['correctAnswer'])],
                ],
            );

            $record->options()->delete();
            foreach ($options as $sequence => $label) {
                $record->options()->create([
                    'label' => $label,
                    'is_correct' => mb_strtolower($label) === mb_strtolower(trim($data['correctAnswer'])),
                    'sequence' => $sequence,
                ]);
            }

            $this->showFormModal = false;
            $this->resetForm();
            $this->resetPage();
            LivewireAlert::title($question ? 'Question updated' : 'Question added')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the question')->error()->asToast()->position('top-end')->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save question')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(Question $question): void
    {
        $question = $this->scopedQuestions()->findOrFail($question->id);
        $this->authorize('delete', $question);
        $this->deletingId = $question->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $question = $this->scopedQuestions()->findOrFail($this->deletingId);
        $this->authorize('delete', $question);

        try {
            $question->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Question deleted')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete question')->error()->asToast()->position('top-end')->show();
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
        $subjects = Subject::query()
            ->where('school_id', $this->schoolId())
            ->orderBy('name')
            ->get();
        $filterTopics = filled($this->filterSubjectId)
            ? $this->subjectTopics((int) $this->filterSubjectId)->get()
            : collect();
        $filterLessons = filled($this->filterTopicId)
            ? $this->topicLessons((int) $this->filterTopicId)->get()
            : collect();
        $formTopics = filled($this->subjectId)
            ? $this->subjectTopics((int) $this->subjectId)->get()
            : collect();
        $formLessons = filled($this->topicId)
            ? $this->topicLessons((int) $this->topicId)->get()
            : collect();
        $search = trim($this->search);

        return view('livewire.lms.questions.index', [
            'questions' => $this->scopedQuestions()
                ->with(['subject', 'topic.classSubject.schoolClass', 'lesson.topic', 'options'])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($questions) use ($search): void {
                        $questions->where('prompt', 'like', "%{$search}%")
                            ->orWhere('type', 'like', "%{$search}%")
                            ->orWhereHas('subject', fn ($subjects) => $subjects->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('topic', fn ($topics) => $topics->where('title', 'like', "%{$search}%"))
                            ->orWhereHas('lesson', fn ($lessons) => $lessons->where('title', 'like', "%{$search}%"));
                    });
                })
                ->when(filled($this->filterSubjectId), fn ($query) => $query->where('subject_id', $this->filterSubjectId))
                ->when(filled($this->filterTopicId), fn ($query) => $query->where('topic_id', $this->filterTopicId))
                ->when(filled($this->filterLessonId), fn ($query) => $query->where('lesson_id', $this->filterLessonId))
                ->when(filled($this->filterType), fn ($query) => $query->where('type', $this->filterType))
                ->latest()
                ->paginate(15),
            'subjects' => $subjects,
            'filterTopics' => $filterTopics,
            'filterLessons' => $filterLessons,
            'formTopics' => $formTopics,
            'formLessons' => $formLessons,
        ]);
    }

    private function schoolId(): int
    {
        return (int) School::query()->value('id');
    }

    private function scopedQuestions()
    {
        return Question::query()->where('school_id', $this->schoolId());
    }

    private function subjectTopics(?int $subjectId)
    {
        return Topic::query()
            ->with(['classSubject.schoolClass', 'classSubject.subject'])
            ->whereHas('classSubject', function ($classSubjects) use ($subjectId): void {
                $classSubjects
                    ->where('subject_id', $subjectId)
                    ->whereHas('schoolClass.academicYear', fn ($years) => $years->where('school_id', $this->schoolId()));
            })
            ->orderBy('sequence')
            ->orderBy('title');
    }

    private function topicLessons(?int $topicId)
    {
        return Lesson::query()
            ->where('topic_id', $topicId)
            ->whereHas('topic.classSubject.schoolClass.academicYear', fn ($years) => $years->where('school_id', $this->schoolId()))
            ->orderBy('sequence')
            ->orderBy('title');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'subjectId', 'topicId', 'lessonId', 'type', 'prompt', 'maxScore', 'optionsText', 'correctAnswer']);
        $this->type = 'multiple_choice';
        $this->maxScore = '1';
        $this->resetValidation();
    }
}
