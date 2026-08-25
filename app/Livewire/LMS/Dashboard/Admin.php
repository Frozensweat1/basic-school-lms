<?php

namespace App\Livewire\LMS\Dashboard;

use App\Models\Announcement;
use App\Models\AssessmentScore;
use App\Models\AssignmentSubmission;
use App\Models\ClassSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Support\AttendanceSummary;
use App\Support\DashboardChartData;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Admin extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'school_admin']), 403);
    }

    public function render(AttendanceSummary $attendanceSummary, DashboardChartData $charts)
    {
        $schoolId = (int) School::query()->value('id');
        $classIds = SchoolClass::whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))->pluck('id');
        $classSubjectIds = ClassSubject::whereIn('school_class_id', $classIds)->pluck('id');
        $attendance = $attendanceSummary->forSchool($schoolId);
        $scoreQuery = AssessmentScore::query()
            ->whereHas('assessment', fn ($query) => $query
                ->whereIn('class_subject_id', $classSubjectIds)
                ->where('status', 'published'));
        $academicAverage = $charts->normalizedAverage(
            clone $scoreQuery,
        );
        $performanceOverview = $charts->performanceOverview(clone $scoreQuery, $schoolId);

        return view('livewire.lms.dashboard.admin', [
            'metrics' => [
                'Students' => Student::where('school_id', $schoolId)->count(),
                'Teachers' => Teacher::where('school_id', $schoolId)->count(),
                'Classes' => $classIds->count(),
                'Subjects' => ClassSubject::whereIn('id', $classSubjectIds)->distinct('subject_id')->count('subject_id'),
                'Attendance rate' => $attendance['total'] ? $attendance['percentage'].'%' : '—',
                'Pending submissions' => AssignmentSubmission::whereHas('assignment', fn ($q) => $q->whereIn('class_subject_id', $classSubjectIds))->where('status', 'submitted')->count(),
                'Academic average' => $academicAverage !== null ? $academicAverage.'%' : '—',
            ],
            'attendanceSummary' => collect($attendance['summary']),
            'attendanceChart' => $charts->attendance($attendance['summary']),
            'enrollmentChart' => $charts->schoolEnrollment($schoolId),
            'performanceOverview' => $performanceOverview,
            'announcements' => Announcement::where('school_id', $schoolId)->whereNotNull('published_at')->where('published_at', '<=', now())->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->latest('published_at')->limit(5)->get(),
        ]);
    }
}
