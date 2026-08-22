<?php

namespace Tests\Feature;

use App\Livewire\LMS\Students\Index;
use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_search_and_filter_students(): void
    {
        [$user, $school, $class] = $this->schoolAdminWithClass();

        $firstStudent = Student::create([
            'school_id' => $school->id,
            'student_id' => 'STU-100',
            'admission_number' => 'ADM-100',
            'first_name' => 'Zia',
            'last_name' => 'Koomson',
            'date_of_birth' => '2015-04-10',
            'gender' => 'female',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
        Student::create([
            'school_id' => $school->id,
            'student_id' => 'STU-200',
            'admission_number' => 'ADM-200',
            'first_name' => 'Kwaku',
            'last_name' => 'Nartey',
            'date_of_birth' => '2014-01-20',
            'gender' => 'male',
            'admission_date' => '2026-09-01',
            'status' => 'graduated',
        ]);
        ClassEnrollment::create([
            'student_id' => $firstStudent->id,
            'school_class_id' => $class->id,
            'enrolled_at' => '2026-09-01',
            'status' => 'active',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('search', 'Zia')
            ->assertSee('Zia')
            ->assertDontSee('Kwaku')
            ->set('search', '')
            ->set('filterStatus', 'graduated')
            ->assertSee('Kwaku')
            ->assertDontSee('Zia')
            ->set('filterStatus', '')
            ->set('filterClassId', (string) $class->id)
            ->assertSee('Zia')
            ->assertDontSee('Kwaku')
            ->call('clearFilters')
            ->assertSet('filterClassId', '')
            ->assertSee('Zia')
            ->assertSee('Kwaku');
    }

    public function test_school_admin_can_import_students_from_csv_and_enrol_them(): void
    {
        [$user, $school, $class] = $this->schoolAdminWithClass();
        $csv = implode("\n", [
            'student_id,admission_number,first_name,middle_name,last_name,date_of_birth,gender,admission_date,status,class_name',
            'STU-001,ADM-001,Ama,,Mensah,2015-06-12,female,2026-09-01,active,Basic 1',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('importFile', UploadedFile::fake()->createWithContent('students.csv', $csv))
            ->call('importStudents')
            ->assertHasNoErrors();

        $student = Student::where('student_id', 'STU-001')->firstOrFail();

        $this->assertDatabaseHas('students', [
            'school_id' => $school->id,
            'student_id' => 'STU-001',
            'admission_number' => 'ADM-001',
            'first_name' => 'Ama',
        ]);
        $this->assertDatabaseHas('class_enrollments', [
            'student_id' => $student->id,
            'school_class_id' => $class->id,
            'status' => 'active',
        ]);
    }

    public function test_student_import_writes_nothing_when_any_row_is_invalid(): void
    {
        [$user] = $this->schoolAdminWithClass();
        $csv = implode("\n", [
            'student_id,admission_number,first_name,last_name,date_of_birth,gender,admission_date',
            'STU-301,ADM-301,Abena,Boateng,2015-06-12,female,2026-09-01',
            'STU-302,ADM-302,Kofi,Asare,2015-06-12,unknown,2026-09-01',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('openImport')
            ->set('importFile', UploadedFile::fake()->createWithContent('invalid-students.csv', $csv))
            ->call('importStudents')
            ->assertSee('The file was not imported');

        $this->assertDatabaseMissing('students', ['student_id' => 'STU-301']);
        $this->assertDatabaseMissing('students', ['student_id' => 'STU-302']);
    }

    /** @return array{0: User, 1: School, 2: SchoolClass} */
    private function schoolAdminWithClass(): array
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
        $class = SchoolClass::create([
            'academic_year_id' => $year->id,
            'name' => 'Basic 1',
            'status' => 'active',
        ]);

        return [$user, $school, $class];
    }
}
