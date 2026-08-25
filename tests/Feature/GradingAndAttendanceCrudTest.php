<?php

namespace Tests\Feature;

use App\Livewire\LMS\Attendance\Admin\Overview as AttendanceOverview;
use App\Livewire\LMS\GradingScales\Index as GradingScalesIndex;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ClassEnrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GradingAndAttendanceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_a_non_overlapping_grading_scale(): void
    {
        [$admin, $school] = $this->schoolAdminAndSchool();

        Livewire::actingAs($admin)
            ->test(GradingScalesIndex::class)
            ->call('create')
            ->assertSet('showFormModal', true)
            ->set('grade', 'a')
            ->set('minimum', '80')
            ->set('maximum', '100')
            ->set('remark', 'Excellent')
            ->set('sequence', '1')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Excellent');

        $this->assertDatabaseHas('grading_scales', [
            'school_id' => $school->id,
            'grade' => 'A',
            'minimum' => 80,
            'maximum' => 100,
        ]);

        Livewire::actingAs($admin)
            ->test(GradingScalesIndex::class)
            ->call('create')
            ->set('grade', 'B')
            ->set('minimum', '70')
            ->set('maximum', '80')
            ->set('sequence', '2')
            ->call('save')
            ->assertHasErrors(['minimum']);
    }

    public function test_school_admin_can_load_filter_and_save_a_class_attendance_register(): void
    {
        [$admin, $school] = $this->schoolAdminAndSchool();
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
        $class = SchoolClass::create([
            'academic_year_id' => $year->id,
            'name' => 'Basic 4',
            'status' => 'active',
        ]);
        $student = Student::create([
            'school_id' => $school->id,
            'student_id' => 'STU-001',
            'admission_number' => 'ADM-001',
            'first_name' => 'Kojo',
            'last_name' => 'Owusu',
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

        $date = now()->toDateString();
        Livewire::actingAs($admin)
            ->test(AttendanceOverview::class)
            ->set('academicYearId', (string) $year->id)
            ->set('termId', (string) $term->id)
            ->set('classId', (string) $class->id)
            ->set('attendanceDate', $date)
            ->call('loadRegister')
            ->assertSet('registerLoaded', true)
            ->assertSee('Kojo')
            ->set('studentSearch', 'ADM-001')
            ->assertSee('Kojo')
            ->set("statuses.{$student->id}", 'late')
            ->set("remarks.{$student->id}", 'Bus delay')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('attendance_records', [
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'school_class_id' => $class->id,
            'student_id' => $student->id,
            'status' => 'late',
            'remarks' => 'Bus delay',
            'marked_by' => $admin->id,
        ]);

        $this->assertSame(
            $date,
            AttendanceRecord::query()
                ->where('school_class_id', $class->id)
                ->where('student_id', $student->id)
                ->firstOrFail()
                ->attendance_date
                ->toDateString(),
        );

    }

    private function schoolAdminAndSchool(): array
    {
        Role::create(['name' => 'school_admin']);
        $admin = User::factory()->create();
        $admin->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);

        return [$admin, $school];
    }
}
