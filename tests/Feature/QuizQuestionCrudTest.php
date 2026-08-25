<?php

namespace Tests\Feature;

use App\Livewire\LMS\QuizQuestions\Index;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuizQuestionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_add_a_matching_subject_question_to_a_quiz(): void
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
        $topic = Topic::create(['class_subject_id' => $classSubject->id, 'title' => 'Fractions', 'sequence' => 1]);
        $lesson = Lesson::create(['topic_id' => $topic->id, 'teacher_id' => $teacher->id, 'title' => 'Adding fractions', 'sequence' => 1]);
        $quiz = Quiz::create(['class_subject_id' => $classSubject->id, 'topic_id' => $topic->id, 'lesson_id' => $lesson->id, 'teacher_id' => $teacher->id, 'title' => 'Fractions Quiz', 'max_attempts' => 1]);
        $question = Question::create(['school_id' => $school->id, 'subject_id' => $subject->id, 'topic_id' => $topic->id, 'lesson_id' => $lesson->id, 'created_by' => $user->id, 'type' => 'short_answer', 'prompt' => 'What is half of ten?', 'grading_key' => ['answer' => '5'], 'max_score' => 1]);
        $otherTopic = Topic::create(['class_subject_id' => $classSubject->id, 'title' => 'Decimals', 'sequence' => 2]);
        $otherLesson = Lesson::create(['topic_id' => $otherTopic->id, 'teacher_id' => $teacher->id, 'title' => 'Reading decimals', 'sequence' => 1]);
        Question::create(['school_id' => $school->id, 'subject_id' => $subject->id, 'topic_id' => $otherTopic->id, 'lesson_id' => $otherLesson->id, 'created_by' => $user->id, 'type' => 'multiple_choice', 'prompt' => 'Which decimal is larger?', 'grading_key' => ['answer' => '0.5'], 'max_score' => 1]);

        Livewire::actingAs($user)
            ->test(Index::class, ['quiz' => $quiz])
            ->call('create')
            ->assertSee('What is half of ten?')
            ->assertDontSee('Which decimal is larger?')
            ->set('bankSearch', 'half')
            ->assertSee('What is half of ten?')
            ->set('questionId', (string) $question->id)
            ->set('sequence', '1')
            ->call('save')
            ->assertHasNoErrors()
            ->set('search', 'half')
            ->assertSee('What is half of ten?')
            ->set('search', '')
            ->set('filterType', 'short_answer')
            ->assertSee('What is half of ten?')
            ->call('clearFilters')
            ->assertSet('filterType', '');

        $this->assertDatabaseHas('quiz_questions', ['quiz_id' => $quiz->id, 'question_id' => $question->id, 'sequence' => 1]);
    }
}
