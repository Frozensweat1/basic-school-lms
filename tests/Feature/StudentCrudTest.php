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
use Illuminate\Support\Facades\Hash;
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
        $csv = $this->studentImportCsv();

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
            'user_id' => $student->user_id,
        ]);
        $this->assertDatabaseHas('class_enrollments', [
            'student_id' => $student->id,
            'school_class_id' => $class->id,
            'status' => 'active',
            'enrollment_type' => 'day',
        ]);
        $this->assertDatabaseHas('parents', [
            'school_id' => $school->id,
            'email' => 'adwoa.parent@example.test',
            'phone' => '0241111111',
        ]);
        $this->assertSame('ama.student@example.test', $student->user->email);
        $this->assertSame('Ama Mensah', $student->user->name);
        $this->assertTrue($student->user->hasRole('student'));
        $this->assertTrue(Hash::check('StudentPass123!', $student->user->password));
    }

    public function test_student_import_writes_nothing_when_any_row_is_invalid(): void
    {
        [$user] = $this->schoolAdminWithClass();
        $csv = $this->studentImportCsv([
            [
                'student_id' => 'STU-301',
                'admission_number' => 'ADM-301',
                'first_name' => 'Abena',
                'last_name' => 'Boateng',
                'email' => 'abena.student@example.test',
            ],
            [
                'student_id' => 'STU-302',
                'admission_number' => 'ADM-302',
                'first_name' => 'Kofi',
                'last_name' => 'Asare',
                'email' => 'kofi.student@example.test',
                'gender' => 'unknown',
                'guardian_email' => 'second.parent@example.test',
                'guardian_phone' => '0242222222',
            ],
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('openImport')
            ->set('importFile', UploadedFile::fake()->createWithContent('invalid-students.csv', $csv))
            ->call('importStudents')
            ->assertSee('The file was not imported');

        $this->assertDatabaseMissing('students', ['student_id' => 'STU-301']);
        $this->assertDatabaseMissing('students', ['student_id' => 'STU-302']);
        $this->assertDatabaseMissing('users', ['email' => 'abena.student@example.test']);
        $this->assertDatabaseMissing('users', ['email' => 'kofi.student@example.test']);
    }

    public function test_changing_a_students_class_completes_the_previous_enrollment_history(): void
    {
        [$user, $school, $sourceClass] = $this->schoolAdminWithClass();
        $targetClass = SchoolClass::create([
            'academic_year_id' => $sourceClass->academic_year_id,
            'name' => 'Basic 1 North',
            'status' => 'active',
        ]);
        $student = Student::create([
            'school_id' => $school->id,
            'student_id' => 'STU-401',
            'admission_number' => 'ADM-401',
            'first_name' => 'Efua',
            'last_name' => 'Owusu',
            'date_of_birth' => '2015-04-10',
            'gender' => 'female',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
        ClassEnrollment::create([
            'student_id' => $student->id,
            'school_class_id' => $sourceClass->id,
            'enrolled_at' => '2026-09-01',
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('edit', $student->id)
            ->set('email', 'efua.student@example.test')
            ->set('password', 'StudentPass123!')
            ->set('homeTown', 'Cape Coast')
            ->set('region', 'Central')
            ->set('nationality', 'Ghanaian')
            ->set('schoolClassId', (string) $targetClass->id)
            ->set('guardianFirstName', 'Akosua')
            ->set('guardianLastName', 'Owusu')
            ->set('guardianEmail', 'akosua.parent@example.test')
            ->set('guardianPhone', '0243333333')
            ->set('guardianInformationDate', '2026-09-01')
            ->set('guardianGpsAddress', 'CC-001-0001')
            ->set('guardianCity', 'Cape Coast')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('class_enrollments', [
            'student_id' => $student->id,
            'school_class_id' => $sourceClass->id,
            'status' => ClassEnrollment::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('class_enrollments', [
            'student_id' => $student->id,
            'school_class_id' => $targetClass->id,
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);
    }

    /** @return array{0: User, 1: School, 2: SchoolClass} */
    private function schoolAdminWithClass(): array
    {
        Role::create(['name' => 'school_admin']);
        Role::create(['name' => 'student']);
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

    /**
     * @param  array<int, array<string, string>>  $overrides
     */
    private function studentImportCsv(array $overrides = [[]]): string
    {
        $headers = [
            'student_id', 'admission_number', 'first_name', 'middle_name', 'last_name',
            'email', 'temporary_password', 'date_of_birth', 'gender', 'home_town', 'region',
            'nationality', 'denomination', 'health_insurance_id', 'admission_date', 'status',
            'class_name', 'enrollment_type', 'previous_school_name', 'previous_school_city',
            'previous_school_country', 'previous_school_gps_address', 'previous_school_phone',
            'previous_school_last_class', 'guardian_first_name', 'guardian_last_name',
            'guardian_relationship', 'guardian_email', 'guardian_phone', 'guardian_information_date',
            'guardian_gps_address', 'guardian_city', 'guardian_workplace',
            'guardian_ghana_card_number', 'has_allergies', 'allergy_details',
        ];
        $defaults = [
            'student_id' => 'STU-001',
            'admission_number' => 'ADM-001',
            'first_name' => 'Ama',
            'middle_name' => '',
            'last_name' => 'Mensah',
            'email' => 'ama.student@example.test',
            'temporary_password' => 'StudentPass123!',
            'date_of_birth' => '2015-06-12',
            'gender' => 'female',
            'home_town' => 'Kumasi',
            'region' => 'Ashanti',
            'nationality' => 'Ghanaian',
            'denomination' => 'Christian',
            'health_insurance_id' => 'NHIS-001',
            'admission_date' => '2026-09-01',
            'status' => 'active',
            'class_name' => 'Basic 1',
            'enrollment_type' => 'day',
            'previous_school_name' => '',
            'previous_school_city' => '',
            'previous_school_country' => '',
            'previous_school_gps_address' => '',
            'previous_school_phone' => '',
            'previous_school_last_class' => '',
            'guardian_first_name' => 'Adwoa',
            'guardian_last_name' => 'Mensah',
            'guardian_relationship' => 'Mother',
            'guardian_email' => 'adwoa.parent@example.test',
            'guardian_phone' => '0241111111',
            'guardian_information_date' => '2026-09-01',
            'guardian_gps_address' => 'AK-111-1111',
            'guardian_city' => 'Kumasi',
            'guardian_workplace' => '',
            'guardian_ghana_card_number' => '',
            'has_allergies' => 'no',
            'allergy_details' => '',
        ];

        $rows = [implode(',', $headers)];
        foreach ($overrides as $rowOverrides) {
            $row = array_replace($defaults, $rowOverrides);
            $rows[] = implode(',', array_map(fn (string $header): string => $row[$header], $headers));
        }

        return implode("\n", $rows);
    }
}
