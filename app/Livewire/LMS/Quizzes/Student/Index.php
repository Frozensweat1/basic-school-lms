<?php

namespace App\Livewire\LMS\Quizzes\Student;

use App\Models\{Quiz, QuizAttempt, Student};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Index extends Component
{
    public Student $student;

    public function mount(): void
    {
        $this->student = auth()->user()->student;
        abort_unless(auth()->user()->hasRole('student') && $this->student, 403);
    }

    public function start(int $quizId): void
    {
        $quiz = $this->quizzes()->findOrFail($quizId);
        if ($active = $quiz->attempts()->where('student_id', $this->student->id)->where('status', 'in_progress')->latest('attempt_number')->first()) {
            $this->redirectRoute('lms.quizzes.student.attempt', $active);
            return;
        }
        abort_unless(! $quiz->opens_at || $quiz->opens_at->isPast(), 422, 'This quiz has not opened yet.');
        abort_unless(! $quiz->closes_at || $quiz->closes_at->isFuture(), 422, 'This quiz is closed.');
        $count = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $this->student->id)->count();
        abort_unless($count < $quiz->max_attempts, 422, 'You have used all attempts for this quiz.');
        $attempt = QuizAttempt::create(['quiz_id' => $quiz->id, 'student_id' => $this->student->id, 'attempt_number' => $count + 1, 'started_at' => now(), 'status' => 'in_progress']);
        LivewireAlert::title('Quiz attempt started')->success()->asToast()->position('top-end')->show();
        $this->redirectRoute('lms.quizzes.student.attempt', $attempt);
    }

    public function render()
    {
        $quizzes = $this->quizzes()->with('classSubject.subject')->get()->map(function ($quiz) {
            $quiz->attempt = $quiz->attempts()->where('student_id', $this->student->id)->latest('attempt_number')->first();
            $quiz->attemptCount = $quiz->attempts()->where('student_id', $this->student->id)->count();
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
