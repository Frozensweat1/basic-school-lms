<?php

namespace Tests\Feature;

use App\Livewire\LMS\Quizzes\Index;
use App\Livewire\LMS\Quizzes\Student\Attempt as StudentQuizAttempt;
use App\Livewire\LMS\Quizzes\Student\Index as StudentQuizIndex;
use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuizCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_search_and_filter_quizzes(): void
    {
        Role::create(['name' => 'school_admin']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026/2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-07-31']);
        $class = SchoolClass::create(['academic_year_id' => $year->id, 'name' => 'Basic 4']);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH']);
        $teacher = Teacher::create(['school_id' => $school->id, 'employee_id' => 'T-001', 'first_name' => 'Ama', 'last_name' => 'Mensah']);
        $classSubject = ClassSubject::create(['school_class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->set('classSubjectId', (string) $classSubject->id)
            ->set('title', 'Fractions Quiz')
            ->set('maxAttempts', '2')
            ->set('status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('quizzes', [
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $teacher->id,
            'title' => 'Fractions Quiz',
            'max_attempts' => 2,
        ]);

        Quiz::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $teacher->id,
            'title' => 'Geometry revision',
            'max_attempts' => 1,
            'status' => 'draft',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('search', 'Fractions')
            ->assertSee('Fractions Quiz')
            ->assertDontSee('Geometry revision')
            ->set('search', '')
            ->set('filterStatus', 'draft')
            ->assertSee('Geometry revision')
            ->assertDontSee('Fractions Quiz')
            ->call('clearFilters')
            ->assertSet('filterStatus', '')
            ->assertSee('Fractions Quiz')
            ->assertSee('Geometry revision');
    }

    public function test_student_sees_upcoming_quiz_state_without_receiving_an_http_exception(): void
    {
        Role::create(['name' => 'student']);
        $user = User::factory()->create();
        $user->assignRole('student');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026/2027', 'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonths(8)]);
        $class = SchoolClass::create(['academic_year_id' => $year->id, 'name' => 'Basic 4']);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH']);
        $teacher = Teacher::create(['school_id' => $school->id, 'employee_id' => 'T-001', 'first_name' => 'Ama', 'last_name' => 'Mensah']);
        $classSubject = ClassSubject::create(['school_class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id]);
        $student = Student::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'student_id' => 'STU-001',
            'admission_number' => 'ADM-001',
            'first_name' => 'Kojo',
            'last_name' => 'Owusu',
            'date_of_birth' => '2015-05-10',
            'gender' => 'male',
            'admission_date' => now()->subMonth(),
            'status' => 'active',
        ]);
        ClassEnrollment::create([
            'school_class_id' => $class->id,
            'student_id' => $student->id,
            'enrolled_at' => now()->subMonth(),
            'status' => 'active',
        ]);
        $quiz = Quiz::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $teacher->id,
            'title' => 'Scheduled fractions quiz',
            'max_attempts' => 2,
            'opens_at' => now()->addDay(),
            'closes_at' => now()->addWeek(),
            'status' => 'published',
        ]);
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        Livewire::actingAs($user)
            ->test(StudentQuizIndex::class)
            ->assertSee('Scheduled fractions quiz')
            ->assertSee('Upcoming')
            ->assertDontSee('Start attempt')
            ->assertDontSee('Continue attempt')
            ->call('start', $quiz->id)
            ->assertNoRedirect();

        Livewire::actingAs($user)
            ->test(StudentQuizAttempt::class, ['attempt' => $attempt])
            ->assertRedirect(route('lms.quizzes.student.index'));

        $this->assertDatabaseCount('quiz_attempts', 1);
    }
}
