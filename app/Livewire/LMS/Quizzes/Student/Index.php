<?php

namespace App\Livewire\LMS\Quizzes\Student;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Student;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use WithPagination;

    public Student $student;

    public function mount(): void
    {
        $this->student = auth()->user()->student;
        abort_unless(auth()->user()->hasRole('student') && $this->student, 403);
    }

    public function start(int $quizId): void
    {
        $quiz = $this->quizzes()->findOrFail($quizId);

        if ($quiz->hasNotOpened()) {
            LivewireAlert::title('Quiz not open yet')
                ->text('This quiz opens '.$quiz->opens_at->format('d M Y \a\t g:i A').'.')
                ->warning()->asToast()->position('top-end')->show();

            return;
        }

        if ($quiz->hasClosed()) {
            LivewireAlert::title('Quiz window closed')
                ->text('This quiz closed '.$quiz->closes_at->format('d M Y \a\t g:i A').'.')
                ->warning()->asToast()->position('top-end')->show();

            return;
        }

        if ($active = $quiz->attempts()->where('student_id', $this->student->id)->where('status', 'in_progress')->latest('attempt_number')->first()) {
            $this->redirectRoute('lms.quizzes.student.attempt', $active);

            return;
        }

        $count = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $this->student->id)->count();
        if ($count >= $quiz->max_attempts) {
            LivewireAlert::title('No attempts remaining')
                ->text('You have used all available attempts for this quiz.')
                ->warning()->asToast()->position('top-end')->show();

            return;
        }

        try {
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $this->student->id,
                'attempt_number' => $count + 1,
                'started_at' => now(),
                'status' => 'in_progress',
            ]);
            LivewireAlert::title('Quiz attempt started')->success()->asToast()->position('top-end')->show();
            $this->redirectRoute('lms.quizzes.student.attempt', $attempt);
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to start quiz')
                ->text('Please refresh the page and try again.')
                ->error()->asToast()->position('top-end')->show();
        }
    }

    public function render()
    {
        $quizzes = $this->quizzes()
            ->with([
                'classSubject.subject',
                'attempts' => fn ($query) => $query
                    ->where('student_id', $this->student->id)
                    ->latest('attempt_number'),
            ])
            ->paginate(15)
            ->through(function ($quiz) {
                $quiz->attempt = $quiz->attempts->first();
                $quiz->attemptCount = $quiz->attempts->count();

                return $quiz;
            });

        return view('livewire.lms.quizzes.student.index', compact('quizzes'));
    }

    private function quizzes()
    {
        return Quiz::where('status', 'published')
            ->whereHas('classSubject.schoolClass.academicYear', fn ($query) => $query->where('school_id', $this->student->school_id))
            ->whereHas('classSubject.schoolClass.enrollments', fn ($q) => $q->where('student_id', $this->student->id)->where('status', 'active'));
    }
}
