<?php

namespace App\Livewire\LMS\Reports;

use App\Jobs\GenerateReportCardJob;
use App\Models\{ReportCard, School, SchoolClass, Student, SubjectResult, Term};
use App\Services\Reports\ReportCardGenerator;
use App\Support\LmsNotifier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;

    public string $termId = '';
    public string $classId = '';
    public string $studentId = '';

    public function mount(): void { $this->authorize('viewAny', ReportCard::class); }

    public function generateSingle(ReportCardGenerator $generator): void
    {
        $this->authorize('create', ReportCard::class);
        try {
            $this->validate(['termId' => ['required', 'integer'], 'classId' => ['required', 'integer'], 'studentId' => ['required', 'integer']]);
            $term = $this->scopedTerms()->findOrFail((int) $this->termId); $class = $this->scopedClasses()->findOrFail((int) $this->classId); $student = $this->scopedStudents()->findOrFail((int) $this->studentId);
            abort_unless($term->academic_year_id === $class->academic_year_id && $class->enrollments()->where('student_id', $student->id)->where('status', 'active')->exists(), 422, 'Choose an active student enrolled in the selected class and term.');
            $generator->generate($student, $term, $class->id); LivewireAlert::title('Report card generated')->success()->asToast()->show();
        } catch (ValidationException $exception) { LivewireAlert::title('Choose a valid report scope')->error()->asToast()->show(); throw $exception; } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to generate report card')->error()->asToast()->show(); }
    }

    public function generateBulk(): void
    {
        $this->authorize('create', ReportCard::class);
        try {
            $this->validate(['termId' => ['required', 'integer'], 'classId' => ['required', 'integer']]); $term = $this->scopedTerms()->findOrFail((int) $this->termId); $class = $this->scopedClasses()->findOrFail((int) $this->classId); abort_unless($term->academic_year_id === $class->academic_year_id, 422, 'Choose a term from the selected academic year.');
            $enrollments = $class->enrollments()->with('student')->where('status', 'active')->get();
            abort_unless($enrollments->isNotEmpty(), 422, 'The selected class has no active students.');
            foreach ($enrollments as $enrollment) GenerateReportCardJob::dispatch($enrollment->student, $term, $class->id);
            LivewireAlert::title('Report cards queued')->success()->asToast()->show();
        } catch (ValidationException $exception) { LivewireAlert::title('Choose a valid report scope')->error()->asToast()->show(); throw $exception; } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to generate reports')->error()->asToast()->show(); }
    }

    public function publish(int $reportCardId): void
    {
        $reportCard = $this->scopedReportCards()->with(['student.user', 'student.parents.user'])->findOrFail($reportCardId); $this->authorize('update', $reportCard); $wasPublished = $reportCard->status === 'published'; $reportCard->publish(); if (! $wasPublished) $this->notifyPublished($reportCard); LivewireAlert::title('Report card published')->success()->asToast()->show();
    }

    public function publishBulk(): void
    {
        $this->authorize('create', ReportCard::class);
        try { $this->validate(['termId' => ['required', 'integer'], 'classId' => ['required', 'integer']]); $term = $this->scopedTerms()->findOrFail((int) $this->termId); $class = $this->scopedClasses()->findOrFail((int) $this->classId); abort_unless($term->academic_year_id === $class->academic_year_id, 422, 'Choose a term from the selected academic year.'); $reports = $this->scopedReportCards()->with(['student.user', 'student.parents.user'])->where('term_id', $term->id)->where('school_class_id', $class->id)->where('status', 'draft')->get(); abort_unless($reports->isNotEmpty(), 422, 'Generate the class reports before publishing them.'); DB::transaction(function () use ($reports): void { foreach ($reports as $report) { $report->publish(); $this->notifyPublished($report); } }); LivewireAlert::title('Class reports published')->success()->asToast()->show(); } catch (ValidationException $exception) { LivewireAlert::title('Choose a valid report scope')->error()->asToast()->show(); throw $exception; } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to publish reports')->error()->asToast()->show(); }
    }

    public function render()
    {
        $reportCardsQuery = $this->scopedReportCards()->with('student')->when($this->termId, fn ($query) => $query->where('term_id', (int) $this->termId))->when($this->classId, fn ($query) => $query->where('school_class_id', (int) $this->classId)); $reportCards = $reportCardsQuery->latest('generated_at')->get(); $scores = $reportCards->pluck('student_id')->isNotEmpty() ? SubjectResult::whereIn('student_id', $reportCards->pluck('student_id'))->where('status', 'published')->when($this->termId, fn ($query) => $query->where('term_id', (int) $this->termId))->when($this->classId, fn ($query) => $query->whereHas('classSubject', fn ($subject) => $subject->where('school_class_id', (int) $this->classId))) : collect();
        return view('livewire.lms.reports.index', ['terms' => $this->scopedTerms()->orderBy('sequence')->get(), 'classes' => $this->scopedClasses()->orderBy('name')->get(), 'students' => $this->scopedStudents()->where('status', 'active')->orderBy('last_name')->get(), 'reportCards' => $reportCards, 'metrics' => ['attendance' => round((float) ($reportCards->avg('attendance_percentage') ?? 0), 1), 'averageScore' => round((float) ($scores->avg('total_score') ?? 0), 1), 'atRisk' => $scores->where('total_score', '<', 50)->pluck('student_id')->unique()->count()]]);
    }

    private function schoolId(): int { return (int) School::query()->value('id'); }
    private function scopedTerms() { return Term::whereHas('academicYear', fn ($q) => $q->where('school_id', $this->schoolId())); }
    private function scopedClasses() { return SchoolClass::whereHas('academicYear', fn ($q) => $q->where('school_id', $this->schoolId())); }
    private function scopedStudents() { return Student::where('school_id', $this->schoolId()); }
    private function scopedReportCards() { return ReportCard::whereHas('student', fn ($q) => $q->where('school_id', $this->schoolId())); }
    private function notifyPublished(ReportCard $reportCard): void { $recipients = collect([$reportCard->student?->user])->merge($reportCard->student?->parents?->pluck('user') ?? collect())->filter()->unique('id'); LmsNotifier::send($recipients, 'Report card published', 'Your report card for '.$reportCard->term?->name.' is now available.', route('lms.reports.show', $reportCard), 'report'); }
}
