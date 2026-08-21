<?php

namespace App\Livewire\LMS\Examinations\Student;

use App\Models\{Examination, Student};
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
        return view('livewire.lms.examinations.student.index', [
            'examinations' => Examination::with(['classSubject.subject', 'term'])
                ->where('school_id', $this->student->school_id)
                ->whereIn('status', ['scheduled', 'completed'])
                ->whereHas('classSubject.schoolClass.enrollments', fn ($q) => $q->where('student_id', $this->student->id)->where('status', 'active'))
                ->orderBy('exam_date')->get(),
        ]);
    }
}
