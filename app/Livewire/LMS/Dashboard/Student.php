<?php

namespace App\Livewire\LMS\Dashboard;

use App\Models\Announcement;
use App\Models\AssessmentScore;
use App\Models\Assignment;
use App\Models\ClassSubject;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Support\AttendanceSummary;
use App\Support\DashboardChartData;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Student extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('student') && auth()->user()->student, 403);
    }

    public function render(AttendanceSummary $attendanceSummary, DashboardChartData $charts)
    {
        $student = auth()->user()->student;
        $classIds = $student->enrollments()->where('status', 'active')->pluck('school_class_id');
        $classSubjectIds = ClassSubject::whereIn('school_class_id', $classIds)->pluck('id');
        $assignments = Assignment::whereIn('class_subject_id', $classSubjectIds)->where('status', 'published');
        $quizzes = Quiz::whereIn('class_subject_id', $classSubjectIds)->where('status', 'published');
        $currentClass = $student->enrollments()->with('schoolClass')->where('status', 'active')->latest()->first()?->schoolClass;
        $attendance = $attendanceSummary->forStudent($student);
        $scoreQuery = AssessmentScore::query()
            ->where('student_id', $student->id)
            ->whereHas('assessment', fn ($query) => $query->where('status', 'published'));
        $performanceOverview = $charts->performanceOverview(
            clone $scoreQuery,
            (int) $student->school_id,
        );

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
            'recentResults' => (clone $scoreQuery)->with(['assessment.classSubject.subject'])->latest()->limit(5)->get(),
            'attendanceChart' => $charts->attendance($attendance['summary']),
            'performanceChart' => $charts->studentPerformance($student->id),
            'performanceOverview' => $performanceOverview,
            'announcements' => Announcement::where('school_id', $student->school_id)
                ->published()
                ->where(fn ($query) => $query
                    ->where('audience', 'school')
                    ->orWhere(fn ($classes) => $classes->where('audience', 'class')->whereIn('school_class_id', $classIds))
                    ->orWhere(fn ($subjects) => $subjects->where('audience', 'subject')->whereIn('subject_id', ClassSubject::whereIn('school_class_id', $classIds)->select('subject_id'))))
                ->latest('published_at')->limit(5)->get(),
        ]);
    }
}
