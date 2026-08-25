<?php

namespace Tests\Feature;

use App\Livewire\LMS\Examinations\Index;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Examination;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExaminationCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_schedule_an_examination_for_a_class_subject(): void
    {
        Role::create(['name' => 'school_admin']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');

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
        ]);
        $class = SchoolClass::create(['academic_year_id' => $year->id, 'name' => 'Basic 4']);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH']);
        $teacher = Teacher::create(['school_id' => $school->id, 'employee_id' => 'T-001', 'first_name' => 'Ama', 'last_name' => 'Mensah']);
        $classSubject = ClassSubject::create(['school_class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->assertSee('Schedule examination')
            ->assertSeeHtml('z-[100]')
            ->set('academicYearId', (string) $year->id)
            ->set('termId', (string) $term->id)
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('teacherId', (string) $teacher->id)
            ->set('title', 'Term 1 Mathematics Examination')
            ->set('examDate', '2026-12-10')
            ->set('durationMinutes', '90')
            ->set('maxScore', '100')
            ->set('status', 'scheduled')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('examinations', [
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $teacher->id,
            'title' => 'Term 1 Mathematics Examination',
            'status' => 'scheduled',
        ]);
    }

    public function test_legacy_published_examinations_remain_visible_to_learners(): void
    {
        $this->assertContains('published', Examination::LEARNER_VISIBLE_STATUSES);
    }
}
