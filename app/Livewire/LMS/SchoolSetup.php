<?php

namespace App\Livewire\LMS;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Subject;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class SchoolSetup extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->can('viewAny', AcademicYear::class), 403);
    }

    public function render()
    {
        return view('livewire.lms.school-setup', [
            'years' => AcademicYear::with('terms')->latest()->limit(5)->get(),
            'classes' => SchoolClass::with('students')->latest()->limit(8)->get(),
            'subjects' => Subject::latest()->limit(8)->get(),
        ]);
    }
}
