<?php

namespace App\Livewire\LMS\Dashboard;

use App\Models\{Announcement, Assignment, AssignmentSubmission, Assessment, ClassSubject, Quiz};
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Teacher extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('teacher') && auth()->user()->teacher, 403);
    }

    public function render()
    {
        $teacherId = auth()->user()->teacher->id;
        $assignments = Assignment::where('teacher_id', $teacherId);
        $quizzes = Quiz::where('teacher_id', $teacherId);
        $classSubjects = ClassSubject::with(['schoolClass', 'subject'])->where('teacher_id', $teacherId)->get();

        return view('livewire.lms.dashboard.teacher', [
            'metrics' => [
                'Assigned classes' => $classSubjects->pluck('school_class_id')->unique()->count(),
                'Assigned subjects' => $classSubjects->pluck('subject_id')->unique()->count(),
                'Assignments' => (clone $assignments)->count(),
                'Pending grading' => AssignmentSubmission::whereHas('assignment', fn ($query) => $query->where('teacher_id', $teacherId))->where('status', 'submitted')->count(),
                'Upcoming quizzes' => (clone $quizzes)->where('status', 'published')->where(function ($query) {
                    $query->whereNull('opens_at')->orWhere('opens_at', '>=', now());
                })->count(),
                'Assessments' => Assessment::where('teacher_id', $teacherId)->count(),
            ],
            'recentSubmissions' => AssignmentSubmission::with(['student', 'assignment'])->whereHas('assignment', fn ($query) => $query->where('teacher_id', $teacherId))->where('status', 'submitted')->latest('submitted_at')->limit(5)->get(),
            'announcements' => Announcement::where('created_by', auth()->id())->latest()->limit(5)->get(),
        ]);
    }
}
