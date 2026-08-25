<?php

namespace App\Livewire\LMS\Attendance\Teacher;

use App\Livewire\LMS\Attendance\Index as SharedAttendance;
use App\Models\AttendanceRecord;
use Livewire\Attributes\Layout;

#[Layout('layouts.lms')]
class Record extends SharedAttendance
{
    public function mount(): void
    {
        $this->authorize('viewAny', AttendanceRecord::class);
        abort_unless(auth()->user()->hasRole('teacher') && auth()->user()->teacher, 403);

        $this->initialiseRegisterDefaults();
    }
}
