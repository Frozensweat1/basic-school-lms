<?php

namespace App\Livewire\LMS\Assignments\Teacher;

use App\Models\{Assignment, SubmissionAttachment};
use App\Support\LmsNotifier;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Grade extends Component
{
    public Assignment $assignment;
    public array $scores = [];
    public array $feedback = [];

    public function mount(Assignment $assignment): void
    {
        $this->authorize('update', $assignment);
        abort_unless(auth()->user()->hasRole('teacher') && $assignment->teacher_id === auth()->user()->teacher?->id, 403);
        $this->assignment = $assignment;
        $this->loadScores();
    }

    public function saveGrade(int $submissionId): void
    {
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
        $attachment = SubmissionAttachment::whereKey($attachmentId)->whereHas('submission', fn ($query) => $query->where('assignment_id', $this->assignment->id))->firstOrFail();
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->name);
    }

    public function render()
    {
        return view('livewire.lms.assignments.teacher.grade', ['submissions' => $this->assignment->submissions()->with(['student', 'attachments'])->latest('submitted_at')->get()]);
    }

    private function loadScores(): void
    {
        foreach ($this->assignment->submissions as $submission) { $this->scores[$submission->id] = $submission->score ?? ''; $this->feedback[$submission->id] = $submission->feedback ?? ''; }
    }
}
