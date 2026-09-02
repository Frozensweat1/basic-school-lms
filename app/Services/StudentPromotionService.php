<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentPromotionService
{
    public const OUTCOME_GRADUATE = 'graduate';

    public const OUTCOME_TRANSFER = 'transfer';

    private const MAX_BATCH_SIZE = 500;

    /**
     * Promote or prepare one or more learners for a later academic year.
     *
     * The array key is the student ID. Its value is either a target class ID,
     * "graduate", or "transfer".
     *
     * @param  array<int|string, int|string>  $transitions
     * @return array{processed: int, pending: int, activated: int, graduated: int, transferred: int, unchanged: int}
     */
    public function process(
        AcademicYear $sourceYear,
        SchoolClass $sourceClass,
        AcademicYear $targetYear,
        array $transitions,
        string $effectiveDate,
        ?string $notes = null,
    ): array {
        return DB::transaction(function () use ($sourceYear, $sourceClass, $targetYear, $transitions, $effectiveDate, $notes): array {
            [$source, $target] = $this->lockYears($sourceYear->id, $targetYear->id);
            $this->validateYearPair($source, $target);

            $lockedSourceClass = SchoolClass::query()
                ->whereKey($sourceClass->id)
                ->where('academic_year_id', $source->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $lockedSourceClass) {
                throw ValidationException::withMessages([
                    'sourceClassId' => 'The source class is unavailable or does not belong to the selected academic year.',
                ]);
            }

            $effectiveAt = $this->effectiveDate($effectiveDate, $target);
            $normalisedTransitions = $this->normaliseTransitions($transitions);
            $studentIds = $normalisedTransitions->keys()->all();

            if (! $target->is_active && $normalisedTransitions->contains(
                fn (string $decision) => in_array($decision, [self::OUTCOME_GRADUATE, self::OUTCOME_TRANSFER], true),
            )) {
                throw ValidationException::withMessages([
                    'studentDestinations' => 'Graduation and transfer outcomes can only be applied when the target academic year is active.',
                ]);
            }

            $students = Student::query()
                ->where('school_id', $source->school_id)
                ->whereIn('id', $studentIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($students->count() !== count($studentIds)) {
                throw ValidationException::withMessages([
                    'selectedStudentIds' => 'One or more selected learners are unavailable or belong to another school.',
                ]);
            }

            $ineligible = $students->first(fn (Student $student) => ! in_array($student->status, ['active', 'suspended'], true));

            if ($ineligible) {
                throw ValidationException::withMessages([
                    'selectedStudentIds' => "{$ineligible->first_name} {$ineligible->last_name} is no longer eligible for promotion.",
                ]);
            }

            $sourceEnrollments = ClassEnrollment::query()
                ->where('school_class_id', $lockedSourceClass->id)
                ->whereIn('student_id', $studentIds)
                ->whereIn('status', [ClassEnrollment::STATUS_ACTIVE, ClassEnrollment::STATUS_COMPLETED])
                ->lockForUpdate()
                ->get()
                ->keyBy('student_id');

            if ($sourceEnrollments->count() !== count($studentIds)) {
                throw ValidationException::withMessages([
                    'selectedStudentIds' => 'Every selected learner must have an active enrollment in the source class.',
                ]);
            }

            $activeSchoolEnrollments = ClassEnrollment::query()
                ->whereIn('student_id', $studentIds)
                ->where('status', ClassEnrollment::STATUS_ACTIVE)
                ->whereHas('schoolClass.academicYear', fn ($years) => $years->where('school_id', $source->school_id))
                ->orderBy('student_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->groupBy('student_id');

            foreach ($sourceEnrollments as $sourceEnrollment) {
                if ($sourceEnrollment->status !== ClassEnrollment::STATUS_ACTIVE) {
                    continue;
                }

                $activeEnrollments = $activeSchoolEnrollments->get($sourceEnrollment->student_id, collect());

                if ($activeEnrollments->count() !== 1
                    || (int) $activeEnrollments->first()->school_class_id !== (int) $lockedSourceClass->id) {
                    throw ValidationException::withMessages([
                        'selectedStudentIds' => 'A selected learner has more than one active class. Resolve the enrollment conflict before continuing.',
                    ]);
                }
            }

            $destinationIds = $normalisedTransitions
                ->filter(fn (string $decision) => ctype_digit($decision) && (int) $decision > 0)
                ->map(fn (string $decision) => (int) $decision)
                ->unique()
                ->values();

            $destinationClasses = SchoolClass::query()
                ->where('academic_year_id', $target->id)
                ->where('status', 'active')
                ->whereIn('id', $destinationIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($destinationClasses->count() !== $destinationIds->count()) {
                throw ValidationException::withMessages([
                    'bulkDestination' => 'One or more destination classes are unavailable or do not belong to the target academic year.',
                ]);
            }

            $targetEnrollments = ClassEnrollment::query()
                ->whereIn('student_id', $studentIds)
                ->whereHas('schoolClass', fn ($classes) => $classes->where('academic_year_id', $target->id))
                ->lockForUpdate()
                ->get()
                ->groupBy('student_id');

            if ($targetEnrollments->contains(fn (Collection $enrollments) => $enrollments->count() > 1)) {
                throw ValidationException::withMessages([
                    'selectedStudentIds' => 'A selected learner has more than one placement in the target academic year. Resolve the conflict before continuing.',
                ]);
            }

            $counts = [
                'processed' => 0,
                'pending' => 0,
                'activated' => 0,
                'graduated' => 0,
                'transferred' => 0,
                'unchanged' => 0,
            ];

            foreach ($normalisedTransitions as $studentId => $decision) {
                /** @var Student $student */
                $student = $students->get($studentId);
                /** @var ClassEnrollment $sourceEnrollment */
                $sourceEnrollment = $sourceEnrollments->get($studentId);
                /** @var ClassEnrollment|null $targetEnrollment */
                $targetEnrollment = $targetEnrollments->get($studentId)?->first();

                if ($sourceEnrollment->status === ClassEnrollment::STATUS_COMPLETED) {
                    if ($this->isAlreadyApplied($student, $targetEnrollment, $decision, $target->is_active)) {
                        $counts['unchanged']++;
                        $counts['processed']++;

                        continue;
                    }

                    throw ValidationException::withMessages([
                        'selectedStudentIds' => "{$student->first_name} {$student->last_name} has already left the source class.",
                    ]);
                }

                if (in_array($decision, [self::OUTCOME_GRADUATE, self::OUTCOME_TRANSFER], true)) {
                    $this->applyTerminalOutcome(
                        $student,
                        $sourceEnrollment,
                        $targetEnrollment,
                        $decision,
                        $source,
                        $target,
                        $effectiveAt,
                        $notes,
                    );

                    $counts[$decision === self::OUTCOME_GRADUATE ? 'graduated' : 'transferred']++;
                    $counts['processed']++;

                    continue;
                }

                /** @var SchoolClass $destinationClass */
                $destinationClass = $destinationClasses->get((int) $decision);
                $result = $this->applyClassPlacement(
                    $student,
                    $sourceEnrollment,
                    $targetEnrollment,
                    $lockedSourceClass,
                    $destinationClass,
                    $source,
                    $target,
                    $effectiveAt,
                    $notes,
                );

                $counts[$result]++;
                $counts['processed']++;
            }

            return $counts;
        });
    }

    public function cancelPendingPlacement(
        AcademicYear $sourceYear,
        SchoolClass $sourceClass,
        AcademicYear $targetYear,
        int $pendingEnrollmentId,
    ): void {
        DB::transaction(function () use ($sourceYear, $sourceClass, $targetYear, $pendingEnrollmentId): void {
            [$source, $target] = $this->lockYears($sourceYear->id, $targetYear->id);
            $this->validateYearPair($source, $target);

            $lockedSourceClass = SchoolClass::query()
                ->whereKey($sourceClass->id)
                ->where('academic_year_id', $source->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $lockedSourceClass) {
                throw ValidationException::withMessages(['sourceClassId' => 'The source class is unavailable.']);
            }

            $pendingEnrollment = ClassEnrollment::query()
                ->with(['student', 'schoolClass'])
                ->whereKey($pendingEnrollmentId)
                ->where('status', ClassEnrollment::STATUS_PENDING)
                ->whereHas('schoolClass', fn ($classes) => $classes
                    ->where('academic_year_id', $target->id)
                    ->where('status', 'active'))
                ->lockForUpdate()
                ->first();

            $sourceEnrollment = $pendingEnrollment
                ? ClassEnrollment::query()
                    ->where('school_class_id', $lockedSourceClass->id)
                    ->where('student_id', $pendingEnrollment->student_id)
                    ->where('status', ClassEnrollment::STATUS_ACTIVE)
                    ->lockForUpdate()
                    ->first()
                : null;

            if (! $pendingEnrollment
                || ! $pendingEnrollment->student
                || (int) $pendingEnrollment->student->school_id !== (int) $source->school_id
                || ! $sourceEnrollment) {
                throw ValidationException::withMessages([
                    'selectedStudentIds' => 'The pending placement cannot be cancelled from this source class.',
                ]);
            }

            $oldValues = [
                'source_class_id' => $lockedSourceClass->id,
                'target_class_id' => $pendingEnrollment->school_class_id,
                'target_academic_year_id' => $target->id,
                'status' => ClassEnrollment::STATUS_PENDING,
            ];
            $student = $pendingEnrollment->student;
            $pendingEnrollment->delete();

            app(AuditLogger::class)->record(
                'student.promotion.cancelled',
                $student,
                $oldValues,
                ['status' => 'cancelled'],
                (int) $source->school_id,
            );
        });
    }

    /** @return array{0: AcademicYear, 1: AcademicYear} */
    private function lockYears(int $sourceYearId, int $targetYearId): array
    {
        $years = AcademicYear::query()
            ->whereIn('id', [$sourceYearId, $targetYearId])
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $source = $years->get($sourceYearId);
        $target = $years->get($targetYearId);

        if (! $source || ! $target) {
            throw ValidationException::withMessages([
                'targetYearId' => 'The selected academic years are unavailable.',
            ]);
        }

        return [$source, $target];
    }

    private function validateYearPair(AcademicYear $source, AcademicYear $target): void
    {
        if ((int) $source->school_id !== (int) $target->school_id) {
            throw ValidationException::withMessages([
                'targetYearId' => 'The source and target academic years must belong to the same school.',
            ]);
        }

        if ($source->id === $target->id || $target->starts_at->lte($source->ends_at)) {
            throw ValidationException::withMessages([
                'targetYearId' => 'Choose a later academic year that starts after the source year ends.',
            ]);
        }

        if ($source->is_locked) {
            throw ValidationException::withMessages([
                'sourceYearId' => 'The source academic year is locked and cannot be changed.',
            ]);
        }

        if ($target->is_locked) {
            throw ValidationException::withMessages([
                'targetYearId' => 'The target academic year is locked and cannot receive placements.',
            ]);
        }
    }

    private function effectiveDate(string $effectiveDate, AcademicYear $target): CarbonImmutable
    {
        try {
            $date = CarbonImmutable::parse($effectiveDate)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages(['effectiveDate' => 'Enter a valid effective date.']);
        }

        if ($date->lt($target->starts_at) || $date->gt($target->ends_at)) {
            throw ValidationException::withMessages([
                'effectiveDate' => 'The effective date must fall within the target academic year.',
            ]);
        }

        return $date;
    }

    /** @param array<int|string, int|string> $transitions */
    private function normaliseTransitions(array $transitions): Collection
    {
        if ($transitions === [] || count($transitions) > self::MAX_BATCH_SIZE) {
            throw ValidationException::withMessages([
                'selectedStudentIds' => 'Select between 1 and '.self::MAX_BATCH_SIZE.' learners at a time.',
            ]);
        }

        $normalised = collect($transitions)->mapWithKeys(function ($decision, $studentId): array {
            $id = filter_var($studentId, FILTER_VALIDATE_INT);
            $value = trim((string) $decision);

            if (! $id || ($value !== self::OUTCOME_GRADUATE
                && $value !== self::OUTCOME_TRANSFER
                && (! ctype_digit($value) || (int) $value < 1))) {
                throw ValidationException::withMessages([
                    'studentDestinations' => 'Every selected learner needs a valid destination or outcome.',
                ]);
            }

            return [(int) $id => $value];
        });

        if ($normalised->count() !== count($transitions)) {
            throw ValidationException::withMessages([
                'selectedStudentIds' => 'The learner selection contains duplicate records.',
            ]);
        }

        return $normalised;
    }

    private function isAlreadyApplied(
        Student $student,
        ?ClassEnrollment $targetEnrollment,
        string $decision,
        bool $targetIsActive,
    ): bool {
        if ($decision === self::OUTCOME_GRADUATE) {
            return $student->status === 'graduated' && ! $targetEnrollment;
        }

        if ($decision === self::OUTCOME_TRANSFER) {
            return $student->status === 'transferred' && ! $targetEnrollment;
        }

        return $targetEnrollment
            && (int) $targetEnrollment->school_class_id === (int) $decision
            && $targetEnrollment->status === ($targetIsActive
                ? ClassEnrollment::STATUS_ACTIVE
                : ClassEnrollment::STATUS_PENDING);
    }

    private function applyTerminalOutcome(
        Student $student,
        ClassEnrollment $sourceEnrollment,
        ?ClassEnrollment $targetEnrollment,
        string $decision,
        AcademicYear $source,
        AcademicYear $target,
        CarbonImmutable $effectiveAt,
        ?string $notes,
    ): void {
        if ($targetEnrollment && $targetEnrollment->status !== ClassEnrollment::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'selectedStudentIds' => "{$student->first_name} {$student->last_name} already has a completed or active target-year placement.",
            ]);
        }

        $targetEnrollment?->delete();
        $sourceEnrollment->update([
            'status' => ClassEnrollment::STATUS_COMPLETED,
            'left_at' => $this->sourceLeaveDate($sourceEnrollment, $source, $effectiveAt),
        ]);
        $student->update([
            'status' => $decision === self::OUTCOME_GRADUATE ? 'graduated' : 'transferred',
        ]);

        app(AuditLogger::class)->record(
            $decision === self::OUTCOME_GRADUATE ? 'student.graduated' : 'student.transferred',
            $student,
            [
                'source_class_id' => $sourceEnrollment->school_class_id,
                'source_academic_year_id' => $source->id,
                'status' => 'active',
            ],
            [
                'target_academic_year_id' => $target->id,
                'outcome' => $decision,
                'effective_date' => $effectiveAt->toDateString(),
                'notes' => $notes,
            ],
            (int) $source->school_id,
        );
    }

    private function applyClassPlacement(
        Student $student,
        ClassEnrollment $sourceEnrollment,
        ?ClassEnrollment $targetEnrollment,
        SchoolClass $sourceClass,
        SchoolClass $destinationClass,
        AcademicYear $source,
        AcademicYear $target,
        CarbonImmutable $effectiveAt,
        ?string $notes,
    ): string {
        if ($targetEnrollment && in_array($targetEnrollment->status, [ClassEnrollment::STATUS_ACTIVE, ClassEnrollment::STATUS_COMPLETED], true)) {
            if ($targetEnrollment->status !== ClassEnrollment::STATUS_ACTIVE
                || (int) $targetEnrollment->school_class_id !== (int) $destinationClass->id
                || ! $target->is_active) {
                throw ValidationException::withMessages([
                    'selectedStudentIds' => "{$student->first_name} {$student->last_name} already has a conflicting target-year placement.",
                ]);
            }
        }

        $status = $target->is_active ? ClassEnrollment::STATUS_ACTIVE : ClassEnrollment::STATUS_PENDING;

        if ($targetEnrollment) {
            $targetEnrollment->update([
                'school_class_id' => $destinationClass->id,
                'enrolled_at' => $effectiveAt,
                'left_at' => null,
                'status' => $status,
            ]);
        } else {
            $targetEnrollment = ClassEnrollment::create([
                'school_class_id' => $destinationClass->id,
                'student_id' => $student->id,
                'enrolled_at' => $effectiveAt,
                'left_at' => null,
                'status' => $status,
            ]);
        }

        if ($target->is_active) {
            $sourceEnrollment->update([
                'status' => ClassEnrollment::STATUS_COMPLETED,
                'left_at' => $this->sourceLeaveDate($sourceEnrollment, $source, $effectiveAt),
            ]);
        }

        app(AuditLogger::class)->record(
            $target->is_active ? 'student.promotion.completed' : 'student.promotion.prepared',
            $student,
            [
                'source_class_id' => $sourceClass->id,
                'source_academic_year_id' => $source->id,
            ],
            [
                'target_class_id' => $destinationClass->id,
                'target_academic_year_id' => $target->id,
                'enrollment_id' => $targetEnrollment->id,
                'status' => $status,
                'effective_date' => $effectiveAt->toDateString(),
                'notes' => $notes,
            ],
            (int) $source->school_id,
        );

        return $target->is_active ? 'activated' : 'pending';
    }

    private function sourceLeaveDate(
        ClassEnrollment $sourceEnrollment,
        AcademicYear $source,
        CarbonImmutable $effectiveAt,
    ): CarbonImmutable {
        $leaveAt = $effectiveAt->subDay();

        if ($leaveAt->gt($source->ends_at)) {
            $leaveAt = CarbonImmutable::instance($source->ends_at);
        }

        if ($leaveAt->lt($sourceEnrollment->enrolled_at)) {
            $leaveAt = CarbonImmutable::instance($sourceEnrollment->enrolled_at);
        }

        return $leaveAt;
    }
}
