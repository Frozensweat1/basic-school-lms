<?php
namespace App\Livewire\LMS\Attendance\Admin;
use App\Livewire\LMS\Attendance\Index as SharedAttendance; use App\Models\AttendanceRecord; use Livewire\Attributes\Layout;
#[Layout('layouts.lms')] class Overview extends SharedAttendance { public function mount():void{$this->authorize('viewAny',AttendanceRecord::class);abort_unless(auth()->user()->hasAnyRole(['super_admin','school_admin']),403);$this->attendanceDate=now()->toDateString();} }
