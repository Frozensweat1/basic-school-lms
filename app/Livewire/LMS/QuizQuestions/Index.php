<?php

namespace App\Livewire\LMS\QuizQuestions;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;

    public Quiz $quiz;
    public bool $showFormModal = false, $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $questionId = '', $sequence = '0';

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

    public function save(): void
    {
        $this->authorize('update', $this->quiz);

        try {
            $data = $this->validate([
                'questionId' => ['required', 'integer', Rule::exists('questions', 'id')],
                'sequence' => ['required', 'integer', 'min:0', 'max:9999'],
            ]);
            $questionBelongsToSchool = Question::whereKey($data['questionId'])
                ->where('school_id', $this->quiz->classSubject->schoolClass->academicYear->school_id)
                ->exists();
            abort_unless($questionBelongsToSchool, 422, 'Choose a question from this school.');

            if ($this->quiz->quizQuestions()->where('question_id', $data['questionId'])->exists()) {
                $this->addError('questionId', 'This question is already in the quiz.');
                return;
            }

            QuizQuestion::create([
                'quiz_id' => $this->quiz->id,
                'question_id' => $data['questionId'],
                'sequence' => $data['sequence'],
            ]);

            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title('Question added to quiz')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the form')->error()->asToast()->position('top-end')->show();
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
        $quizQuestion = QuizQuestion::findOrFail($this->deletingId);
        abort_unless($quizQuestion->quiz_id === $this->quiz->id, 404);
        $this->authorize('update', $this->quiz);

        try {
            $quizQuestion->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
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

    private function resetForm(): void
    {
        $this->reset(['questionId', 'sequence', 'deletingId']);
        $this->resetValidation();
    }

    public function render()
    {
        $schoolId = $this->quiz->classSubject->schoolClass->academicYear->school_id;

        return view('livewire.lms.quiz-questions.index', [
            'quizQuestions' => $this->quiz->quizQuestions()->with('question.options')->orderBy('sequence')->get(),
            'questions' => Question::where('school_id', $schoolId)->orderByDesc('id')->get(),
        ]);
    }
}
