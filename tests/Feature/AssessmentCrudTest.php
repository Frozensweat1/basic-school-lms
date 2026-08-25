<?php

namespace Tests\Feature;

use App\Livewire\LMS\AssessmentComponents\Index as AssessmentComponentsIndex;
use App\Livewire\LMS\Assessments\Index;
use App\Livewire\LMS\AssessmentScores\Index as AssessmentScoresIndex;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentComponent;
use App\Models\ClassSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssessmentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_an_assessment_linked_to_a_component_and_open_score_entry(): void
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
        ]);
        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-12-18',
        ]);
        $component = AssessmentComponent::create([
            'term_id' => $term->id,
            'name' => 'Class Exercise',
            'weight' => 20,
            'sequence' => 1,
        ]);
        $class = SchoolClass::create(['academic_year_id' => $year->id, 'name' => 'Basic 4']);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH']);
        $teacher = Teacher::create(['school_id' => $school->id, 'employee_id' => 'T-001', 'first_name' => 'Ama', 'last_name' => 'Mensah']);
        $classSubject = ClassSubject::create([
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->assertSee('New assessment')
            ->assertSeeHtml('z-[100]')
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('termId', (string) $term->id)
            ->set('componentId', (string) $component->id)
            ->set('teacherId', (string) $teacher->id)
            ->set('title', 'Fractions Class Exercise')
            ->set('maxScore', '20')
            ->set('assessedAt', '2026-09-12')
            ->set('status', 'draft')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Scores');

        Livewire::actingAs($user)
            ->test(AssessmentComponentsIndex::class)
            ->set('search', 'Class Exercise')
            ->assertSee('Class Exercise')
            ->assertSee('1 assessment');

        $this->assertDatabaseHas('assessments', [
            'class_subject_id' => $classSubject->id,
            'term_id' => $term->id,
            'assessment_component_id' => $component->id,
            'teacher_id' => $teacher->id,
            'title' => 'Fractions Class Exercise',
            'status' => 'draft',
        ]);
    }

    public function test_published_assessment_scores_are_saved_and_recalculate_the_subject_result(): void
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
        ]);
        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-12-18',
        ]);
        $component = AssessmentComponent::create(['term_id' => $term->id, 'name' => 'Class Exercise', 'weight' => 20, 'sequence' => 1]);
        $class = SchoolClass::create(['academic_year_id' => $year->id, 'name' => 'Basic 4']);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH']);
        $teacher = Teacher::create(['school_id' => $school->id, 'employee_id' => 'T-001', 'first_name' => 'Ama', 'last_name' => 'Mensah']);
        $student = Student::create([
            'school_id' => $school->id,
            'student_id' => 'S-001',
            'admission_number' => 'ADM-001',
            'first_name' => 'Kojo',
            'last_name' => 'Owusu',
            'date_of_birth' => '2016-01-01',
            'gender' => 'male',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
        $class->enrollments()->create(['student_id' => $student->id, 'enrolled_at' => '2026-09-01', 'status' => 'active']);
        $classSubject = ClassSubject::create(['school_class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);
        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'term_id' => $term->id,
            'assessment_component_id' => $component->id,
            'teacher_id' => $teacher->id,
            'title' => 'Fractions Exercise',
            'max_score' => 20,
            'assessed_at' => '2026-09-12',
            'status' => 'published',
        ]);

        Livewire::actingAs($user)
            ->test(AssessmentScoresIndex::class, ['assessment' => $assessment])
            ->set('search', 'Kojo')
            ->assertSee('Kojo')
            ->set("scores.{$student->id}", '18')
            ->set("comments.{$student->id}", 'Great effort')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'score' => 18,
            'comment' => 'Great effort',
        ]);
        $this->assertDatabaseHas('subject_results', [
            'student_id' => $student->id,
            'class_subject_id' => $classSubject->id,
            'term_id' => $term->id,
            'total_score' => 18,
            'status' => 'published',
        ]);
    }
}
