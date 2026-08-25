<?php

namespace App\Livewire\LMS\QuizQuestions;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Builder;
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

    public Quiz $quiz;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;

    public ?int $deletingId = null;

    public string $search = '';
    public string $filterType = '';
    public string $bankSearch = '';
    public string $bankType = '';

    public string $questionId = '';
    public string $sequence = '0';

    public function mount(Quiz $quiz): void
    {
        $this->authorize('update', $quiz);
        $this->quiz = $quiz;
    }

    public function create(): void
    {
        $this->authorize('update', $this->quiz);
        $this->resetForm();
        $this->sequence = (string) (($this->quiz->quizQuestions()->max('sequence') ?? -1) + 1);
        $this->showFormModal = true;
    }

    public function updatedSearch(): void
    {
        $this->resetPage('linkedQuestionsPage');
    }

    public function updatedFilterType(): void
    {
        $this->resetPage('linkedQuestionsPage');
    }

    public function updatedBankSearch(): void
    {
        $this->resetPage('bankQuestionsPage');
    }

    public function updatedBankType(): void
    {
        $this->resetPage('bankQuestionsPage');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterType']);
        $this->resetPage('linkedQuestionsPage');
    }

    public function clearBankFilters(): void
    {
        $this->reset(['bankSearch', 'bankType']);
        $this->resetPage('bankQuestionsPage');
    }

    public function save(): void
    {
        $this->authorize('update', $this->quiz);

        try {
            $data = $this->validate([
                'questionId' => ['required', 'integer', Rule::exists('questions', 'id')],
                'sequence' => ['required', 'integer', 'min:0', 'max:9999'],
            ]);

            $question = $this->scopedQuestions()->findOrFail($data['questionId']);

            if ($this->quiz->quizQuestions()->where('question_id', $question->id)->exists()) {
                $this->addError('questionId', 'This question is already in the quiz.');

                return;
            }

            QuizQuestion::create([
                'quiz_id' => $this->quiz->id,
                'question_id' => $question->id,
                'sequence' => $data['sequence'],
            ]);

            $this->showFormModal = false;
            $this->resetForm();
            $this->resetPage('linkedQuestionsPage');
            LivewireAlert::title('Question added to quiz')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the selection')->error()->asToast()->position('top-end')->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to add question')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(QuizQuestion $quizQuestion): void
    {
        abort_unless($quizQuestion->quiz_id === $this->quiz->id, 404);
        $this->authorize('update', $this->quiz);

        $this->deletingId = $quizQuestion->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $quizQuestion = QuizQuestion::where('quiz_id', $this->quiz->id)->findOrFail($this->deletingId);
        $this->authorize('update', $this->quiz);

        try {
            $quizQuestion->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage('linkedQuestionsPage');
            LivewireAlert::title('Question removed from quiz')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to remove question')->error()->asToast()->position('top-end')->show();
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
        $this->quiz->loadMissing([
            'classSubject.schoolClass.academicYear',
            'classSubject.subject',
            'topic',
            'lesson',
        ]);
        $search = trim($this->search);
        $bankSearch = trim($this->bankSearch);

        return view('livewire.lms.quiz-questions.index', [
            'quizQuestions' => $this->quiz->quizQuestions()
                ->with([
                    'question' => fn ($query) => $query->select(['id', 'subject_id', 'topic_id', 'lesson_id', 'type', 'prompt', 'max_score']),
                    'question.topic:id,title',
                    'question.lesson:id,title',
                ])
                ->whereHas('question', function (Builder $questions) use ($search): void {
                    if ($search === '') {
                        return;
                    }

                    $questions->where(function (Builder $questions) use ($search): void {
                        $questions->where('prompt', 'like', "%{$search}%")
                            ->orWhere('type', 'like', "%{$search}%")
                            ->orWhereHas('topic', fn (Builder $topics) => $topics->where('title', 'like', "%{$search}%"))
                            ->orWhereHas('lesson', fn (Builder $lessons) => $lessons->where('title', 'like', "%{$search}%"));
                    });
                })
                ->when(filled($this->filterType), fn (Builder $query) => $query->whereHas('question', fn (Builder $questions) => $questions->where('type', $this->filterType)))
                ->orderBy('sequence')
                ->orderBy('id')
                ->paginate(15, ['*'], 'linkedQuestionsPage'),
            'availableQuestions' => $this->availableQuestions()
                ->select(['id', 'subject_id', 'topic_id', 'lesson_id', 'type', 'prompt', 'max_score'])
                ->when($bankSearch !== '', function (Builder $questions) use ($bankSearch): void {
                    $questions->where(function (Builder $questions) use ($bankSearch): void {
                        $questions->where('prompt', 'like', "%{$bankSearch}%")
                            ->orWhere('type', 'like', "%{$bankSearch}%")
                            ->orWhereHas('topic', fn (Builder $topics) => $topics->where('title', 'like', "%{$bankSearch}%"))
                            ->orWhereHas('lesson', fn (Builder $lessons) => $lessons->where('title', 'like', "%{$bankSearch}%"));
                    });
                })
                ->when(filled($this->bankType), fn (Builder $query) => $query->where('type', $this->bankType))
                ->with(['topic:id,title', 'lesson:id,title'])
                ->latest('id')
                ->paginate(10, ['*'], 'bankQuestionsPage'),
        ]);
    }

    private function scopedQuestions(): Builder
    {
        $schoolId = (int) $this->quiz->classSubject->schoolClass->academicYear->school_id;
        $query = Question::query()
            ->where('school_id', $schoolId)
            ->where('subject_id', $this->quiz->classSubject->subject_id);

        if ($this->quiz->lesson_id) {
            $query->where('lesson_id', $this->quiz->lesson_id);
        } elseif ($this->quiz->topic_id) {
            $query->where('topic_id', $this->quiz->topic_id);
        }

        return $query;
    }

    private function availableQuestions(): Builder
    {
        return $this->scopedQuestions()
            ->whereNotIn('id', $this->quiz->quizQuestions()->select('question_id'));
    }

    private function resetForm(): void
    {
        $this->reset(['questionId', 'sequence', 'deletingId', 'bankSearch', 'bankType']);
        $this->resetPage('bankQuestionsPage');
        $this->resetValidation();
    }
}
