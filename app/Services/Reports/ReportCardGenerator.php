<?php

namespace App\Services\Reports;

use App\Models\AttendanceRecord;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\Term;

class ReportCardGenerator
{
    public function generate(Student $student, Term $term, int $schoolClassId): ReportCard
    {
        $records = AttendanceRecord::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->where('school_class_id', $schoolClassId)
            ->get();

        $attendance = $records->isEmpty()
            ? null
            : round(($records->whereIn('status', ['present', 'late'])->count() / $records->count()) * 100, 2);

        return ReportCard::updateOrCreate(
            ['student_id' => $student->id, 'term_id' => $term->id],
            [
                'academic_year_id' => $term->academic_year_id,
                'school_class_id' => $schoolClassId,
                'attendance_percentage' => $attendance,
                'status' => 'draft',
                'generated_at' => now(),
            ],
        );
    }
}
