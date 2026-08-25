<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Quiz;
use App\Models\SchoolClass;
use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class DashboardChartData
{
    public function attendance(array $summary): array
    {
        return [
            'meta' => ['hasData' => array_sum($summary) > 0],
            'type' => 'doughnut',
            'data' => [
                'labels' => ['Present', 'Late', 'Absent', 'Excused'],
                'datasets' => [[
                    'label' => 'Attendance records',
                    'data' => [
                        (int) ($summary['present'] ?? 0),
                        (int) ($summary['late'] ?? 0),
                        (int) ($summary['absent'] ?? 0),
                        (int) ($summary['excused'] ?? 0),
                    ],
                    'backgroundColor' => ['#10b981', '#f59e0b', '#f43f5e', '#38bdf8'],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 3,
                    'hoverOffset' => 6,
                ]],
            ],
            'options' => [
                'cutout' => '66%',
            ],
        ];
    }

    public function schoolEnrollment(int $schoolId): array
    {
        $classes = SchoolClass::query()
            ->whereHas('academicYear', fn (Builder $query) => $query
                ->where('school_id', $schoolId)
                ->where('is_active', true))
            ->withCount(['enrollments as active_students_count' => fn (Builder $query) => $query->where('status', 'active')])
            ->orderBy('name')
            ->get();

        return $this->bar(
            $classes->pluck('name'),
            $classes->pluck('active_students_count')->map(fn ($count) => (int) $count),
            'Active students',
            '#2563eb',
            horizontal: true,
        );
    }

    public function teacherWorkload(int $teacherId): array
    {
        return $this->bar(
            collect(['Assignments', 'Quizzes', 'Assessments', 'Pending grading']),
            collect([
                Assignment::query()->where('teacher_id', $teacherId)->count(),
                Quiz::query()->where('teacher_id', $teacherId)->count(),
                Assessment::query()->where('teacher_id', $teacherId)->count(),
                AssignmentSubmission::query()
                    ->whereHas('assignment', fn (Builder $query) => $query->where('teacher_id', $teacherId))
                    ->where('status', 'submitted')->count(),
            ]),
            'Items',
            ['#2563eb', '#7c3aed', '#0d9488', '#f59e0b'],
        );
    }

    public function teacherPerformance(int $teacherId): array
    {
        return $this->subjectPerformance(
            AssessmentScore::query()->whereHas('assessment', fn (Builder $query) => $query
                ->where('teacher_id', $teacherId)
                ->where('status', 'published')),
        );
    }

    public function studentPerformance(int $studentId): array
    {
        return $this->subjectPerformance(
            AssessmentScore::query()
                ->where('student_id', $studentId)
                ->whereHas('assessment', fn (Builder $query) => $query->where('status', 'published')),
        );
    }

    public function normalizedAverage(Builder $query): ?float
    {
        $scores = $query
            ->with('assessment')
            ->get()
            ->filter(fn (AssessmentScore $score) => (float) ($score->assessment?->max_score ?? 0) > 0);

        if ($scores->isEmpty()) {
            return null;
        }

        return round((float) $scores->avg(fn (AssessmentScore $score) => $this->percentage($score)), 1);
    }

    public function performanceOverview(Builder $query, int $schoolId): array
    {
        $academicYear = AcademicYear::query()
            ->with(['terms' => fn ($terms) => $terms->orderBy('sequence')])
            ->where('school_id', $schoolId)
            ->orderByDesc('is_active')
            ->orderByDesc('starts_at')
            ->first();
        $activeTerm = $academicYear?->terms->firstWhere('is_active', true)
            ?? $academicYear?->terms->sortByDesc('starts_at')->first();
        $scores = $query
            ->with(['assessment.term.academicYear', 'assessment.classSubject.subject'])
            ->get()
            ->filter(fn (AssessmentScore $score) => (float) ($score->assessment?->max_score ?? 0) > 0);
        $yearScores = $academicYear
            ? $scores->filter(fn (AssessmentScore $score) => (int) $score->assessment?->term?->academic_year_id === $academicYear->id)
            : collect();
        $termScores = $activeTerm
            ? $yearScores->filter(fn (AssessmentScore $score) => (int) $score->assessment?->term_id === $activeTerm->id)
            : collect();

        return [
            'academicYearName' => $academicYear?->name ?? 'No academic year',
            'termName' => $activeTerm?->name ?? 'No active term',
            'termlyChart' => $this->termlyPerformance($academicYear, $yearScores),
            'academicYearChart' => $this->academicYearPerformance($schoolId, $scores),
            'subjectChart' => $this->subjectPeriodComparison($activeTerm, $termScores, $yearScores),
        ];
    }

    public function wardPerformance(Collection $studentIds): array
    {
        $values = AssessmentScore::query()
            ->with(['assessment', 'student'])
            ->whereIn('student_id', $studentIds)
            ->whereHas('assessment', fn (Builder $query) => $query->where('status', 'published'))
            ->get()
            ->filter(fn (AssessmentScore $score) => (float) ($score->assessment?->max_score ?? 0) > 0)
            ->groupBy('student_id')
            ->map(function (Collection $scores): array {
                $student = $scores->first()->student;

                return [
                    'label' => trim(($student?->first_name ?? 'Student').' '.($student?->last_name ?? '')),
                    'value' => round((float) $scores->avg(fn (AssessmentScore $score) => $this->percentage($score)), 1),
                ];
            })
            ->values();

        return $this->bar(
            $values->pluck('label'),
            $values->pluck('value'),
            'Average score (%)',
            '#7c3aed',
        );
    }

    private function subjectPerformance(Builder $query): array
    {
        $values = $query
            ->with('assessment.classSubject.subject')
            ->get()
            ->filter(fn (AssessmentScore $score) => (float) ($score->assessment?->max_score ?? 0) > 0)
            ->groupBy(fn (AssessmentScore $score) => $score->assessment?->classSubject?->subject?->name ?? 'Other')
            ->map(fn (Collection $scores) => round((float) $scores->avg(fn (AssessmentScore $score) => $this->percentage($score)), 1))
            ->sortKeys();

        return $this->bar(
            $values->keys(),
            $values->values(),
            'Average score (%)',
            '#0d9488',
        );
    }

    private function percentage(AssessmentScore $score): float
    {
        return min(100, max(0, (float) $score->score / (float) $score->assessment->max_score * 100));
    }

    private function termlyPerformance(?AcademicYear $academicYear, Collection $scores): array
    {
        $terms = $academicYear?->terms?->sortBy('sequence')->values() ?? collect();
        $values = $terms->map(fn (Term $term) => $this->averagePercentage(
            $scores->filter(fn (AssessmentScore $score) => (int) $score->assessment?->term_id === $term->id),
        ));

        return $this->line(
            $terms->pluck('name'),
            $values,
            'Average score (%)',
            '#2563eb',
        );
    }

    private function academicYearPerformance(int $schoolId, Collection $scores): array
    {
        $academicYears = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->orderBy('starts_at')
            ->get();
        $values = $academicYears->map(fn (AcademicYear $year) => $this->averagePercentage(
            $scores->filter(fn (AssessmentScore $score) => (int) $score->assessment?->term?->academic_year_id === $year->id),
        ));

        return $this->line(
            $academicYears->pluck('name'),
            $values,
            'Average score (%)',
            '#7c3aed',
        );
    }

    private function subjectPeriodComparison(?Term $activeTerm, Collection $termScores, Collection $yearScores): array
    {
        $termValues = $this->subjectAverages($termScores);
        $yearValues = $this->subjectAverages($yearScores);
        $labels = $termValues->keys()->merge($yearValues->keys())->unique()->sort()->values();

        return [
            'meta' => ['hasData' => $labels->isNotEmpty()],
            'type' => 'bar',
            'data' => [
                'labels' => $labels->all(),
                'datasets' => [
                    [
                        'label' => ($activeTerm?->name ?? 'Active term').' average (%)',
                        'data' => $labels->map(fn (string $subject) => $termValues->get($subject))->all(),
                        'backgroundColor' => '#0d9488',
                        'borderRadius' => 7,
                        'borderSkipped' => false,
                        'maxBarThickness' => 42,
                    ],
                    [
                        'label' => 'Academic year average (%)',
                        'data' => $labels->map(fn (string $subject) => $yearValues->get($subject))->all(),
                        'backgroundColor' => '#7c3aed',
                        'borderRadius' => 7,
                        'borderSkipped' => false,
                        'maxBarThickness' => 42,
                    ],
                ],
            ],
            'options' => [
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'max' => 100,
                        'ticks' => ['precision' => 0],
                    ],
                ],
            ],
        ];
    }

    private function subjectAverages(Collection $scores): Collection
    {
        return $scores
            ->groupBy(fn (AssessmentScore $score) => $score->assessment?->classSubject?->subject?->name ?? 'Other')
            ->map(fn (Collection $subjectScores) => $this->averagePercentage($subjectScores))
            ->sortKeys();
    }

    private function averagePercentage(Collection $scores): ?float
    {
        if ($scores->isEmpty()) {
            return null;
        }

        return round((float) $scores->avg(fn (AssessmentScore $score) => $this->percentage($score)), 1);
    }

    private function line(Collection $labels, Collection $values, string $label, string $color): array
    {
        return [
            'meta' => ['hasData' => $values->contains(fn ($value) => $value !== null)],
            'type' => 'line',
            'data' => [
                'labels' => $labels->values()->all(),
                'datasets' => [[
                    'label' => $label,
                    'data' => $values->values()->all(),
                    'borderColor' => $color,
                    'backgroundColor' => $color.'22',
                    'pointBackgroundColor' => $color,
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 3,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 7,
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.35,
                    'spanGaps' => true,
                ]],
            ],
            'options' => [
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'max' => 100,
                        'ticks' => ['precision' => 0],
                    ],
                ],
                'plugins' => [
                    'legend' => ['display' => false],
                ],
            ],
        ];
    }

    private function bar(
        Collection $labels,
        Collection $values,
        string $label,
        string|array $color,
        bool $horizontal = false,
    ): array {
        return [
            'meta' => ['hasData' => $labels->isNotEmpty() && $values->isNotEmpty()],
            'type' => 'bar',
            'data' => [
                'labels' => $labels->values()->all(),
                'datasets' => [[
                    'label' => $label,
                    'data' => $values->values()->all(),
                    'backgroundColor' => $color,
                    'borderRadius' => 8,
                    'borderSkipped' => false,
                    'maxBarThickness' => 48,
                ]],
            ],
            'options' => [
                'indexAxis' => $horizontal ? 'y' : 'x',
                'scales' => [
                    $horizontal ? 'x' : 'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                    ],
                ],
                'plugins' => [
                    'legend' => ['display' => false],
                ],
            ],
        ];
    }
}
