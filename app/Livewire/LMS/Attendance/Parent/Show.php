<?php

namespace App\Livewire\LMS\Attendance\Parent;

use App\Models\{AttendanceRecord, ParentGuardian};
use App\Support\AttendanceSummary;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.lms')]
class Show extends Component
{
    use WithPagination;

    public ParentGuardian $parent;
    public string $studentId = '', $fromDate = '', $toDate = '';

    public function mount(): void
    {
        $this->parent = auth()->user()->parentGuardian;
        abort_unless(auth()->user()->hasRole('parent') && $this->parent, 403);
        $this->studentId = (string) ($this->parent->students()->where('students.status', 'active')->value('students.id') ?? ''); $this->fromDate = now()->startOfMonth()->toDateString(); $this->toDate = now()->toDateString();
    }

    public function updatedStudentId(): void { $this->resetPage(); abort_unless($this->studentId === '' || $this->parent->students()->whereKey((int) $this->studentId)->where('students.status', 'active')->exists(), 403); }
    public function updatedFromDate(): void { $this->resetPage(); $this->validateDateRange(); }
    public function updatedToDate(): void { $this->resetPage(); $this->validateDateRange(); }

    public function render(AttendanceSummary $attendanceSummary)
    {
        $students = $this->parent->students()->where('students.status', 'active')->orderBy('last_name')->get(); $student = $students->firstWhere('id', (int) $this->studentId); $records = $student ? AttendanceRecord::where('student_id', $student->id)->whereHas('schoolClass.academicYear', fn ($query) => $query->where('school_id', $this->parent->school_id))->when($this->fromDate, fn ($q) => $q->whereDate('attendance_date', '>=', $this->fromDate))->when($this->toDate, fn ($q) => $q->whereDate('attendance_date', '<=', $this->toDate))->with('schoolClass')->latest('attendance_date')->paginate(15) : AttendanceRecord::query()->whereRaw('1 = 0')->paginate(15); $summary = $student ? $attendanceSummary->forStudent($student, $this->fromDate, $this->toDate) : ['percentage' => 0, 'summary' => []];
        return view('livewire.lms.attendance.parent.show', compact('students', 'student', 'records') + ['percentage' => $summary['percentage'], 'summary' => collect($summary['summary'])]);
    }

    private function validateDateRange(): void
    {
        $this->validate(['fromDate' => ['nullable', 'date'], 'toDate' => ['nullable', 'date', 'after_or_equal:fromDate', 'before_or_equal:today']]);
    }
}
