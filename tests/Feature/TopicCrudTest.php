<?php

namespace Tests\Feature;

use App\Livewire\LMS\Topics\Admin\Index;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Lesson;
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

class TopicCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_a_topic(): void
    {
        Role::create(['name' => 'school_admin']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026/2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-07-31']);
        $class = SchoolClass::create(['academic_year_id' => $year->id, 'name' => 'Basic 4']);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH']);
        $classSubject = ClassSubject::create(['school_class_id' => $class->id, 'subject_id' => $subject->id]);

        Livewire::actingAs($user)->test(Index::class)->call('create')->set('classSubjectId', (string) $classSubject->id)->set('title', 'Fractions')->set('sequence', '1')->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('topics', ['class_subject_id' => $classSubject->id, 'title' => 'Fractions', 'sequence' => 1]);

        $teacher = Teacher::create(['school_id' => $school->id, 'employee_id' => 'T-001', 'first_name' => 'Ama', 'last_name' => 'Mensah']);
        $classSubject->update(['teacher_id' => $teacher->id]);
        $fractions = Topic::where('title', 'Fractions')->firstOrFail();
        Lesson::create(['topic_id' => $fractions->id, 'teacher_id' => $teacher->id, 'title' => 'Adding fractions', 'sequence' => 1]);
        Topic::create(['class_subject_id' => $classSubject->id, 'title' => 'Decimals', 'sequence' => 2]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('search', 'Fractions')
            ->assertSee('Fractions')
            ->assertDontSee('Decimals')
            ->set('search', '')
            ->set('filterLessonState', 'without_lessons')
            ->assertSee('Decimals')
            ->assertDontSee('Fractions')
            ->call('clearFilters')
            ->assertSet('filterLessonState', '')
            ->assertSee('Fractions')
            ->assertSee('Decimals');
    }
}
