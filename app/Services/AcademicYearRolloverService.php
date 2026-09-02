<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AssessmentComponent;
use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Term;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicYearRolloverService
{
    public function __construct(private readonly AcademicYearActivationService $activationService) {}

    /**
     * Create a new year from an existing year's reusable academic structure.
     *
     * @param  array{
     *     name: string,
     *     starts_at: string,
     *     ends_at: string,
     *     copy_terms?: bool,
     *     copy_subjects?: bool,
     *     copy_teachers?: bool,
     *     prepare_students?: bool,
     *     activate?: bool,
     *     activate_first_term?: bool,
     *     promotions?: array<int|string, int|string|null>
     * }  $options
     * @return array{academic_year_id: int, terms: int, assessment_components: int, classes: int, subjects: int, teachers: int, students: int, graduated: int, transferred: int, activated_students: int}
     */
    public function rollover(AcademicYear $sourceYear, array $options): array
    {
        return DB::transaction(function () use ($sourceYear, $options): array {
            $source = AcademicYear::query()
                ->whereKey($sourceYear->id)
                ->lockForUpdate()
                ->firstOrFail();

            $source->load([
                'terms' => fn ($query) => $query->with('assessmentComponents')->orderBy('sequence')->orderBy('starts_at'),
                'classes' => fn ($query) => $query
                    ->where('status', 'active')
                    ->with([
                        'stream',
                        'classSubjects',
                        'teachers',
                        'enrollments' => fn ($enrollments) => $enrollments
                            ->with('student')
                            ->where('status', ClassEnrollment::STATUS_ACTIVE),
                    ])
                    ->orderBy('name')
                    ->orderBy('stream_id'),
            ]);

            $targetStartsAt = CarbonImmutable::parse($options['starts_at'])->startOfDay();
            $targetEndsAt = CarbonImmutable::parse($options['ends_at'])->startOfDay();

            if ($targetStartsAt->gte($targetEndsAt)) {
                throw ValidationException::withMessages([
                    'rolloverEndsAt' => 'The new academic year must end after it starts.',
                ]);
            }

            if ($targetStartsAt->lte($source->ends_at)) {
                throw ValidationException::withMessages([
                    'rolloverStartsAt' => 'The new academic year must start after the source academic year ends.',
                ]);
            }

            if (AcademicYear::query()
                ->where('school_id', $source->school_id)
                ->where('name', $options['name'])
                ->exists()) {
                throw ValidationException::withMessages([
                    'rolloverName' => 'An academic year with this name already exists.',
                ]);
            }

            $copyTerms = (bool) ($options['copy_terms'] ?? true);
            $copySubjects = (bool) ($options['copy_subjects'] ?? true);
            $copyTeachers = (bool) ($options['copy_teachers'] ?? false);
            $prepareStudents = (bool) ($options['prepare_students'] ?? false);
            $activate = (bool) ($options['activate'] ?? false);
            $activateFirstTerm = $activate && $copyTerms && (bool) ($options['activate_first_term'] ?? false);
            $promotions = collect($options['promotions'] ?? [])->mapWithKeys(
                fn ($decision, $classId) => [(int) $classId => (string) ($decision ?? '')],
            );

            $sourceClasses = $source->classes->keyBy('id');
            $activeEnrollmentCount = $sourceClasses->sum(fn (SchoolClass $class) => $class->enrollments->count());

            if ($activate && ! $prepareStudents && $activeEnrollmentCount > 0) {
                throw ValidationException::withMessages([
                    'rolloverPrepareStudents' => 'Prepare every active student placement before activating the new academic year.',
                ]);
            }

            if ($prepareStudents && $activeEnrollmentCount > 0) {
                $this->validatePromotionPlan($sourceClasses, $promotions, $activate);
            }

            $target = AcademicYear::create([
                'school_id' => $source->school_id,
                'name' => $options['name'],
                'starts_at' => $targetStartsAt,
                'ends_at' => $targetEndsAt,
                'is_active' => false,
                'is_locked' => false,
            ]);

            $counts = [
                'academic_year_id' => $target->id,
                'terms' => 0,
                'assessment_components' => 0,
                'classes' => 0,
                'subjects' => 0,
                'teachers' => 0,
                'students' => 0,
                'graduated' => 0,
                'transferred' => 0,
                'activated_students' => 0,
            ];

            if ($copyTerms) {
                foreach ($source->terms as $sourceTerm) {
                    [$termStartsAt, $termEndsAt] = $this->shiftTermDates(
                        $source,
                        $sourceTerm,
                        $targetStartsAt,
                        $targetEndsAt,
                    );

                    $targetTerm = Term::create([
                        'academic_year_id' => $target->id,
                        'name' => $sourceTerm->name,
                        'sequence' => $sourceTerm->sequence,
                        'starts_at' => $termStartsAt,
                        'ends_at' => $termEndsAt,
                        'is_active' => false,
                        'is_locked' => false,
                    ]);

                    $counts['terms']++;

                    foreach ($sourceTerm->assessmentComponents as $component) {
                        AssessmentComponent::create([
                            'term_id' => $targetTerm->id,
                            'name' => $component->name,
                            'weight' => $component->weight,
                            'sequence' => $component->sequence,
                        ]);
                        $counts['assessment_components']++;
                    }
                }
            }

            $activeTeacherIds = Teacher::query()
                ->where('school_id', $source->school_id)
                ->where('status', 'active')
                ->pluck('id')
                ->flip();
            $targetClasses = [];

            foreach ($sourceClasses as $sourceClass) {
                $targetClass = SchoolClass::create([
                    'academic_year_id' => $target->id,
                    'stream_id' => $sourceClass->stream_id,
                    'name' => $sourceClass->name,
                    'code' => $sourceClass->code,
                    'status' => 'active',
                ]);

                $targetClasses[$sourceClass->id] = $targetClass;
                $counts['classes']++;

                if ($copySubjects) {
                    foreach ($sourceClass->classSubjects as $sourceSubject) {
                        $teacherId = $copyTeachers && $sourceSubject->teacher_id && $activeTeacherIds->has($sourceSubject->teacher_id)
                            ? $sourceSubject->teacher_id
                            : null;

                        ClassSubject::create([
                            'school_class_id' => $targetClass->id,
                            'subject_id' => $sourceSubject->subject_id,
                            'teacher_id' => $teacherId,
                        ]);

                        $counts['subjects']++;
                    }
                }

                if ($copyTeachers) {
                    foreach ($sourceClass->teachers as $teacher) {
                        if (! $activeTeacherIds->has($teacher->id)) {
                            continue;
                        }

                        $targetClass->teachers()->attach($teacher->id, [
                            'role' => $teacher->pivot->role ?? 'teacher',
                        ]);
                        $counts['teachers']++;
                    }
                }
            }

            if ($prepareStudents) {
                $this->prepareStudentTransitions(
                    $source,
                    $sourceClasses,
                    $targetClasses,
                    $promotions,
                    $targetStartsAt,
                    $counts,
                );
            }

            if ($activate) {
                $counts['activated_students'] = $this->activationService->activate($target, $activateFirstTerm);
            }

            return $counts;
        });
    }

    private function validatePromotionPlan($sourceClasses, $promotions, bool $activate): void
    {
        $validClassIds = $sourceClasses->keys()->map(fn ($id) => (string) $id)->all();

        foreach ($sourceClasses as $sourceClass) {
            if ($sourceClass->enrollments->isEmpty()) {
                continue;
            }

            $decision = $promotions->get($sourceClass->id, '');

            if ($decision === '') {
                throw ValidationException::withMessages([
                    "rolloverPromotions.{$sourceClass->id}" => 'Choose a destination or outcome for this class.',
                ]);
            }

            if (in_array($decision, ['graduate', 'transfer'], true) && ! $activate) {
                throw ValidationException::withMessages([
                    "rolloverPromotions.{$sourceClass->id}" => 'Graduation and transfer outcomes can only be applied when the new year is activated.',
                ]);
            }

            if (! in_array($decision, ['graduate', 'transfer'], true)
                && ! in_array($decision, $validClassIds, true)) {
                throw ValidationException::withMessages([
                    "rolloverPromotions.{$sourceClass->id}" => 'The selected destination class is not part of the source academic year.',
                ]);
            }
        }
    }

    private function prepareStudentTransitions(
        AcademicYear $source,
        $sourceClasses,
        array $targetClasses,
        $promotions,
        CarbonImmutable $targetStartsAt,
        array &$counts,
    ): void {
        $handledStudentIds = [];

        foreach ($sourceClasses as $sourceClass) {
            $decision = $promotions->get($sourceClass->id, '');

            foreach ($sourceClass->enrollments as $sourceEnrollment) {
                if (! $sourceEnrollment->student
                    || (int) $sourceEnrollment->student->school_id !== (int) $source->school_id
                    || ! in_array($sourceEnrollment->student->status, ['active', 'suspended'], true)) {
                    throw ValidationException::withMessages([
                        "rolloverPromotions.{$sourceClass->id}" => 'This class contains a learner who is no longer eligible for rollover. Review the learner record first.',
                    ]);
                }

                if (isset($handledStudentIds[$sourceEnrollment->student_id])) {
                    throw ValidationException::withMessages([
                        'rolloverPromotions' => 'A student has more than one active enrolment in the source academic year. Resolve the duplicate before continuing.',
                    ]);
                }

                $handledStudentIds[$sourceEnrollment->student_id] = true;

                if ($decision === 'graduate' || $decision === 'transfer') {
                    $sourceEnrollment->update([
                        'status' => ClassEnrollment::STATUS_COMPLETED,
                        'left_at' => $source->ends_at,
                    ]);

                    Student::query()
                        ->whereKey($sourceEnrollment->student_id)
                        ->where('school_id', $source->school_id)
                        ->update(['status' => $decision === 'graduate' ? 'graduated' : 'transferred']);

                    $counts[$decision === 'graduate' ? 'graduated' : 'transferred']++;

                    continue;
                }

                $destinationSourceClassId = (int) $decision;
                $destinationClass = $targetClasses[$destinationSourceClassId] ?? null;

                if (! $destinationClass) {
                    throw ValidationException::withMessages([
                        "rolloverPromotions.{$sourceClass->id}" => 'The destination class could not be prepared.',
                    ]);
                }

                ClassEnrollment::updateOrCreate(
                    [
                        'school_class_id' => $destinationClass->id,
                        'student_id' => $sourceEnrollment->student_id,
                    ],
                    [
                        'enrolled_at' => $targetStartsAt,
                        'left_at' => null,
                        'status' => ClassEnrollment::STATUS_PENDING,
                    ],
                );

                $counts['students']++;
            }
        }
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function shiftTermDates(
        AcademicYear $source,
        Term $sourceTerm,
        CarbonImmutable $targetStartsAt,
        CarbonImmutable $targetEndsAt,
    ): array {
        $sourceStartsAt = CarbonImmutable::instance($source->starts_at);
        $startOffset = (int) max(0, $sourceStartsAt->diffInDays($sourceTerm->starts_at, false));
        $endOffset = (int) max($startOffset + 1, $sourceStartsAt->diffInDays($sourceTerm->ends_at, false));

        $termStartsAt = $targetStartsAt->addDays($startOffset);
        $termEndsAt = $targetStartsAt->addDays($endOffset);

        if ($termStartsAt->gte($targetEndsAt)) {
            $termStartsAt = $targetEndsAt->subDay();
        }

        if ($termEndsAt->gt($targetEndsAt)) {
            $termEndsAt = $targetEndsAt;
        }

        if ($termEndsAt->lte($termStartsAt)) {
            $termEndsAt = $termStartsAt->addDay();
        }

        return [$termStartsAt, $termEndsAt];
    }
}
