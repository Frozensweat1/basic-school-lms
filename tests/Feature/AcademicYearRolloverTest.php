<?php

namespace Tests\Feature;

use App\Livewire\LMS\AcademicYears\Index;
use App\Models\AcademicYear;
use App\Models\AssessmentComponent;
use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicYearRolloverTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_prepare_structure_and_pending_placements_then_activate_them_later(): void
    {
        $setup = $this->rolloverSetup();

        $component = Livewire::actingAs($setup['admin'])
            ->test(Index::class)
            ->call('prepareRollover', $setup['year']->id)
            ->assertSet('showRolloverModal', true)
            ->assertSet('rolloverName', '2027/2028')
            ->assertSet('rolloverStartsAt', '2027-09-01')
            ->assertSet('rolloverEndsAt', '2028-07-31')
            ->set('rolloverCopyTeachers', true)
            ->set('rolloverPrepareStudents', true)
            ->set('rolloverPromotions.'.$setup['basicOne']->id, (string) $setup['basicTwo']->id)
            ->set('rolloverPromotions.'.$setup['basicTwo']->id, (string) $setup['basicTwo']->id)
            ->call('runRollover')
            ->assertHasNoErrors()
            ->assertSet('showRolloverModal', false);

        $target = AcademicYear::query()->where('name', '2027/2028')->firstOrFail();
        $targetBasicOne = $target->classes()->where('name', 'Basic 1')->firstOrFail();
        $targetBasicTwo = $target->classes()->where('name', 'Basic 2')->firstOrFail();
        $targetTerm = $target->terms()->where('name', 'Term 1')->firstOrFail();

        $this->assertFalse($target->is_active);
        $this->assertDatabaseCount('academic_years', 2);
        $this->assertSame(2, $target->classes()->count());
        $this->assertSame(2, $targetTerm->classes()->count());
        $this->assertSame('2027-09-01', $targetTerm->starts_at->toDateString());
        $this->assertSame('2027-12-18', $targetTerm->ends_at->toDateString());
        $this->assertDatabaseHas('assessment_components', [
            'term_id' => $targetTerm->id,
            'name' => 'Class work',
            'weight' => 40,
        ]);
        $this->assertDatabaseHas('class_subjects', [
            'school_class_id' => $targetBasicOne->id,
            'subject_id' => $setup['subject']->id,
            'teacher_id' => $setup['teacher']->id,
        ]);
        $this->assertDatabaseHas('class_teachers', [
            'school_class_id' => $targetBasicOne->id,
            'teacher_id' => $setup['teacher']->id,
            'role' => 'class_teacher',
        ]);
        $this->assertDatabaseHas('class_enrollments', [
            'school_class_id' => $targetBasicTwo->id,
            'student_id' => $setup['promotedStudent']->id,
            'status' => ClassEnrollment::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('class_enrollments', [
            'school_class_id' => $setup['basicOne']->id,
            'student_id' => $setup['promotedStudent']->id,
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);

        $component
            ->call('edit', $target->id)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($target->fresh()->is_active);
        $this->assertFalse($setup['year']->fresh()->is_active);
        $this->assertDatabaseHas('class_enrollments', [
            'school_class_id' => $targetBasicTwo->id,
            'student_id' => $setup['promotedStudent']->id,
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('class_enrollments', [
            'school_class_id' => $setup['basicOne']->id,
            'student_id' => $setup['promotedStudent']->id,
            'status' => ClassEnrollment::STATUS_COMPLETED,
            'left_at' => '2027-07-31 00:00:00',
        ]);
    }

    public function test_rollover_can_activate_first_term_and_record_graduates(): void
    {
        $setup = $this->rolloverSetup();

        Livewire::actingAs($setup['admin'])
            ->test(Index::class)
            ->call('prepareRollover', $setup['year']->id)
            ->set('rolloverPrepareStudents', true)
            ->set('rolloverActivate', true)
            ->set('rolloverActivateFirstTerm', true)
            ->set('rolloverPromotions.'.$setup['basicOne']->id, (string) $setup['basicTwo']->id)
            ->set('rolloverPromotions.'.$setup['basicTwo']->id, 'graduate')
            ->call('runRollover')
            ->assertHasNoErrors();

        $target = AcademicYear::query()->where('name', '2027/2028')->firstOrFail();
        $targetBasicTwo = $target->classes()->where('name', 'Basic 2')->firstOrFail();

        $this->assertTrue($target->is_active);
        $this->assertFalse($setup['year']->fresh()->is_active);
        $this->assertTrue($target->terms()->where('sequence', 1)->firstOrFail()->is_active);
        $this->assertSame('graduated', $setup['graduatingStudent']->fresh()->status);
        $this->assertDatabaseHas('class_enrollments', [
            'school_class_id' => $targetBasicTwo->id,
            'student_id' => $setup['promotedStudent']->id,
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseMissing('class_enrollments', [
            'student_id' => $setup['graduatingStudent']->id,
            'status' => ClassEnrollment::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('class_enrollments', [
            'school_class_id' => $setup['basicTwo']->id,
            'student_id' => $setup['graduatingStudent']->id,
            'status' => ClassEnrollment::STATUS_COMPLETED,
        ]);
    }

    public function test_invalid_promotion_mapping_rolls_back_the_entire_rollover(): void
    {
        $setup = $this->rolloverSetup();

        Livewire::actingAs($setup['admin'])
            ->test(Index::class)
            ->call('prepareRollover', $setup['year']->id)
            ->set('rolloverPrepareStudents', true)
            ->set('rolloverPromotions.'.$setup['basicOne']->id, '999999')
            ->set('rolloverPromotions.'.$setup['basicTwo']->id, (string) $setup['basicTwo']->id)
            ->call('runRollover')
            ->assertHasErrors('rolloverPromotions.'.$setup['basicOne']->id)
            ->assertSet('showRolloverModal', true);

        $this->assertDatabaseCount('academic_years', 1);
        $this->assertDatabaseCount('school_classes', 2);
        $this->assertDatabaseCount('terms', 1);
    }

    private function rolloverSetup(): array
    {
        Role::findOrCreate('school_admin');
        $admin = User::factory()->create();
        $admin->assignRole('school_admin');

        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-07-31',
            'is_active' => true,
        ]);
        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-12-18',
            'is_active' => true,
        ]);
        AssessmentComponent::create([
            'term_id' => $term->id,
            'name' => 'Class work',
            'weight' => 40,
            'sequence' => 1,
        ]);

        $stream = Stream::create(['school_id' => $school->id, 'name' => 'Gold', 'is_active' => true]);
        $basicOne = SchoolClass::create([
            'academic_year_id' => $year->id,
            'stream_id' => $stream->id,
            'name' => 'Basic 1',
            'code' => 'B1-G',
            'status' => 'active',
        ]);
        $basicTwo = SchoolClass::create([
            'academic_year_id' => $year->id,
            'stream_id' => $stream->id,
            'name' => 'Basic 2',
            'code' => 'B2-G',
            'status' => 'active',
        ]);

        $teacher = Teacher::create([
            'school_id' => $school->id,
            'employee_id' => 'T-RLO-001',
            'first_name' => 'Akosua',
            'last_name' => 'Mensah',
            'status' => 'active',
        ]);
        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'Mathematics',
            'code' => 'MATH-RLO',
            'is_active' => true,
        ]);
        ClassSubject::create([
            'school_class_id' => $basicOne->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);
        $basicOne->teachers()->attach($teacher->id, ['role' => 'class_teacher']);

        $promotedStudent = $this->createStudent($school, 'RLO-001');
        $graduatingStudent = $this->createStudent($school, 'RLO-002');
        ClassEnrollment::create([
            'school_class_id' => $basicOne->id,
            'student_id' => $promotedStudent->id,
            'enrolled_at' => '2026-09-01',
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);
        ClassEnrollment::create([
            'school_class_id' => $basicTwo->id,
            'student_id' => $graduatingStudent->id,
            'enrolled_at' => '2026-09-01',
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);

        return compact(
            'admin',
            'school',
            'year',
            'term',
            'stream',
            'basicOne',
            'basicTwo',
            'teacher',
            'subject',
            'promotedStudent',
            'graduatingStudent',
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
            'date_of_birth' => '2017-01-01',
            'gender' => 'female',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
    }
}
