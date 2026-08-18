<?php

namespace App\Livewire\LMS\Reports;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\ReportCard;
use App\Services\Reports\ReportCardGenerator;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Index extends Component
{
    public string $termId = '';
    public string $classId = '';
    public string $studentId = '';

    public function generateSingle(ReportCardGenerator $generator): void
    {
        $this->validate(['termId' => ['required', 'exists:terms,id'], 'classId' => ['required', 'exists:school_classes,id'], 'studentId' => ['required', 'exists:students,id']]);
        $generator->generate(Student::findOrFail($this->studentId), Term::findOrFail($this->termId), (int) $this->classId);
        LivewireAlert::title('Report card generated')->success()->asToast()->show();
    }

    public function generateBulk(ReportCardGenerator $generator): void
    {
        $this->validate(['termId' => ['required', 'exists:terms,id'], 'classId' => ['required', 'exists:school_classes,id']]);
        $term = Term::findOrFail($this->termId);
        $class = SchoolClass::findOrFail($this->classId);
        foreach ($class->enrollments()->where('status', 'active')->get() as $enrollment) {
            $generator->generate($enrollment->student, $term, $class->id);
        }
        LivewireAlert::title('Class report cards generated')->success()->asToast()->show();
    }

    public function publish(int $reportCardId): void
    {
        ReportCard::findOrFail($reportCardId)->publish();
        LivewireAlert::title('Report card published')->success()->asToast()->show();
    }

    public function publishBulk(): void
    {
        $this->validate(['termId' => ['required', 'exists:terms,id'], 'classId' => ['required', 'exists:school_classes,id']]);
        ReportCard::where('term_id', $this->termId)->where('school_class_id', $this->classId)->where('status', 'draft')->get()->each->publish();
        LivewireAlert::title('Class report cards published')->success()->asToast()->show();
    }

    public function render()
    {
        return view('livewire.lms.reports.index', [
            'terms' => Term::orderBy('sequence')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
            'students' => Student::where('status', 'active')->orderBy('last_name')->get(),
            'reportCards' => ReportCard::with('student')->latest('generated_at')->get(),
        ]);
    }
}
