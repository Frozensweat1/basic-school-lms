<?php

namespace App\Livewire\LMS\Assignments\Student;

use App\Models\{Assignment, AssignmentSubmission, Student, SubmissionAttachment};
use App\Support\LmsNotifier;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use WithFileUploads;
    public Student $student;
    public array $submissionTexts = [];
    public array $submissionFiles = [];

    public function mount(): void { $this->student = auth()->user()->student; abort_unless(auth()->user()->hasRole('student') && $this->student, 403); }

    public function submit(int $assignmentId): void
    {
        $assignment = $this->assignments()->findOrFail($assignmentId);
        try {
            abort_unless(! $assignment->opens_at || $assignment->opens_at->isPast(), 422, 'This assignment has not opened yet.');
            abort_unless($assignment->allow_late_submission || ! $assignment->due_at->isPast(), 422, 'The assignment deadline has passed.');
            $existingSubmission = $assignment->submissions()->where('student_id', $this->student->id)->first();
            abort_unless(! $existingSubmission, 422, 'You have already submitted this assignment.');
            $data = $this->validate(['submissionTexts.'.$assignmentId => ['required', 'string', 'max:50000'], 'submissionFiles.'.$assignmentId => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png,zip']]);
            $submission = AssignmentSubmission::updateOrCreate(['assignment_id' => $assignment->id, 'student_id' => $this->student->id], ['submission_text' => $data['submissionTexts'][$assignmentId], 'status' => 'submitted', 'submitted_at' => now()]);
            if ($file = ($data['submissionFiles'][$assignmentId] ?? null)) {
                $path = $file->store('assignments/submissions/'.$this->student->id.'/'.$assignment->id, 'local');
                SubmissionAttachment::create(['assignment_submission_id' => $submission->id, 'name' => $file->getClientOriginalName(), 'disk' => 'local', 'path' => $path, 'size' => $file->getSize()]);
            }
            LmsNotifier::send(
                [$assignment->teacher?->user],
                'Assignment submitted',
                $this->student->first_name.' '.$this->student->last_name.' submitted '.$assignment->title.'.',
                route('lms.assignments.teacher.grade', $assignment),
                'assignment',
            );
            unset($this->submissionTexts[$assignmentId], $this->submissionFiles[$assignmentId]);
            LivewireAlert::title('Assignment submitted')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) { LivewireAlert::title('Check your submission')->error()->asToast()->position('top-end')->show(); throw $exception; } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to submit assignment')->error()->asToast()->position('top-end')->show(); }
    }

    public function render()
    {
        $assignments = $this->assignments()->with('classSubject.subject')->latest('due_at')->get()->map(function ($assignment) { $assignment->submission = $assignment->submissions()->with('attachments')->where('student_id', $this->student->id)->first(); return $assignment; });
        return view('livewire.lms.assignments.student.index', compact('assignments'));
    }

    public function downloadAttachment(int $attachmentId)
    {
        $attachment = SubmissionAttachment::whereKey($attachmentId)->whereHas('submission', fn ($query) => $query->where('student_id', $this->student->id))->firstOrFail();
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->name);
    }

    private function assignments()
    {
        return Assignment::where('status', 'published')
            ->whereHas('classSubject.schoolClass.academicYear', fn ($query) => $query->where('school_id', $this->student->school_id))
            ->whereHas('classSubject.schoolClass.enrollments', fn ($q) => $q->where('student_id', $this->student->id)->where('status', 'active'));
    }
}
