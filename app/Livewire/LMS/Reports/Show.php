<?php

namespace App\Livewire\LMS\Reports;

use App\Models\ReportCard;
use App\Models\SubjectResult;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Show extends Component
{
    use AuthorizesRequests;

    public ReportCard $reportCard;

    public function mount(ReportCard $reportCard): void
    {
        $this->authorize('view', $reportCard);
        $this->reportCard = $reportCard->load(['student', 'academicYear', 'term', 'schoolClass']);
    }

    public function render()
    {
        $results = SubjectResult::query()
            ->with(['classSubject.subject', 'gradingScale'])
            ->where('student_id', $this->reportCard->student_id)
            ->where('term_id', $this->reportCard->term_id)
            ->where('status', 'published')
            ->whereHas('classSubject', fn ($query) => $query->where('school_class_id', $this->reportCard->school_class_id))
            ->orderBy('class_subject_id')
            ->get();

        return view('livewire.lms.reports.show', compact('results'));
    }
}
