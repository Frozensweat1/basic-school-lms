<?php

namespace App\Livewire\LMS\Dashboard;

use App\Models\Announcement;
use App\Models\AssessmentScore;
use App\Models\Assignment;
use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\ReportCard;
use App\Support\AttendanceSummary;
use App\Support\DashboardChartData;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class ParentDashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('parent') && auth()->user()->parentGuardian, 403);
    }

    public function render(AttendanceSummary $attendanceSummary, DashboardChartData $charts)
    {
        $studentIds = auth()->user()->parentGuardian->students()->where('status', 'active')->pluck('students.id');
        $classIds = ClassEnrollment::whereIn('student_id', $studentIds)->where('status', 'active')->pluck('school_class_id');
        $classSubjectIds = ClassSubject::whereIn('school_class_id', $classIds)->pluck('id');
        $attendance = $attendanceSummary->forStudents($studentIds);
        $scoreQuery = AssessmentScore::query()
            ->whereIn('student_id', $studentIds)
            ->whereHas('assessment', fn ($query) => $query->where('status', 'published'));
        $academicAverage = $charts->normalizedAverage(clone $scoreQuery);
        $performanceOverview = $charts->performanceOverview(
            clone $scoreQuery,
            (int) auth()->user()->parentGuardian->school_id,
        );

        return view('livewire.lms.dashboard.parent', [
            'metrics' => [
                'Wards' => $studentIds->count(),
                'Attendance rate' => $attendance['total'] ? $attendance['percentage'].'%' : '—',
                'Pending assignments' => Assignment::whereIn('class_subject_id', $classSubjectIds)->where('status', 'published')->where('due_at', '>=', now())->count(),
                'Published reports' => ReportCard::whereIn('student_id', $studentIds)->where('status', 'published')->count(),
                'Academic average' => $academicAverage !== null ? $academicAverage.'%' : '—',
                'Unread notifications' => auth()->user()->unreadNotifications()->count(),
            ],
            'attendanceChart' => $charts->attendance($attendance['summary']),
            'wardPerformanceChart' => $charts->wardPerformance($studentIds),
            'performanceOverview' => $performanceOverview,
            'announcements' => Announcement::where('school_id', auth()->user()->parentGuardian->school_id)
                ->published()
                ->where(fn ($query) => $query
                    ->where('audience', 'school')
                    ->orWhere(fn ($classes) => $classes->where('audience', 'class')->whereIn('school_class_id', $classIds))
                    ->orWhere(fn ($subjects) => $subjects->where('audience', 'subject')->whereIn('subject_id', ClassSubject::whereIn('school_class_id', $classIds)->select('subject_id'))))
                ->latest('published_at')->limit(5)->get(),
        ]);
    }
}
