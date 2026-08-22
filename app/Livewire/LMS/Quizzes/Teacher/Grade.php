<?php

namespace App\Livewire\LMS\Quizzes\Teacher;

use App\Models\{Quiz, QuizAttempt};
use App\Support\LmsNotifier;
use App\Support\AuditLogger;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.lms')]
class Grade extends Component
{
    use WithPagination;

    public Quiz $quiz;
    public array $scores = [];

    public function mount(Quiz $quiz): void
    {
        $this->authorize('update', $quiz);
        abort_unless(auth()->user()->hasRole('teacher') && $quiz->teacher_id === auth()->user()->teacher?->id, 403);
        $this->quiz = $quiz;
        $this->scores = $quiz->attempts()->pluck('score', 'id')->toArray();
    }

    public function saveGrade(int $attemptId): void
    {
        try {
            $attempt = $this->quiz->attempts()->with('student.user', 'student.parents.user')->findOrFail($attemptId);
            abort_unless($attempt->status === 'submitted' || $attempt->status === 'completed', 422, 'Only submitted attempts can be graded.');
            $this->validate(['scores.'.$attemptId => ['required', 'numeric', 'min:0', 'max:'.$this->quiz->max_score]]);
            $oldValues = ['score' => $attempt->score, 'status' => $attempt->status];
            $attempt->update(['score' => $this->scores[$attemptId], 'status' => 'completed', 'submitted_at' => $attempt->submitted_at ?? now()]);
            app(AuditLogger::class)->record('quiz_grade.updated', $attempt, $oldValues, ['score' => $attempt->score, 'status' => $attempt->status], (int) $this->quiz->classSubject->schoolClass->academicYear->school_id);
            $recipients = collect([$attempt->student?->user])->merge($attempt->student?->parents?->pluck('user') ?? collect())->filter()->unique('id');
            LmsNotifier::send($recipients, 'Quiz graded', $this->quiz->title.' has been graded. Score: '.$this->scores[$attemptId].'/'.$this->quiz->max_score.'.', null, 'quiz');
            LivewireAlert::title('Quiz score saved')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) { LivewireAlert::title('Check the score')->error()->asToast()->position('top-end')->show(); throw $exception; } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to save quiz score')->error()->asToast()->position('top-end')->show(); }
    }

    public function render()
    {
        return view('livewire.lms.quizzes.teacher.grade', ['attempts' => $this->quiz->attempts()->with('student')->whereIn('status', ['submitted', 'completed'])->latest('submitted_at')->paginate(15)]);
    }
}
