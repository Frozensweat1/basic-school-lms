<?php

namespace App\Livewire\LMS\Dashboard;

use App\Models\{Announcement, Assignment, AttendanceRecord, AssessmentScore, ClassEnrollment, ClassSubject, ReportCard};
use App\Support\AttendanceSummary;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class ParentDashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('parent') && auth()->user()->parentGuardian, 403);
    }

    public function render(AttendanceSummary $attendanceSummary)
    {
        $studentIds = auth()->user()->parentGuardian->students()->where('status', 'active')->pluck('students.id');
        $classIds = ClassEnrollment::whereIn('student_id', $studentIds)->where('status', 'active')->pluck('school_class_id');
        $classSubjectIds = ClassSubject::whereIn('school_class_id', $classIds)->pluck('id');
        $attendance = $attendanceSummary->forStudents($studentIds);

        return view('livewire.lms.dashboard.parent', [
            'metrics' => [
                'Wards' => $studentIds->count(),
                'Attendance rate' => $attendance['total'] ? $attendance['percentage'].'%' : '—',
                'Pending assignments' => Assignment::whereIn('class_subject_id', $classSubjectIds)->where('status', 'published')->where('due_at', '>=', now())->count(),
                'Published reports' => ReportCard::whereIn('student_id', $studentIds)->where('status', 'published')->count(),
                'Academic average' => ($average = AssessmentScore::whereIn('student_id', $studentIds)->avg('score')) !== null ? round((float) $average, 1) : '—',
                'Unread notifications' => auth()->user()->unreadNotifications()->count(),
            ],
            'announcements' => Announcement::where('school_id', auth()->user()->parentGuardian->school_id)->whereNotNull('published_at')->where('published_at', '<=', now())->where(fn ($q) => $q->where('audience', 'school')->orWhere(fn ($class) => $class->where('audience', 'class')->whereIn('school_class_id', $classIds)))->latest('published_at')->limit(5)->get(),
        ]);
    }
}
