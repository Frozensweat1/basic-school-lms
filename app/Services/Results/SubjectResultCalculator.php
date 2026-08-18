<?php

namespace App\Services\Results;

use App\Models\Assessment;
use App\Models\GradingScale;
use App\Models\SubjectResult;

class SubjectResultCalculator
{
    public function calculate(int $studentId, int $classSubjectId, int $termId): SubjectResult
    {
        $assessments = Assessment::with(['scores' => fn ($query) => $query->where('student_id', $studentId), 'component'])
            ->where('class_subject_id', $classSubjectId)
            ->where('term_id', $termId)
            ->where('status', 'published')
            ->get();

        $total = $assessments->sum(function (Assessment $assessment): float {
            $score = $assessment->scores->first()?->score;
            $weight = $assessment->component?->weight;

            if ($score === null || $weight === null || $assessment->max_score <= 0) {
                return 0;
            }

            return ((float) $score / (float) $assessment->max_score) * (float) $weight;
        });

        $schoolId = $assessments->first()?->classSubject->schoolClass->academicYear->school_id;
        $scale = $schoolId ? GradingScale::where('school_id', $schoolId)
            ->where('minimum', '<=', $total)
            ->where('maximum', '>=', $total)
            ->orderBy('sequence')
            ->first() : null;

        return SubjectResult::updateOrCreate(
            ['student_id' => $studentId, 'class_subject_id' => $classSubjectId, 'term_id' => $termId],
            ['total_score' => round($total, 2), 'grading_scale_id' => $scale?->id, 'grade' => $scale?->grade, 'status' => 'published'],
        );
    }
}
