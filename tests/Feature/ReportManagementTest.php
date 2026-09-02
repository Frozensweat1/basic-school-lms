<?php

namespace Tests\Feature;

use App\Livewire\LMS\Reports\Index as ReportsIndex;
use App\Livewire\LMS\Reports\Parent\Index as ParentReportsIndex;
use App\Livewire\LMS\Reports\Show as ReportShow;
use App\Livewire\LMS\Reports\Student\Index as StudentReportsIndex;
use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\ParentGuardian;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\User;
use App\Services\Reports\ReportCardGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_generates_a_report_only_for_an_active_class_enrollment(): void
    {
        [$admin, , $term, $class, $student] = $this->reportSetup();

        Livewire::actingAs($admin)
            ->test(ReportsIndex::class)
            ->set('generationTermId', (string) $term->id)
            ->set('generationClassId', (string) $class->id)
            ->set('generationStudentId', (string) $student->id)
            ->call('generateSingle')
            ->assertHasNoErrors();

        $report = ReportCard::query()->firstOrFail();
        $this->assertSame('draft', $report->status);
        $this->assertSame($class->id, $report->school_class_id);
        $this->assertSame($term->id, $report->term_id);
    }

    public function test_report_cannot_be_published_until_a_subject_result_is_published(): void
    {
        [$admin, , $term, $class, $student, $classSubject] = $this->reportSetup();
        $report = app(ReportCardGenerator::class)->generate($student, $term, $class->id);

        $component = Livewire::actingAs($admin)
            ->test(ReportsIndex::class)
            ->call('confirmPublish', $report->id)
            ->call('publishConfirmed')
            ->assertHasErrors(['publish']);

        SubjectResult::create([
            'student_id' => $student->id,
            'class_subject_id' => $classSubject->id,
            'term_id' => $term->id,
            'total_score' => 76,
            'grade' => 'B',
            'teacher_comment' => 'Good progress',
            'status' => 'published',
        ]);

        $component->call('publishConfirmed')->assertHasNoErrors();
        $this->assertSame('published', $report->fresh()->status);
    }

    public function test_historical_report_can_be_generated_after_an_enrolment_is_completed(): void
    {
        [, , $term, $class, $student] = $this->reportSetup();

        ClassEnrollment::query()
            ->where('school_class_id', $class->id)
            ->where('student_id', $student->id)
            ->update([
                'status' => ClassEnrollment::STATUS_COMPLETED,
                'left_at' => $term->ends_at,
            ]);

        $report = app(ReportCardGenerator::class)->generate($student, $term, $class->id);

        $this->assertSame($student->id, $report->student_id);
        $this->assertSame($term->id, $report->term_id);
        $this->assertSame($class->id, $report->school_class_id);
    }

    public function test_review_comments_and_published_report_access_are_role_scoped(): void
    {
        [$admin, $school, $term, $class, $student, $classSubject] = $this->reportSetup();
        SubjectResult::create([
            'student_id' => $student->id,
            'class_subject_id' => $classSubject->id,
            'term_id' => $term->id,
            'total_score' => 88,
            'grade' => 'A',
            'teacher_comment' => 'Excellent work',
            'status' => 'published',
        ]);
        $report = app(ReportCardGenerator::class)->generate($student, $term, $class->id);
        $report->publish();

        Livewire::actingAs($admin)
            ->test(ReportShow::class, ['reportCard' => $report])
            ->assertSee('Academic performance')
            ->assertSee('Mathematics')
            ->set('teacherComment', 'Consistent improvement')
            ->set('headteacherComment', 'Keep aiming high')
            ->call('saveComments')
            ->assertHasNoErrors();

        $studentUser = User::factory()->create();
        Role::create(['name' => 'student']);
        $studentUser->assignRole('student');
        $student->update(['user_id' => $studentUser->id, 'status' => 'graduated']);

        $parentUser = User::factory()->create();
        Role::create(['name' => 'parent']);
        $parentUser->assignRole('parent');
        $parent = ParentGuardian::create([
            'user_id' => $parentUser->id,
            'school_id' => $school->id,
            'first_name' => 'Efua',
            'last_name' => 'Asare',
        ]);
        $parent->students()->attach($student->id, ['relationship' => 'mother', 'is_primary_contact' => true]);

        Livewire::actingAs($studentUser)->test(StudentReportsIndex::class)->assertSee('Term 1')->assertSee('Basic 4');
        Livewire::actingAs($parentUser)->test(ParentReportsIndex::class)->assertSee('Kofi Asare')->assertSee('Term 1');
        Livewire::actingAs($parentUser)->test(ReportShow::class, ['reportCard' => $report->fresh()])->assertSee('Consistent improvement')->assertSee('Keep aiming high');
    }

    private function reportSetup(): array
    {
        Role::create(['name' => 'school_admin']);
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
        $class = SchoolClass::create(['academic_year_id' => $year->id, 'name' => 'Basic 4', 'status' => 'active']);
        $student = Student::create([
            'school_id' => $school->id,
            'student_id' => 'STU-RPT-001',
            'admission_number' => 'ADM-RPT-001',
            'first_name' => 'Kofi',
            'last_name' => 'Asare',
            'date_of_birth' => '2016-01-01',
            'gender' => 'male',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
        ClassEnrollment::create([
            'school_class_id' => $class->id,
            'student_id' => $student->id,
            'enrolled_at' => '2026-09-01',
            'status' => 'active',
        ]);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH-RPT', 'is_active' => true]);
        $classSubject = ClassSubject::create(['school_class_id' => $class->id, 'subject_id' => $subject->id]);

        return [$admin, $school, $term, $class, $student, $classSubject];
    }
}
