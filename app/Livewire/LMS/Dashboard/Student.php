<?php

namespace App\Livewire\LMS\Dashboard;

use App\Models\{Announcement, Assignment, AssessmentScore, ClassSubject, LessonProgress, Quiz};
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Student extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('student') && auth()->user()->student, 403);
    }

    public function render()
    {
        $student = auth()->user()->student;
        $classIds = $student->enrollments()->where('status', 'active')->pluck('school_class_id');
        $classSubjectIds = ClassSubject::whereIn('school_class_id', $classIds)->pluck('id');
        $assignments = Assignment::whereIn('class_subject_id', $classSubjectIds)->where('status', 'published');
        $quizzes = Quiz::whereIn('class_subject_id', $classSubjectIds)->where('status', 'published');
        $currentClass = $student->enrollments()->with('schoolClass')->where('status', 'active')->latest()->first()?->schoolClass;

        return view('livewire.lms.dashboard.student', [
            'metrics' => [
                'Current class' => $currentClass?->name ?? '—',
                'Subjects' => $classSubjectIds->count(),
                'Lessons completed' => LessonProgress::where('student_id', $student->id)->count(),
                'Upcoming assignments' => (clone $assignments)->where('due_at', '>=', now())->count(),
                'Pending assignments' => (clone $assignments)->whereDoesntHave('submissions', fn ($query) => $query->where('student_id', $student->id))->where('due_at', '>=', now())->count(),
                'Upcoming quizzes' => (clone $quizzes)->where(function ($query) {
                    $query->whereNull('opens_at')->orWhere('opens_at', '>=', now());
                })->count(),
                'Unread notifications' => auth()->user()->unreadNotifications()->count(),
            ],
            'recentResults' => AssessmentScore::with(['assessment.classSubject.subject'])->where('student_id', $student->id)->latest()->limit(5)->get(),
            'announcements' => Announcement::where('school_id', $student->school_id)->whereNotNull('published_at')->where('published_at', '<=', now())->where(fn ($q) => $q->where('audience', 'school')->orWhere(fn ($class) => $class->where('audience', 'class')->whereIn('school_class_id', $classIds)))->latest('published_at')->limit(5)->get(),
        ]);
    }
}
