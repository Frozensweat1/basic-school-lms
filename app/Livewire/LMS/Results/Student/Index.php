<?php

namespace App\Livewire\LMS\Results\Student;

use App\Models\Student;
use App\Models\SubjectResult;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Index extends Component
{
    public Student $student;

    public function mount(): void
    {
        $this->student = auth()->user()->student;
        abort_unless(auth()->user()->hasRole('student') && $this->student, 403);
    }

    public function render()
    {
        return view('livewire.lms.results.student.index', [
            'results' => SubjectResult::query()
                ->with(['classSubject.subject', 'term', 'gradingScale'])
                ->where('student_id', $this->student->id)
                ->where('status', 'published')
                ->whereHas('student', fn ($query) => $query->where('school_id', $this->student->school_id))
                ->latest('term_id')
                ->get(),
        ]);
    }
}
