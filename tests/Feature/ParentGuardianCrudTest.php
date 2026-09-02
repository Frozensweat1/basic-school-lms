<?php

namespace Tests\Feature;

use App\Livewire\LMS\Parents\Index;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParentGuardianCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_add_a_parent_and_link_a_student(): void
    {
        Role::create(['name' => 'school_admin']);
        Role::create(['name' => 'parent']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');

        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);
        $student = Student::create([
            'school_id' => $school->id,
            'student_id' => 'STU-001',
            'admission_number' => 'ADM-001',
            'first_name' => 'Kojo',
            'last_name' => 'Owusu',
            'date_of_birth' => '2015-05-10',
            'gender' => 'male',
            'admission_date' => '2026-01-12',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->set('firstName', 'Adwoa')
            ->set('lastName', 'Owusu')
            ->set('phone', '0200000000')
            ->set('email', 'adwoa.owusu@example.test')
            ->set('address', '12 Liberation Road')
            ->set('gpsAddress', 'GA-123-4567')
            ->set('city', 'Accra')
            ->set('workplace', 'Owusu Trading Company')
            ->set('ghanaCardNumber', 'GHA-123456789-0')
            ->set('relationship', 'Mother')
            ->set('studentIds', [(string) $student->id])
            ->call('save')
            ->assertHasNoErrors();

        $parent = ParentGuardian::firstOrFail();

        $this->assertDatabaseHas('parents', [
            'school_id' => $school->id,
            'first_name' => 'Adwoa',
            'last_name' => 'Owusu',
            'user_id' => $parent->user_id,
            'phone' => '0200000000',
            'address' => '12 Liberation Road',
            'gps_address' => 'GA-123-4567',
            'city' => 'Accra',
            'workplace' => 'Owusu Trading Company',
            'ghana_card_number' => 'GHA-123456789-0',
        ]);
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'relationship' => 'Mother',
        ]);
        $this->assertSame('adwoa.owusu@example.test', $parent->user->email);
        $this->assertSame('Adwoa Owusu', $parent->user->name);
        $this->assertTrue($parent->user->hasRole('parent'));
        $this->assertTrue(Hash::check('0200000000', $parent->user->password));
    }

    public function test_editing_a_linked_parent_keeps_the_existing_password(): void
    {
        Role::create(['name' => 'school_admin']);
        Role::create(['name' => 'parent']);
        $admin = User::factory()->create();
        $admin->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);
        $account = User::factory()->create([
            'name' => 'Ama Mensah',
            'email' => 'ama@example.test',
            'password' => 'OriginalParentPass123!',
        ]);
        $account->assignRole('parent');
        $parent = ParentGuardian::create([
            'user_id' => $account->id,
            'school_id' => $school->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'phone' => '024 111 2233',
            'email' => 'ama@example.test',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('edit', $parent->id)
            ->set('phone', '+233 24 999 8877')
            ->set('city', 'Tema')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('OriginalParentPass123!', $account->fresh()->password));
        $this->assertFalse(Hash::check('233249998877', $account->fresh()->password));
        $this->assertDatabaseHas('parents', [
            'id' => $parent->id,
            'phone' => '+233 24 999 8877',
            'city' => 'Tema',
        ]);
    }

    public function test_school_admin_can_search_guardians_by_contact_or_linked_student(): void
    {
        Role::create(['name' => 'school_admin']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);
        $student = Student::create([
            'school_id' => $school->id,
            'student_id' => 'STU-100',
            'admission_number' => 'ADM-100',
            'first_name' => 'Nana',
            'last_name' => 'Ofori',
            'date_of_birth' => '2015-05-10',
            'gender' => 'male',
            'admission_date' => '2026-01-12',
        ]);
        $firstParent = ParentGuardian::create([
            'school_id' => $school->id,
            'first_name' => 'Akosua',
            'last_name' => 'Asiedu',
            'email' => 'akosua@example.test',
        ]);
        $firstParent->students()->attach($student->id, ['relationship' => 'Mother', 'is_primary_contact' => true]);
        ParentGuardian::create([
            'school_id' => $school->id,
            'first_name' => 'Kwame',
            'last_name' => 'Yeboah',
            'phone' => '0200000000',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('search', 'Nana')
            ->assertSee('Akosua')
            ->assertDontSee('Kwame')
            ->set('search', '0200000000')
            ->assertSee('Kwame')
            ->assertDontSee('Akosua')
            ->call('clearSearch')
            ->assertSet('search', '')
            ->assertSee('Akosua')
            ->assertSee('Kwame');
    }
}
