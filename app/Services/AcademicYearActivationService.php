<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicYearActivationService
{
    /**
     * Activate an academic year and apply any student placements prepared for it.
     *
     * @return int Number of pending enrolments activated.
     */
    public function activate(AcademicYear $academicYear, bool $activateFirstTerm = false): int
    {
        return DB::transaction(function () use ($academicYear, $activateFirstTerm): int {
            $target = AcademicYear::query()->lockForUpdate()->findOrFail($academicYear->id);

            $pendingEnrollments = ClassEnrollment::query()
                ->with(['schoolClass.academicYear', 'student'])
                ->where('status', ClassEnrollment::STATUS_PENDING)
                ->whereHas('schoolClass', fn ($query) => $query->where('academic_year_id', $target->id))
                ->lockForUpdate()
                ->get();

            $duplicateStudent = $pendingEnrollments
                ->groupBy('student_id')
                ->first(fn ($enrollments) => $enrollments->count() > 1);

            if ($duplicateStudent) {
                throw ValidationException::withMessages([
                    'rolloverPromotions' => 'A student has more than one pending class placement for the new academic year.',
                ]);
            }

            $ineligibleEnrollment = $pendingEnrollments->first(fn (ClassEnrollment $enrollment) => ! $enrollment->student
                || (int) $enrollment->student->school_id !== (int) $target->school_id
                || ! in_array($enrollment->student->status, ['active', 'suspended'], true)
                || $enrollment->schoolClass->status !== 'active');

            if ($ineligibleEnrollment) {
                throw ValidationException::withMessages([
                    'rolloverPromotions' => 'A pending placement belongs to an ineligible learner or archived class. Review the rollover plan before activation.',
                ]);
            }

            $hasActiveTargetConflict = $pendingEnrollments->isNotEmpty()
                && ClassEnrollment::query()
                    ->whereIn('student_id', $pendingEnrollments->pluck('student_id'))
                    ->where('status', ClassEnrollment::STATUS_ACTIVE)
                    ->whereHas('schoolClass', fn ($query) => $query->where('academic_year_id', $target->id))
                    ->exists();

            if ($hasActiveTargetConflict) {
                throw ValidationException::withMessages([
                    'rolloverPromotions' => 'A student already has an active class in the target academic year.',
                ]);
            }

            AcademicYear::query()
                ->where('school_id', $target->school_id)
                ->whereKeyNot($target->id)
                ->update(['is_active' => false]);

            Term::query()
                ->whereHas('academicYear', fn ($query) => $query->where('school_id', $target->school_id))
                ->where('academic_year_id', '!=', $target->id)
                ->update(['is_active' => false]);

            foreach ($pendingEnrollments as $pendingEnrollment) {
                $currentEnrollments = ClassEnrollment::query()
                    ->with('schoolClass.academicYear')
                    ->where('student_id', $pendingEnrollment->student_id)
                    ->where('status', ClassEnrollment::STATUS_ACTIVE)
                    ->whereHas('schoolClass.academicYear', function ($query) use ($target): void {
                        $query->where('school_id', $target->school_id)
                            ->whereKeyNot($target->id);
                    })
                    ->lockForUpdate()
                    ->get();

                foreach ($currentEnrollments as $currentEnrollment) {
                    $yearEnd = $currentEnrollment->schoolClass->academicYear->ends_at;
                    $dayBeforeTarget = $target->starts_at->copy()->subDay();

                    $currentEnrollment->update([
                        'status' => ClassEnrollment::STATUS_COMPLETED,
                        'left_at' => $yearEnd->lte($dayBeforeTarget) ? $yearEnd : $dayBeforeTarget,
                    ]);
                }

                $pendingEnrollment->update([
                    'status' => ClassEnrollment::STATUS_ACTIVE,
                    'enrolled_at' => $target->starts_at,
                    'left_at' => null,
                ]);
            }

            $target->update(['is_active' => true]);

            if ($activateFirstTerm) {
                Term::query()->where('academic_year_id', $target->id)->update(['is_active' => false]);
                Term::query()
                    ->where('academic_year_id', $target->id)
                    ->orderBy('sequence')
                    ->orderBy('starts_at')
                    ->first()
                    ?->update(['is_active' => true]);
            }

            return $pendingEnrollments->count();
        });
    }
}
