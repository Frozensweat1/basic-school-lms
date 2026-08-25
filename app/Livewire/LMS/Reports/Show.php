<?php

namespace App\Livewire\LMS\Reports;

use App\Models\ReportCard;
use App\Models\SubjectResult;
use App\Support\SchoolBranding;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.lms')]
class Show extends Component
{
    use AuthorizesRequests;

    public ReportCard $reportCard;

    public string $teacherComment = '';

    public string $headteacherComment = '';

    public function mount(ReportCard $reportCard): void
    {
        $this->authorize('view', $reportCard);
        $this->reportCard = $reportCard->load(['student', 'academicYear', 'term', 'schoolClass']);
        $this->teacherComment = (string) ($reportCard->teacher_comment ?? '');
        $this->headteacherComment = (string) ($reportCard->headteacher_comment ?? '');
    }

    public function saveComments(): void
    {
        $this->authorize('update', $this->reportCard);

        try {
            $data = $this->validate([
                'teacherComment' => ['nullable', 'string', 'max:2000'],
                'headteacherComment' => ['nullable', 'string', 'max:2000'],
            ]);

            $this->reportCard->update([
                'teacher_comment' => filled($data['teacherComment']) ? trim($data['teacherComment']) : null,
                'headteacher_comment' => filled($data['headteacherComment']) ? trim($data['headteacherComment']) : null,
            ]);
            $this->reportCard->refresh();
            LivewireAlert::title('Report comments saved')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the report comments')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save report comments')->error()->asToast()->position('top-end')->show();
        }
    }

    public function render()
    {
        $results = SubjectResult::query()
            ->with(['classSubject.subject', 'gradingScale'])
            ->where('student_id', $this->reportCard->student_id)
            ->where('term_id', $this->reportCard->term_id)
            ->where('status', 'published')
            ->whereHas('classSubject', fn ($query) => $query->where('school_class_id', $this->reportCard->school_class_id))
            ->get()
            ->sortBy(fn (SubjectResult $result) => $result->classSubject?->subject?->name)
            ->values();
        $scores = $results->whereNotNull('total_score')->pluck('total_score')->map(fn ($score) => (float) $score);

        return view('livewire.lms.reports.show', [
            'results' => $results,
            'branding' => app(SchoolBranding::class)->data(),
            'metrics' => [
                'average' => $scores->isEmpty() ? null : round($scores->avg(), 1),
                'highest' => $scores->isEmpty() ? null : round($scores->max(), 1),
                'lowest' => $scores->isEmpty() ? null : round($scores->min(), 1),
                'passed' => $scores->filter(fn (float $score) => $score >= 50)->count(),
            ],
            'backRoute' => auth()->user()->hasRole('student')
                ? route('lms.reports.student.index')
                : (auth()->user()->hasRole('parent') ? route('lms.reports.parent.index') : route('lms.reports.index')),
        ]);
    }
}
