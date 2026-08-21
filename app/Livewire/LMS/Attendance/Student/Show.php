<?php

namespace App\Livewire\LMS\Attendance\Student;

use App\Models\{AttendanceRecord, Student};
use App\Support\AttendanceSummary;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Show extends Component
{
    public Student $student;
    public string $fromDate = '', $toDate = '';

    public function mount(): void
    {
        $this->student = auth()->user()->student;
        abort_unless(auth()->user()->hasRole('student') && $this->student, 403);
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function updatedFromDate(): void { $this->validateDateRange(); }
    public function updatedToDate(): void { $this->validateDateRange(); }

    public function render(AttendanceSummary $attendanceSummary)
    {
        $records = AttendanceRecord::where('student_id', $this->student->id)->whereHas('schoolClass.academicYear', fn ($query) => $query->where('school_id', $this->student->school_id))->when($this->fromDate, fn ($q) => $q->whereDate('attendance_date', '>=', $this->fromDate))->when($this->toDate, fn ($q) => $q->whereDate('attendance_date', '<=', $this->toDate))->with('schoolClass')->latest('attendance_date')->get();
        $summary = $attendanceSummary->forStudent($this->student, $this->fromDate, $this->toDate);
        return view('livewire.lms.attendance.student.show', ['records' => $records, 'percentage' => $summary['percentage'], 'summary' => collect($summary['summary'])]);
    }

    private function validateDateRange(): void
    {
        $this->validate(['fromDate' => ['nullable', 'date'], 'toDate' => ['nullable', 'date', 'after_or_equal:fromDate', 'before_or_equal:today']]);
    }
}
