<?php

namespace App\Livewire\LMS\Dashboard;

use App\Models\{Announcement, Assessment, AssessmentScore, AssignmentSubmission, AttendanceRecord, ClassSubject, School, SchoolClass, Student, Teacher};
use App\Support\AttendanceSummary;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Admin extends Component
{
    public function mount(): void { abort_unless(auth()->user()->hasAnyRole(['super_admin', 'school_admin']), 403); }
    public function render(AttendanceSummary $attendanceSummary)
    {
        $schoolId = (int) School::query()->value('id');
        $classIds = SchoolClass::whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))->pluck('id');
        $classSubjectIds = ClassSubject::whereIn('school_class_id', $classIds)->pluck('id');
        $attendance = $attendanceSummary->forSchool($schoolId);

        return view('livewire.lms.dashboard.admin', [
            'metrics' => [
                'Students' => Student::where('school_id', $schoolId)->count(),
                'Teachers' => Teacher::where('school_id', $schoolId)->count(),
                'Classes' => $classIds->count(),
                'Subjects' => ClassSubject::whereIn('id', $classSubjectIds)->distinct('subject_id')->count('subject_id'),
                'Attendance rate' => $attendance['total'] ? $attendance['percentage'].'%' : '—',
                'Pending submissions' => AssignmentSubmission::whereHas('assignment', fn ($q) => $q->whereIn('class_subject_id', $classSubjectIds))->where('status', 'submitted')->count(),
                'Academic average' => ($average = AssessmentScore::whereHas('assessment', fn ($q) => $q->whereIn('class_subject_id', $classSubjectIds))->avg('score')) !== null ? round((float) $average, 1) : '—',
            ],
            'attendanceSummary' => collect($attendance['summary']),
            'announcements' => Announcement::where('school_id', $schoolId)->whereNotNull('published_at')->where('published_at', '<=', now())->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->latest('published_at')->limit(5)->get(),
        ]);
    }
}
