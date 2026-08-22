<?php

namespace App\Livewire\LMS\Assignments\Teacher;

use App\Models\{Assignment, SubmissionAttachment};
use App\Support\LmsNotifier;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.lms')]
class Grade extends Component
{
    use WithPagination;

    public Assignment $assignment;
    public array $scores = [];
    public array $feedback = [];
    public bool $canGrade = false;

    public function mount(Assignment $assignment): void
    {
        $this->authorize('view', $assignment);

        $user = auth()->user();
        abort_unless($user->hasAnyRole(['super_admin', 'school_admin', 'teacher']), 403);

        if ($user->hasRole('teacher')) {
            abort_unless($assignment->teacher_id === $user->teacher?->id, 403);
            $this->canGrade = true;
        }

        $this->assignment = $assignment;
        $this->loadScores();
    }

    public function saveGrade(int $submissionId): void
    {
        abort_unless($this->canGrade && auth()->user()->hasRole('teacher') && $this->assignment->teacher_id === auth()->user()->teacher?->id, 403);

        $submission = $this->assignment->submissions()->with('student.user', 'student.parents.user')->findOrFail($submissionId);
        abort_unless(in_array($submission->status, ['submitted', 'graded'], true), 422, 'Only submitted work can be graded.');
        $this->validate(['scores.'.$submissionId => ['nullable', 'numeric', 'min:0', 'max:'.$this->assignment->max_score], 'feedback.'.$submissionId => ['nullable', 'string', 'max:5000']]);
        $oldValues = ['score' => $submission->score, 'feedback' => $submission->feedback, 'status' => $submission->status];
        $submission->update(['score' => $this->scores[$submissionId] ?? null, 'feedback' => $this->feedback[$submissionId] ?? null, 'status' => 'graded', 'graded_by' => auth()->id(), 'graded_at' => now()]);
        app(AuditLogger::class)->record('assignment_grade.updated', $submission, $oldValues, ['score' => $submission->score, 'feedback' => $submission->feedback, 'status' => $submission->status], (int) $this->assignment->classSubject->schoolClass->academicYear->school_id);
        $recipients = collect([$submission->student?->user])->merge($submission->student?->parents?->pluck('user') ?? collect())->filter()->unique('id');
        LmsNotifier::send($recipients, 'Assignment graded', $this->assignment->title.' has been graded.', null, 'assignment');
        LivewireAlert::title('Submission graded')->success()->asToast()->position('top-end')->show();
    }

    public function downloadAttachment(int $attachmentId)
    {
        $this->authorize('view', $this->assignment);

        $attachment = SubmissionAttachment::whereKey($attachmentId)->whereHas('submission', fn ($query) => $query->where('assignment_id', $this->assignment->id))->firstOrFail();
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->name);
    }

    public function render()
    {
        return view('livewire.lms.assignments.teacher.grade', ['submissions' => $this->assignment->submissions()->with(['student', 'attachments'])->latest('submitted_at')->paginate(15)]);
    }

    private function loadScores(): void
    {
        foreach ($this->assignment->submissions as $submission) { $this->scores[$submission->id] = $submission->score ?? ''; $this->feedback[$submission->id] = $submission->feedback ?? ''; }
    }
}
