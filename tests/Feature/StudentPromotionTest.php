<?php

namespace Tests\Feature;

use App\Livewire\LMS\Students\Promotions\Index;
use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademicYearActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_access_the_student_promotions_module(): void
    {
        $setup = $this->promotionSetup();

        $this->actingAs($setup['admin'])
            ->get(route('lms.students.promotions.index'))
            ->assertOk();

        $this->actingAs($setup['teacher'])
            ->get(route('lms.students.promotions.index'))
            ->assertForbidden();

        Livewire::actingAs($setup['admin'])
            ->test(Index::class)
            ->assertStatus(200);
    }

    public function test_admin_can_prepare_a_bulk_promotion_into_an_inactive_target_year(): void
    {
        $setup = $this->promotionSetup();

        Livewire::actingAs($setup['admin'])
            ->test(Index::class)
            ->set('sourceYearId', (string) $setup['sourceYear']->id)
            ->set('sourceClassId', (string) $setup['sourceClass']->id)
            ->set('targetYearId', (string) $setup['targetYear']->id)
            ->set('bulkDestination', (string) $setup['targetClass']->id)
            ->set('selectedStudentIds', [
                $setup['ama']->id,
                $setup['kwame']->id,
            ])
            ->set('effectiveDate', '2027-09-01')
            ->call('reviewSelected')
            ->assertHasNoErrors()
            ->assertSet('showConfirmationModal', true)
            ->call('processPromotions')
            ->assertHasNoErrors()
            ->assertSet('showConfirmationModal', false);

        foreach ([$setup['ama'], $setup['kwame']] as $student) {
            $this->assertDatabaseHas('class_enrollments', [
                'school_class_id' => $setup['sourceClass']->id,
                'student_id' => $student->id,
                'status' => ClassEnrollment::STATUS_ACTIVE,
                'left_at' => null,
            ]);
            $this->assertDatabaseHas('class_enrollments', [
                'school_class_id' => $setup['targetClass']->id,
                'student_id' => $student->id,
                'status' => ClassEnrollment::STATUS_PENDING,
                'enrolled_at' => '2027-09-01 00:00:00',
                'left_at' => null,
            ]);
        }

        $this->assertDatabaseMissing('class_enrollments', [
            'school_class_id' => $setup['targetClass']->id,
            'student_id' => $setup['unselected']->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $setup['school']->id,
            'event' => 'student.promotion.prepared',
            'auditable_type' => Student::class,
            'auditable_id' => $setup['ama']->id,
        ]);
    }

    public function test_prepared_promotions_are_applied_when_the_target_year_is_activated(): void
    {
        $setup = $this->promotionSetup();

        Livewire::actingAs($setup['admin'])
            ->test(Index::class)
            ->set('sourceYearId', (string) $setup['sourceYear']->id)
            ->set('sourceClassId', (string) $setup['sourceClass']->id)
            ->set('targetYearId', (string) $setup['targetYear']->id)
            ->set('bulkDestination', (string) $setup['targetClass']->id)
            ->set('selectedStudentIds', [$setup['ama']->id])
            ->set('effectiveDate', '2027-09-01')
            ->call('reviewSelected')
            ->call('processPromotions')
            ->assertHasNoErrors();

        $activated = app(AcademicYearActivationService::class)->activate($setup['targetYear']);

        $this->assertSame(1, $activated);
        $this->assertTrue($setup['targetYear']->fresh()->is_active);
        $this->assertFalse($setup['sourceYear']->fresh()->is_active);
        $this->assertDatabaseHas('class_enrollments', [
            'school_class_id' => $setup['sourceClass']->id,
            'student_id' => $setup['ama']->id,
            'status' => ClassEnrollment::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('class_enrollments', [
            'school_class_id' => $setup['targetClass']->id,
            'student_id' => $setup['ama']->id,
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_can_search_and_filter_pending_promotion_plans(): void
    {
        $setup = $this->promotionSetup();

        $component = Livewire::actingAs($setup['admin'])
            ->test(Index::class)
            ->set('sourceYearId', (string) $setup['sourceYear']->id)
            ->set('sourceClassId', (string) $setup['sourceClass']->id)
            ->set('targetYearId', (string) $setup['targetYear']->id)
            ->set('bulkDestination', (string) $setup['targetClass']->id)
            ->set('selectedStudentIds', [$setup['ama']->id])
            ->set('effectiveDate', '2027-09-01')
            ->call('reviewSelected')
            ->call('processPromotions')
            ->assertHasNoErrors();

        $component
            ->set('search', 'PRO-002')
            ->assertSee('STU-PRO-002')
            ->assertDontSee('STU-PRO-001')
            ->set('search', '')
            ->set('placementFilter', 'planned')
            ->assertSee('STU-PRO-001')
            ->assertDontSee('STU-PRO-002');
    }

    public function test_admin_can_immediately_promote_one_student_into_the_active_target_year(): void
    {
        $setup = $this->promotionSetup(targetIsActive: true);

        Livewire::actingAs($setup['admin'])
            ->test(Index::class)
            ->set('sourceYearId', (string) $setup['sourceYear']->id)
            ->set('sourceClassId', (string) $setup['sourceClass']->id)
            ->set('targetYearId', (string) $setup['targetYear']->id)
            ->set('studentDestinations.'.$setup['ama']->id, (string) $setup['targetClass']->id)
            ->set('effectiveDate', '2027-09-01')
            ->call('reviewOne', $setup['ama']->id)
            ->assertHasNoErrors()
            ->assertSet('showConfirmationModal', true)
            ->call('processPromotions')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('class_enrollments', [
            'school_class_id' => $setup['sourceClass']->id,
            'student_id' => $setup['ama']->id,
            'status' => ClassEnrollment::STATUS_COMPLETED,
            'left_at' => '2027-07-31 00:00:00',
        ]);
        $this->assertDatabaseHas('class_enrollments', [
            'school_class_id' => $setup['targetClass']->id,
            'student_id' => $setup['ama']->id,
            'status' => ClassEnrollment::STATUS_ACTIVE,
            'enrolled_at' => '2027-09-01 00:00:00',
            'left_at' => null,
        ]);
    }

    public function test_pending_placement_can_be_reassigned_and_cancelled_without_changing_the_source_enrollment(): void
    {
        $setup = $this->promotionSetup();
        $component = Livewire::actingAs($setup['admin'])
            ->test(Index::class)
            ->set('sourceYearId', (string) $setup['sourceYear']->id)
            ->set('sourceClassId', (string) $setup['sourceClass']->id)
            ->set('targetYearId', (string) $setup['targetYear']->id)
            ->set('studentDestinations.'.$setup['ama']->id, (string) $setup['targetClass']->id)
            ->set('effectiveDate', '2027-09-01')
            ->call('reviewOne', $setup['ama']->id)
            ->call('processPromotions')
            ->assertHasNoErrors();

        $firstPlacement = ClassEnrollment::query()
            ->where('student_id', $setup['ama']->id)
            ->where('status', ClassEnrollment::STATUS_PENDING)
            ->firstOrFail();

        $component
            ->set('studentDestinations.'.$setup['ama']->id, (string) $setup['alternateTargetClass']->id)
            ->call('reviewOne', $setup['ama']->id)
            ->call('processPromotions')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('class_enrollments', [
            'school_class_id' => $setup['targetClass']->id,
            'student_id' => $setup['ama']->id,
            'status' => ClassEnrollment::STATUS_PENDING,
        ]);
        $replacement = ClassEnrollment::query()
            ->where('student_id', $setup['ama']->id)
            ->where('school_class_id', $setup['alternateTargetClass']->id)
            ->where('status', ClassEnrollment::STATUS_PENDING)
            ->firstOrFail();

        $this->assertSame($firstPlacement->id, $replacement->id);

        $component
            ->call('cancelPendingPlacement', $replacement->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('class_enrollments', ['id' => $replacement->id]);
        $this->assertDatabaseHas('class_enrollments', [
            'school_class_id' => $setup['sourceClass']->id,
            'student_id' => $setup['ama']->id,
            'status' => ClassEnrollment::STATUS_ACTIVE,
            'left_at' => null,
        ]);
    }

    public function test_confirmation_revalidates_tampered_student_ids_and_rolls_back_the_whole_batch(): void
    {
        $setup = $this->promotionSetup();
        $otherSchool = School::create(['name' => 'Other School', 'code' => 'OTHER']);
        $outsider = $this->createStudent($otherSchool, 'OUT-001');

        $component = Livewire::actingAs($setup['admin'])
            ->test(Index::class)
            ->set('sourceYearId', (string) $setup['sourceYear']->id)
            ->set('sourceClassId', (string) $setup['sourceClass']->id)
            ->set('targetYearId', (string) $setup['targetYear']->id)
            ->set('bulkDestination', (string) $setup['targetClass']->id)
            ->set('selectedStudentIds', [$setup['ama']->id])
            ->set('effectiveDate', '2027-09-01')
            ->call('reviewSelected')
            ->assertHasNoErrors()
            ->assertSet('showConfirmationModal', true);

        $component
            ->set('selectedStudentIds', [$setup['ama']->id, $outsider->id])
            ->call('processPromotions')
            ->assertHasErrors();

        $this->assertDatabaseMissing('class_enrollments', [
            'school_class_id' => $setup['targetClass']->id,
            'student_id' => $setup['ama']->id,
        ]);
        $this->assertDatabaseMissing('class_enrollments', [
            'school_class_id' => $setup['targetClass']->id,
            'student_id' => $outsider->id,
        ]);
        $this->assertDatabaseHas('class_enrollments', [
            'school_class_id' => $setup['sourceClass']->id,
            'student_id' => $setup['ama']->id,
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);
    }

    public function test_duplicate_active_enrollment_rejects_and_rolls_back_the_entire_batch(): void
    {
        $setup = $this->promotionSetup();
        $duplicateClass = SchoolClass::create([
            'academic_year_id' => $setup['sourceYear']->id,
            'name' => 'Basic 1 Annex',
            'code' => 'B1-A',
            'status' => 'active',
        ]);
        ClassEnrollment::create([
            'school_class_id' => $duplicateClass->id,
            'student_id' => $setup['kwame']->id,
            'enrolled_at' => '2026-09-01',
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($setup['admin'])
            ->test(Index::class)
            ->set('sourceYearId', (string) $setup['sourceYear']->id)
            ->set('sourceClassId', (string) $setup['sourceClass']->id)
            ->set('targetYearId', (string) $setup['targetYear']->id)
            ->set('bulkDestination', (string) $setup['targetClass']->id)
            ->set('selectedStudentIds', [$setup['ama']->id, $setup['kwame']->id])
            ->set('effectiveDate', '2027-09-01')
            ->call('reviewSelected')
            ->assertHasNoErrors()
            ->call('processPromotions')
            ->assertHasErrors('selectedStudentIds');

        $this->assertDatabaseMissing('class_enrollments', [
            'school_class_id' => $setup['targetClass']->id,
            'student_id' => $setup['ama']->id,
        ]);
        $this->assertDatabaseMissing('class_enrollments', [
            'school_class_id' => $setup['targetClass']->id,
            'student_id' => $setup['kwame']->id,
        ]);
    }

    public function test_admin_can_apply_terminal_graduation_without_creating_a_target_enrollment(): void
    {
        $setup = $this->promotionSetup(targetIsActive: true);

        Livewire::actingAs($setup['admin'])
            ->test(Index::class)
            ->set('sourceYearId', (string) $setup['sourceYear']->id)
            ->set('sourceClassId', (string) $setup['sourceClass']->id)
            ->set('targetYearId', (string) $setup['targetYear']->id)
            ->set('studentDestinations.'.$setup['ama']->id, 'graduate')
            ->set('effectiveDate', '2027-09-01')
            ->call('reviewOne', $setup['ama']->id)
            ->assertHasNoErrors()
            ->call('processPromotions')
            ->assertHasNoErrors();

        $this->assertSame('graduated', $setup['ama']->fresh()->status);
        $this->assertDatabaseHas('class_enrollments', [
            'school_class_id' => $setup['sourceClass']->id,
            'student_id' => $setup['ama']->id,
            'status' => ClassEnrollment::STATUS_COMPLETED,
            'left_at' => '2027-07-31 00:00:00',
        ]);
        $this->assertDatabaseMissing('class_enrollments', [
            'student_id' => $setup['ama']->id,
            'status' => ClassEnrollment::STATUS_PENDING,
        ]);
        $this->assertDatabaseMissing('class_enrollments', [
            'student_id' => $setup['ama']->id,
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);
    }

    /**
     * @return array{
     *     admin: User,
     *     teacher: User,
     *     school: School,
     *     sourceYear: AcademicYear,
     *     targetYear: AcademicYear,
     *     sourceClass: SchoolClass,
     *     targetClass: SchoolClass,
     *     alternateTargetClass: SchoolClass,
     *     ama: Student,
     *     kwame: Student,
     *     unselected: Student
     * }
     */
    private function promotionSetup(bool $targetIsActive = false): array
    {
        Role::findOrCreate('school_admin');
        Role::findOrCreate('teacher');

        $admin = User::factory()->create();
        $admin->assignRole('school_admin');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);
        $sourceYear = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-07-31',
            'is_active' => ! $targetIsActive,
        ]);
        $targetYear = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'starts_at' => '2027-09-01',
            'ends_at' => '2028-07-31',
            'is_active' => $targetIsActive,
        ]);

        $sourceClass = SchoolClass::create([
            'academic_year_id' => $sourceYear->id,
            'name' => 'Basic 1',
            'code' => 'B1',
            'status' => 'active',
        ]);
        $targetClass = SchoolClass::create([
            'academic_year_id' => $targetYear->id,
            'name' => 'Basic 2',
            'code' => 'B2',
            'status' => 'active',
        ]);
        $alternateTargetClass = SchoolClass::create([
            'academic_year_id' => $targetYear->id,
            'name' => 'Basic 2 North',
            'code' => 'B2-N',
            'status' => 'active',
        ]);

        $ama = $this->createStudent($school, 'PRO-001');
        $kwame = $this->createStudent($school, 'PRO-002');
        $unselected = $this->createStudent($school, 'PRO-003');

        foreach ([$ama, $kwame, $unselected] as $student) {
            ClassEnrollment::create([
                'school_class_id' => $sourceClass->id,
                'student_id' => $student->id,
                'enrolled_at' => '2026-09-01',
                'status' => ClassEnrollment::STATUS_ACTIVE,
            ]);
        }

        return compact(
            'admin',
            'teacher',
            'school',
            'sourceYear',
            'targetYear',
            'sourceClass',
            'targetClass',
            'alternateTargetClass',
            'ama',
            'kwame',
            'unselected',
        );
    }

    private function createStudent(School $school, string $identifier): Student
    {
        return Student::create([
            'school_id' => $school->id,
            'student_id' => 'STU-'.$identifier,
            'admission_number' => 'ADM-'.$identifier,
            'first_name' => 'Learner',
            'last_name' => $identifier,
            'date_of_birth' => '2016-01-01',
            'gender' => 'female',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
    }
}
