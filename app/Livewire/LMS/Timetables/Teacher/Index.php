<?php

namespace App\Livewire\LMS\Timetables\Teacher;

use App\Models\Timetable;
use App\Models\TimetableEntry;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Index extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('teacher') && auth()->user()->teacher, 403);
    }

    public function render()
    {
        $teacher = auth()->user()->teacher;
        $entries = TimetableEntry::query()
            ->where('teacher_id', $teacher->id)
            ->whereHas('timetable', fn ($query) => $query->where('status', 'published')->whereHas('academicYear', fn ($year) => $year->where('school_id', $teacher->school_id)))
            ->with(['schoolClass', 'classSubject.subject', 'schedulePeriod', 'timetable.term'])
            ->orderBy('day_of_week')
            ->get()
            ->sortBy(fn ($entry) => [$entry->day_of_week, $entry->schedulePeriod?->sequence ?? 0]);

        return view('livewire.lms.timetables.teacher.index', compact('entries'));
    }
}
