<?php

namespace Tests\Feature;

use App\Livewire\LMS\Students\Index;
use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentAdmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private SchoolClass $schoolClass;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['school_admin', 'student', 'parent'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('school_admin');
        $this->school = School::create([
            'name' => 'BrightStar Academy',
            'code' => 'BSA',
        ]);
        $academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-07-31',
            'is_active' => true,
        ]);
        $this->schoolClass = SchoolClass::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Basic 1',
            'status' => 'active',
        ]);
    }

    public function test_full_manual_admission_persists_the_form_and_provisions_both_logins(): void
    {
        $this->admissionForm([
            'middleName' => 'Nhyira',
            'enrollmentType' => ClassEnrollment::ENROLLMENT_TYPE_BOARDING,
            'denomination' => 'Methodist',
            'healthInsuranceId' => 'NHIS-9876543',
            'previousSchoolName' => 'Little Stars Preparatory School',
            'previousSchoolCity' => 'Kumasi',
            'previousSchoolCountry' => 'Ghana',
            'previousSchoolGpsAddress' => 'AK-123-4567',
            'previousSchoolPhone' => '032 202 0202',
            'previousSchoolLastClass' => 'Kindergarten 2',
            'guardianRelationship' => 'Mother',
            'guardianWorkplace' => 'Komfo Anokye Teaching Hospital',
            'guardianGhanaCardNumber' => 'GHA-123456789-0',
            'hasAllergies' => true,
            'allergyDetails' => 'Peanut allergy; prescribed antihistamine is kept with the school nurse.',
        ])->call('save')->assertHasNoErrors();

        $student = Student::where('student_id', 'STU-ADMISSION-001')->firstOrFail();
        $studentAccount = $student->user()->firstOrFail();
        $guardian = $student->parents()->firstOrFail();
        $guardianAccount = $guardian->user()->firstOrFail();

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'school_id' => $this->school->id,
            'admission_number' => 'ADM-ADMISSION-001',
            'first_name' => 'Ama',
            'middle_name' => 'Nhyira',
            'last_name' => 'Mensah',
            'gender' => 'female',
            'home_town' => 'Mampong',
            'region' => 'Ashanti',
            'nationality' => 'Ghanaian',
            'denomination' => 'Methodist',
            'health_insurance_id' => 'NHIS-9876543',
            'previous_school_name' => 'Little Stars Preparatory School',
            'previous_school_city' => 'Kumasi',
            'previous_school_country' => 'Ghana',
            'previous_school_gps_address' => 'AK-123-4567',
            'previous_school_phone' => '032 202 0202',
            'previous_school_last_class' => 'Kindergarten 2',
            'has_allergies' => true,
            'allergy_details' => 'Peanut allergy; prescribed antihistamine is kept with the school nurse.',
            'status' => 'active',
        ]);
        $this->assertSame('2015-05-10', $student->date_of_birth->toDateString());
        $this->assertSame('2026-09-01', $student->admission_date->toDateString());
        $this->assertDatabaseHas('class_enrollments', [
            'student_id' => $student->id,
            'school_class_id' => $this->schoolClass->id,
            'status' => ClassEnrollment::STATUS_ACTIVE,
            'enrollment_type' => ClassEnrollment::ENROLLMENT_TYPE_BOARDING,
        ]);
        $this->assertSame(
            '2026-09-01',
            $student->enrollments()->sole()->enrolled_at->toDateString(),
        );
        $this->assertDatabaseHas('parents', [
            'id' => $guardian->id,
            'school_id' => $this->school->id,
            'first_name' => 'Adwoa',
            'last_name' => 'Mensah',
            'phone' => '+233241234567',
            'email' => 'adwoa.guardian@example.test',
            'gps_address' => 'AK-456-7890',
            'city' => 'Kumasi',
            'workplace' => 'Komfo Anokye Teaching Hospital',
            'ghana_card_number' => 'GHA-123456789-0',
        ]);
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $guardian->id,
            'student_id' => $student->id,
            'relationship' => 'Mother',
            'is_primary_contact' => true,
            'information_date' => '2026-09-01',
        ]);

        $this->assertSame('Ama Nhyira Mensah', $studentAccount->name);
        $this->assertSame('ama.student@example.test', $studentAccount->email);
        $this->assertTrue($studentAccount->hasRole('student'));
        $this->assertTrue(Hash::check('StudentPass123!', $studentAccount->password));
        $this->assertSame('adwoa.guardian@example.test', $guardianAccount->email);
        $this->assertTrue($guardianAccount->hasRole('parent'));
        $this->assertTrue(Hash::check('+233241234567', $guardianAccount->password));
    }

    public function test_an_existing_guardian_is_reused_for_siblings_by_email_and_by_phone(): void
    {
        $this->admissionForm()->call('save')->assertHasNoErrors();

        $guardian = ParentGuardian::sole();

        $this->admissionForm([
            'studentId' => 'STU-ADMISSION-002',
            'admissionNumber' => 'ADM-ADMISSION-002',
            'firstName' => 'Yaw',
            'lastName' => 'Mensah',
            'email' => 'yaw.student@example.test',
            'guardianEmail' => 'ADWOA.GUARDIAN@EXAMPLE.TEST',
            'guardianPhone' => '024 999 0000',
        ])->call('save')->assertHasNoErrors();

        $this->assertSame(1, ParentGuardian::count());
        $this->assertSame('0249990000', $guardian->fresh()->phone);

        $this->admissionForm([
            'studentId' => 'STU-ADMISSION-003',
            'admissionNumber' => 'ADM-ADMISSION-003',
            'firstName' => 'Akua',
            'lastName' => 'Mensah',
            'email' => 'akua.student@example.test',
            'guardianEmail' => 'adwoa.renamed@example.test',
            'guardianPhone' => '(024) 999-0000',
        ])->call('save')->assertHasNoErrors();

        $this->assertSame(1, ParentGuardian::count());
        $this->assertSame(3, $guardian->students()->count());
        $this->assertSame('adwoa.renamed@example.test', $guardian->fresh()->email);
        $this->assertSame('adwoa.renamed@example.test', $guardian->user->fresh()->email);
        $this->assertSame(3, Student::count());
    }

    public function test_conflicting_guardian_email_and_phone_roll_back_the_complete_admission(): void
    {
        $emailGuardian = $this->existingGuardian(
            'Email Guardian',
            'email.guardian@example.test',
            '0241111111',
            'ExistingParentPass123!',
        );
        $phoneGuardian = $this->existingGuardian(
            'Phone Guardian',
            'phone.guardian@example.test',
            '0242222222',
            'OtherParentPass123!',
        );
        $usersBefore = User::count();

        $this->admissionForm([
            'studentId' => 'STU-CONFLICT-001',
            'admissionNumber' => 'ADM-CONFLICT-001',
            'email' => 'conflict.student@example.test',
            'guardianFirstName' => 'Conflicting',
            'guardianLastName' => 'Guardian',
            'guardianEmail' => $emailGuardian->email,
            'guardianPhone' => $phoneGuardian->phone,
        ])->call('save')->assertHasErrors(['guardianEmail', 'guardianPhone']);

        $this->assertDatabaseMissing('students', ['student_id' => 'STU-CONFLICT-001']);
        $this->assertDatabaseMissing('users', ['email' => 'conflict.student@example.test']);
        $this->assertSame($usersBefore, User::count());
        $this->assertSame(2, ParentGuardian::count());
        $this->assertSame('0241111111', $emailGuardian->fresh()->phone);
        $this->assertSame('phone.guardian@example.test', $phoneGuardian->fresh()->email);
    }

    public function test_reusing_a_guardian_account_does_not_replace_its_existing_password(): void
    {
        $guardian = $this->existingGuardian(
            'Existing Parent',
            'existing.parent@example.test',
            '0245556677',
            'ExistingParentPass123!',
        );
        $passwordHash = $guardian->user->password;

        $this->admissionForm([
            'studentId' => 'STU-PASSWORD-001',
            'admissionNumber' => 'ADM-PASSWORD-001',
            'email' => 'password.student@example.test',
            'guardianFirstName' => 'Existing',
            'guardianLastName' => 'Parent',
            'guardianEmail' => 'EXISTING.PARENT@EXAMPLE.TEST',
            'guardianPhone' => '024 555 6677',
        ])->call('save')->assertHasNoErrors();

        $guardianAccount = $guardian->user->fresh();

        $this->assertSame($passwordHash, $guardianAccount->password);
        $this->assertTrue(Hash::check('ExistingParentPass123!', $guardianAccount->password));
        $this->assertFalse(Hash::check('0245556677', $guardianAccount->password));
        $this->assertSame(1, ParentGuardian::count());
        $this->assertSame(1, $guardian->students()->count());
    }

    /** @param array<string, mixed> $overrides */
    private function admissionForm(array $overrides = []): Testable
    {
        $values = array_merge([
            'studentId' => 'STU-ADMISSION-001',
            'admissionNumber' => 'ADM-ADMISSION-001',
            'firstName' => 'Ama',
            'middleName' => '',
            'lastName' => 'Mensah',
            'email' => 'ama.student@example.test',
            'password' => 'StudentPass123!',
            'dateOfBirth' => '2015-05-10',
            'gender' => 'female',
            'homeTown' => 'Mampong',
            'region' => 'Ashanti',
            'nationality' => 'Ghanaian',
            'denomination' => '',
            'healthInsuranceId' => '',
            'admissionDate' => '2026-09-01',
            'schoolClassId' => (string) $this->schoolClass->id,
            'enrollmentType' => ClassEnrollment::ENROLLMENT_TYPE_DAY,
            'status' => 'active',
            'previousSchoolName' => '',
            'previousSchoolCity' => '',
            'previousSchoolCountry' => '',
            'previousSchoolGpsAddress' => '',
            'previousSchoolPhone' => '',
            'previousSchoolLastClass' => '',
            'guardianFirstName' => 'Adwoa',
            'guardianLastName' => 'Mensah',
            'guardianGpsAddress' => 'AK-456-7890',
            'guardianCity' => 'Kumasi',
            'guardianPhone' => '+233 24 123 4567',
            'guardianWorkplace' => '',
            'guardianEmail' => 'adwoa.guardian@example.test',
            'guardianGhanaCardNumber' => '',
            'guardianInformationDate' => '2026-09-01',
            'guardianRelationship' => 'Guardian',
            'hasAllergies' => false,
            'allergyDetails' => '',
        ], $overrides);

        $component = Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->call('create');

        $component->update(updates: $values);

        return $component;
    }

    private function existingGuardian(
        string $name,
        string $email,
        string $phone,
        string $password,
    ): ParentGuardian {
        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, 'Guardian');
        $account = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
        $account->assignRole('parent');

        return ParentGuardian::create([
            'user_id' => $account->id,
            'school_id' => $this->school->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'gps_address' => 'AK-100-2000',
            'city' => 'Kumasi',
        ]);
    }
}
