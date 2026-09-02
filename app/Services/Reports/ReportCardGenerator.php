<?php

namespace App\Services\Reports;

use App\Models\AttendanceRecord;
use App\Models\ReportCard;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Validation\ValidationException;

class ReportCardGenerator
{
    public function generate(Student $student, Term $term, int $schoolClassId): ReportCard
    {
        $term->loadMissing('academicYear');
        $schoolClass = SchoolClass::query()
            ->whereKey($schoolClassId)
            ->where('academic_year_id', $term->academic_year_id)
            ->first();

        if (! $schoolClass || (int) $student->school_id !== (int) $term->academicYear?->school_id) {
            throw ValidationException::withMessages(['generationClassId' => 'The student, class, and term must belong to the same school and academic year.']);
        }

        $isEnrolled = $schoolClass->enrollments()
            ->where('student_id', $student->id)
            ->enrolledDuring($term->starts_at, $term->ends_at)
            ->exists();
        if (! $isEnrolled) {
            throw ValidationException::withMessages(['generationStudentId' => 'The student was not enrolled in the selected class during this term.']);
        }

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
                'school_class_id' => $schoolClass->id,
                'attendance_percentage' => $attendance,
                'status' => 'draft',
                'published_at' => null,
                'pdf_path' => null,
                'generated_at' => now(),
            ],
        );
    }
}
