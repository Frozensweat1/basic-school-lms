<?php

namespace Tests\Feature;

use App\Livewire\LMS\Assignments\Admin\Index;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentAttachment;
use App\Models\ClassSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssignmentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_an_assignment(): void
    {
        Storage::fake('local');
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
            ->set('title', 'Fraction practice')
            ->set('instructions', '<p>Complete all questions.</p>')
            ->set('dueAt', '2026-10-10T12:00')
            ->set('status', 'published')
            ->set('attachmentFiles', [UploadedFile::fake()->create('fraction-practice.pdf', 64, 'application/pdf')])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assignments', ['class_subject_id' => $classSubject->id, 'teacher_id' => $teacher->id, 'title' => 'Fraction practice', 'status' => 'published']);

        $assignment = Assignment::where('title', 'Fraction practice')->firstOrFail();
        $attachment = AssignmentAttachment::where('assignment_id', $assignment->id)->firstOrFail();
        $this->assertSame('fraction-practice.pdf', $attachment->name);
        Storage::disk('local')->assertExists($attachment->path);

        Assignment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $teacher->id,
            'title' => 'Geometry revision',
            'instructions' => '<p>Revise shapes.</p>',
            'max_score' => 100,
            'due_at' => '2026-11-10 12:00:00',
            'status' => 'draft',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('search', 'Fraction')
            ->assertSee('Fraction practice')
            ->assertDontSee('Geometry revision')
            ->set('search', '')
            ->set('filterStatus', 'draft')
            ->assertSee('Geometry revision')
            ->assertDontSee('Fraction practice')
            ->call('clearFilters')
            ->assertSet('filterStatus', '')
            ->assertSee('Fraction practice')
            ->assertSee('Geometry revision');
    }
}
