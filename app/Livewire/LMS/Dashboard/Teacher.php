<?php

namespace App\Livewire\LMS\Dashboard;

use App\Models\Announcement;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSubject;
use App\Models\Quiz;
use App\Support\DashboardChartData;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Teacher extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('teacher') && auth()->user()->teacher, 403);
    }

    public function render(DashboardChartData $charts)
    {
        $teacherId = auth()->user()->teacher->id;
        $assignments = Assignment::where('teacher_id', $teacherId);
        $quizzes = Quiz::where('teacher_id', $teacherId);
        $classSubjects = ClassSubject::with(['schoolClass', 'subject'])->where('teacher_id', $teacherId)->get();
        $performanceOverview = $charts->performanceOverview(
            AssessmentScore::query()->whereHas('assessment', fn ($query) => $query
                ->where('teacher_id', $teacherId)
                ->where('status', 'published')),
            (int) auth()->user()->teacher->school_id,
        );

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
            'workloadChart' => $charts->teacherWorkload($teacherId),
            'performanceChart' => $charts->teacherPerformance($teacherId),
            'performanceOverview' => $performanceOverview,
            'announcements' => Announcement::where('school_id', auth()->user()->teacher->school_id)
                ->published()
                ->where(function ($query) use ($classSubjects): void {
                    $query->whereIn('audience', ['school', 'teachers'])
                        ->orWhere('created_by', auth()->id())
                        ->orWhere(fn ($classes) => $classes->where('audience', 'class')->whereIn('school_class_id', $classSubjects->pluck('school_class_id')))
                        ->orWhere(fn ($subjects) => $subjects->where('audience', 'subject')->whereIn('subject_id', $classSubjects->pluck('subject_id')));
                })
                ->latest('published_at')->limit(5)->get(),
        ]);
    }
}
