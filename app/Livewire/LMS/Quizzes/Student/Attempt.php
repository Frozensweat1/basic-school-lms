<?php

namespace App\Livewire\LMS\Quizzes\Student;

use App\Models\{QuizAnswer, QuizAttempt};
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.lms')]
class Attempt extends Component
{
    public QuizAttempt $attempt;
    public array $answers = [];

    public function mount(QuizAttempt $attempt): void
    {
        abort_unless(auth()->user()->hasRole('student') && auth()->user()->student?->id === $attempt->student_id, 403);
        $this->attempt = $attempt->load('quiz.quizQuestions.question.options', 'answers');
        $this->attempt->quiz->quizQuestions->each(function ($quizQuestion): void {
            $quizQuestion->question?->makeHidden(['grading_key']);
            $quizQuestion->question?->options?->each->makeHidden('is_correct');
        });
        $this->ensureAttemptOpen();
        foreach ($this->attempt->answers as $answer) $this->answers[$answer->question_id] = $answer->answer[0] ?? '';
    }

    public function save(): void
    {
        try {
            $this->ensureAttemptOpen();
            $questionIds = $this->attempt->quiz->quizQuestions->pluck('question_id')->map(fn ($id) => (string) $id)->all();
            $this->validate(['answers' => ['array'], 'answers.*' => ['nullable', 'string', 'max:10000']]);
            foreach (array_keys($this->answers) as $questionId) {
                abort_unless(in_array((string) $questionId, $questionIds, true), 422, 'Invalid quiz question.');
            }
            $totalScore = 0;
            $requiresManualGrading = false;
            foreach ($questionIds as $questionId) {
                $answer = $this->answers[$questionId] ?? '';
                $question = $this->attempt->quiz->quizQuestions->firstWhere('question_id', (int) $questionId)?->question;
                $normalizedAnswer = trim((string) $answer);
                $score = null;
                if ($question?->type === 'essay') {
                    $requiresManualGrading = true;
                } elseif ($question?->grading_key !== null) {
                    $correctAnswer = trim((string) ($question->grading_key['answer'] ?? ''));
                    $score = $correctAnswer !== '' && mb_strtolower($normalizedAnswer) === mb_strtolower($correctAnswer)
                        ? (float) $question->max_score
                        : 0;
                    $totalScore += $score;
                }
                QuizAnswer::updateOrCreate(
                    ['quiz_attempt_id' => $this->attempt->id, 'question_id' => (int) $questionId],
                    ['answer' => [$normalizedAnswer], 'score' => $score, 'graded_at' => $score !== null ? now() : null]
                );
            }
            $this->attempt->update(['score' => $totalScore, 'status' => $requiresManualGrading ? 'submitted' : 'completed', 'submitted_at' => now()]);
            LivewireAlert::title('Quiz submitted')->success()->asToast()->position('top-end')->show();
            $this->redirectRoute('lms.quizzes.student.index');
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check your answers')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to submit quiz')->error()->asToast()->position('top-end')->show();
        }
    }

    public function render()
    {
        $questions = $this->attempt->quiz->quizQuestions->sortBy(function ($quizQuestion): int {
            return $this->attempt->quiz->randomize_questions
                ? crc32($this->attempt->id.'-'.$quizQuestion->question_id)
                : $quizQuestion->sequence;
        });

        return view('livewire.lms.quizzes.student.attempt', compact('questions'));
    }

    private function ensureAttemptOpen(): void
    {
        abort_unless($this->attempt->status === 'in_progress', 422, 'This quiz attempt has already been submitted.');
        $quiz = $this->attempt->quiz;
        abort_unless($quiz->status === 'published', 422, 'This quiz is no longer available.');
        abort_unless(! $quiz->opens_at || $quiz->opens_at->isPast(), 422, 'This quiz has not opened yet.');
        abort_unless(! $quiz->closes_at || $quiz->closes_at->isFuture(), 422, 'The quiz window has closed.');
        if ($quiz->time_limit_minutes && $this->attempt->started_at?->addMinutes($quiz->time_limit_minutes)->isPast()) {
            $this->attempt->update(['status' => 'submitted', 'submitted_at' => now()]);
            abort(422, 'The time limit for this quiz has expired.');
        }
    }
}
