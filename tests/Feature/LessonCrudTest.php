<?php

namespace Tests\Feature;

use App\Livewire\LMS\Lessons\Admin\Index;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
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

class LessonCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_a_lesson(): void
    {
        Role::create(['name' => 'school_admin']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026/2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-07-31']);
        $class = SchoolClass::create(['academic_year_id' => $year->id, 'name' => 'Basic 4']);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH']);
        $teacher = Teacher::create(['school_id' => $school->id, 'employee_id' => 'T-001', 'first_name' => 'Ama', 'last_name' => 'Mensah']);
        $topic = Topic::create(['class_subject_id' => ClassSubject::create(['school_class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id])->id, 'title' => 'Fractions']);

        Livewire::actingAs($user)->test(Index::class)->call('create')->set('topicId', (string) $topic->id)->set('title', 'Adding fractions')->set('sequence', '1')->set('status', 'published')->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('lessons', ['topic_id' => $topic->id, 'teacher_id' => $teacher->id, 'title' => 'Adding fractions', 'status' => 'published']);
    }
}
