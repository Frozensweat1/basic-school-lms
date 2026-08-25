<?php

namespace Tests\Feature;

use App\Livewire\LMS\Questions\Index;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Lesson;
use App\Models\Question;
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

class QuestionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_search_and_filter_a_curriculum_linked_question(): void
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

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->set('subjectId', (string) $subject->id)
            ->set('topicId', (string) $topic->id)
            ->set('lessonId', (string) $lesson->id)
            ->set('type', 'multiple_choice')
            ->set('prompt', 'What is 2 + 2?')
            ->set('optionsText', "3\n4")
            ->set('correctAnswer', '4')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('questions', [
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'topic_id' => $topic->id,
            'lesson_id' => $lesson->id,
            'prompt' => 'What is 2 + 2?',
        ]);
        $this->assertDatabaseHas('question_options', ['label' => '4', 'is_correct' => true]);

        Question::create([
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'topic_id' => $topic->id,
            'lesson_id' => $lesson->id,
            'created_by' => $user->id,
            'type' => 'short_answer',
            'prompt' => 'Name one equivalent fraction for one half.',
            'grading_key' => ['answer' => 'two quarters'],
            'max_score' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('search', '2 + 2')
            ->assertSee('What is 2 + 2?')
            ->assertDontSee('Name one equivalent fraction')
            ->set('search', '')
            ->set('filterType', 'short_answer')
            ->assertSee('Name one equivalent fraction')
            ->assertDontSee('What is 2 + 2?')
            ->set('filterType', '')
            ->set('filterSubjectId', (string) $subject->id)
            ->set('filterTopicId', (string) $topic->id)
            ->set('filterLessonId', (string) $lesson->id)
            ->assertSee('What is 2 + 2?')
            ->assertSee('Name one equivalent fraction')
            ->call('clearFilters')
            ->assertSet('filterSubjectId', '')
            ->assertSet('filterTopicId', '');
    }
}
