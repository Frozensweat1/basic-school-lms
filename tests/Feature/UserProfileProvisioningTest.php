<?php

namespace Tests\Feature;

use App\Livewire\LMS\Parents\Index as ParentsIndex;
use App\Livewire\LMS\Students\Index as StudentsIndex;
use App\Livewire\LMS\Teachers\Index as TeachersIndex;
use App\Livewire\LMS\Users\Index as UsersIndex;
use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserProfileProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private SchoolClass $schoolClass;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'school_admin', 'teacher', 'student', 'parent'] as $role) {
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

    public function test_teacher_module_creates_a_linked_teacher_login_account(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeachersIndex::class)
            ->call('create')
            ->set('employeeId', 'TCH-LOGIN-001')
            ->set('firstName', 'Ama')
            ->set('middleName', 'Serwaa')
            ->set('lastName', 'Mensah')
            ->set('email', 'ama.teacher@example.test')
            ->set('password', 'TeacherPass123!')
            ->call('save')
            ->assertHasNoErrors();

        $teacher = Teacher::where('employee_id', 'TCH-LOGIN-001')->firstOrFail();
        $account = $teacher->user()->firstOrFail();

        $this->assertSame('Ama Serwaa Mensah', $account->name);
        $this->assertSame('ama.teacher@example.test', $account->email);
        $this->assertTrue(Hash::check('TeacherPass123!', $account->password));
        $this->assertTrue($account->hasRole('teacher'));
        $this->assertNull($account->student);
        $this->assertNull($account->parentGuardian);
    }

    public function test_a_teacher_created_by_the_teacher_module_can_log_in_and_open_their_dashboard(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TeachersIndex::class)
            ->call('create')
            ->set('employeeId', 'TCH-LOGIN-002')
            ->set('firstName', 'Yaw')
            ->set('lastName', 'Boateng')
            ->set('email', 'yaw.teacher@example.test')
            ->set('password', 'TeacherPass123!')
            ->call('save')
            ->assertHasNoErrors();

        $account = User::where('email', 'yaw.teacher@example.test')->firstOrFail();
        auth()->logout();

        $token = 'persona-login-csrf-token';
        $this->withSession(['_token' => $token])->post(route('login.store'), [
            'email' => 'yaw.teacher@example.test',
            'password' => 'TeacherPass123!',
            '_token' => $token,
        ])->assertRedirect(route('lms.dashboard'));

        $this->assertAuthenticatedAs($account);
        $this->get(route('lms.dashboard'))->assertRedirect(route('lms.dashboard.teacher'));
        $this->get(route('lms.dashboard.teacher'))->assertOk();
    }

    public function test_student_module_creates_a_linked_student_login_and_enrollment(): void
    {
        Livewire::actingAs($this->admin)
            ->test(StudentsIndex::class)
            ->call('create')
            ->set('studentId', 'STU-LOGIN-001')
            ->set('admissionNumber', 'ADM-LOGIN-001')
            ->set('firstName', 'Kojo')
            ->set('lastName', 'Asare')
            ->set('email', 'kojo.student@example.test')
            ->set('password', 'StudentPass123!')
            ->set('dateOfBirth', '2015-05-10')
            ->set('gender', 'male')
            ->set('homeTown', 'Accra')
            ->set('region', 'Greater Accra')
            ->set('nationality', 'Ghanaian')
            ->set('admissionDate', '2026-09-01')
            ->set('schoolClassId', (string) $this->schoolClass->id)
            ->set('guardianFirstName', 'Abena')
            ->set('guardianLastName', 'Asare')
            ->set('guardianEmail', 'abena.parent@example.test')
            ->set('guardianPhone', '0241000001')
            ->set('guardianInformationDate', '2026-09-01')
            ->set('guardianGpsAddress', 'GA-100-0001')
            ->set('guardianCity', 'Accra')
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::where('student_id', 'STU-LOGIN-001')->firstOrFail();
        $account = $student->user()->firstOrFail();

        $this->assertSame('Kojo Asare', $account->name);
        $this->assertSame('kojo.student@example.test', $account->email);
        $this->assertTrue(Hash::check('StudentPass123!', $account->password));
        $this->assertTrue($account->hasRole('student'));
        $this->assertDatabaseHas('class_enrollments', [
            'student_id' => $student->id,
            'school_class_id' => $this->schoolClass->id,
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);
    }

    public function test_parent_module_creates_a_linked_parent_login_and_ward_relationship(): void
    {
        $ward = $this->createWard('WARD-MODULE');

        Livewire::actingAs($this->admin)
            ->test(ParentsIndex::class)
            ->call('create')
            ->set('firstName', 'Adwoa')
            ->set('lastName', 'Owusu')
            ->set('email', 'adwoa.parent@example.test')
            ->set('phone', '0242000002')
            ->set('relationship', 'Mother')
            ->set('studentIds', [(string) $ward->id])
            ->call('save')
            ->assertHasNoErrors();

        $parent = ParentGuardian::where('email', 'adwoa.parent@example.test')->firstOrFail();
        $account = $parent->user()->firstOrFail();

        $this->assertSame('Adwoa Owusu', $account->name);
        $this->assertTrue(Hash::check('0242000002', $account->password));
        $this->assertTrue($account->hasRole('parent'));
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $ward->id,
            'relationship' => 'Mother',
        ]);
    }

    public function test_users_module_creates_the_matching_teacher_profile(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UsersIndex::class)
            ->call('create')
            ->set('role', 'teacher')
            ->set('email', 'users.teacher@example.test')
            ->set('password', 'TeacherPass123!')
            ->set('firstName', 'Esi')
            ->set('lastName', 'Acquah')
            ->set('employeeId', 'TCH-USERS-001')
            ->set('employmentDate', '2026-08-01')
            ->call('save')
            ->assertHasNoErrors();

        $account = User::where('email', 'users.teacher@example.test')->firstOrFail();
        $teacher = Teacher::where('user_id', $account->id)->firstOrFail();

        $this->assertSame('Esi Acquah', $account->name);
        $this->assertSame($this->school->id, $teacher->school_id);
        $this->assertSame('TCH-USERS-001', $teacher->employee_id);
        $this->assertTrue($account->hasRole('teacher'));
    }

    public function test_users_module_creates_the_matching_student_profile_and_enrollment(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UsersIndex::class)
            ->call('create')
            ->set('role', 'student')
            ->set('email', 'users.student@example.test')
            ->set('password', 'StudentPass123!')
            ->set('firstName', 'Nana')
            ->set('lastName', 'Ofori')
            ->set('studentId', 'STU-USERS-001')
            ->set('admissionNumber', 'ADM-USERS-001')
            ->set('dateOfBirth', '2015-03-14')
            ->set('gender', 'female')
            ->set('homeTown', 'Koforidua')
            ->set('region', 'Eastern')
            ->set('nationality', 'Ghanaian')
            ->set('admissionDate', '2026-09-01')
            ->set('schoolClassId', (string) $this->schoolClass->id)
            ->set('guardianFirstName', 'Afia')
            ->set('guardianLastName', 'Ofori')
            ->set('guardianEmail', 'afia.parent@example.test')
            ->set('guardianPhone', '0243000003')
            ->set('guardianInformationDate', '2026-09-01')
            ->set('guardianGpsAddress', 'EN-100-0001')
            ->set('guardianCity', 'Koforidua')
            ->call('save')
            ->assertHasNoErrors();

        $account = User::where('email', 'users.student@example.test')->firstOrFail();
        $student = Student::where('user_id', $account->id)->firstOrFail();

        $this->assertSame('Nana Ofori', $account->name);
        $this->assertSame('STU-USERS-001', $student->student_id);
        $this->assertTrue($account->hasRole('student'));
        $this->assertDatabaseHas('class_enrollments', [
            'student_id' => $student->id,
            'school_class_id' => $this->schoolClass->id,
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);
    }

    public function test_users_module_creates_the_matching_parent_profile_and_ward_relationship(): void
    {
        $ward = $this->createWard('WARD-USERS');

        Livewire::actingAs($this->admin)
            ->test(UsersIndex::class)
            ->call('create')
            ->set('role', 'parent')
            ->set('email', 'users.parent@example.test')
            ->set('password', 'ParentPass123!')
            ->set('firstName', 'Akosua')
            ->set('lastName', 'Mensah')
            ->set('relationship', 'Guardian')
            ->set('studentIds', [(string) $ward->id])
            ->call('save')
            ->assertHasNoErrors();

        $account = User::where('email', 'users.parent@example.test')->firstOrFail();
        $parent = ParentGuardian::where('user_id', $account->id)->firstOrFail();

        $this->assertSame('Akosua Mensah', $account->name);
        $this->assertSame('users.parent@example.test', $parent->email);
        $this->assertTrue($account->hasRole('parent'));
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $ward->id,
            'relationship' => 'Guardian',
        ]);
    }

    public function test_conflicting_role_email_rolls_back_profile_creation(): void
    {
        $studentAccount = User::factory()->create([
            'email' => 'existing.student@example.test',
        ]);
        $studentAccount->assignRole('student');
        Student::create([
            'user_id' => $studentAccount->id,
            'school_id' => $this->school->id,
            'student_id' => 'STU-EXISTING-001',
            'admission_number' => 'ADM-EXISTING-001',
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'date_of_birth' => '2015-01-10',
            'gender' => 'female',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->admin)
            ->test(TeachersIndex::class)
            ->call('create')
            ->set('employeeId', 'TCH-CONFLICT-001')
            ->set('firstName', 'Wrong')
            ->set('lastName', 'Role')
            ->set('email', 'existing.student@example.test')
            ->set('password', 'TeacherPass123!')
            ->call('save')
            ->assertHasErrors(['email']);

        $this->assertDatabaseMissing('teachers', [
            'employee_id' => 'TCH-CONFLICT-001',
        ]);
        $this->assertDatabaseHas('students', [
            'user_id' => $studentAccount->id,
            'student_id' => 'STU-EXISTING-001',
        ]);
        $this->assertTrue($studentAccount->fresh()->hasRole('student'));
    }

    public function test_an_existing_teacher_role_account_is_reused_by_the_teacher_module(): void
    {
        $account = User::factory()->create([
            'name' => 'Legacy Teacher',
            'email' => 'legacy.teacher@example.test',
        ]);
        $account->assignRole('teacher');

        Livewire::actingAs($this->admin)
            ->test(TeachersIndex::class)
            ->call('create')
            ->set('employeeId', 'TCH-LEGACY-001')
            ->set('firstName', 'Legacy')
            ->set('lastName', 'Teacher')
            ->set('email', 'legacy.teacher@example.test')
            ->call('save')
            ->assertHasNoErrors();

        $teacher = Teacher::where('employee_id', 'TCH-LEGACY-001')->firstOrFail();

        $this->assertSame($account->id, $teacher->user_id);
        $this->assertSame(2, User::count());
        $this->assertTrue($account->fresh()->hasRole('teacher'));
    }

    public function test_editing_a_linked_teacher_synchronizes_account_identity_and_keeps_blank_password(): void
    {
        $account = User::factory()->create([
            'name' => 'Old Teacher',
            'email' => 'old.teacher@example.test',
            'password' => 'OriginalPass123!',
        ]);
        $account->assignRole('teacher');
        $teacher = Teacher::create([
            'user_id' => $account->id,
            'school_id' => $this->school->id,
            'employee_id' => 'TCH-EDIT-001',
            'first_name' => 'Old',
            'last_name' => 'Teacher',
            'email' => $account->email,
            'status' => 'active',
        ]);

        Livewire::actingAs($this->admin)
            ->test(TeachersIndex::class)
            ->call('edit', $teacher->id)
            ->set('firstName', 'Updated')
            ->set('lastName', 'Teacher')
            ->set('email', 'updated.teacher@example.test')
            ->set('password', '')
            ->call('save')
            ->assertHasNoErrors();

        $account->refresh();
        $teacher->refresh();

        $this->assertSame('Updated Teacher', $account->name);
        $this->assertSame('updated.teacher@example.test', $account->email);
        $this->assertSame('updated.teacher@example.test', $teacher->email);
        $this->assertTrue(Hash::check('OriginalPass123!', $account->password));
    }

    private function createWard(string $identifier): Student
    {
        return Student::create([
            'school_id' => $this->school->id,
            'student_id' => 'STU-'.$identifier,
            'admission_number' => 'ADM-'.$identifier,
            'first_name' => 'Test',
            'last_name' => 'Ward',
            'date_of_birth' => '2015-05-10',
            'gender' => 'male',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
    }
}
