<?php

namespace Tests\Feature;

use App\Livewire\LMS\Parents\Index;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParentGuardianCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_add_a_parent_and_link_a_student(): void
    {
        Role::create(['name' => 'school_admin']);
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
            ->set('relationship', 'Mother')
            ->set('studentIds', [(string) $student->id])
            ->call('save')
            ->assertHasNoErrors();

        $parent = ParentGuardian::firstOrFail();

        $this->assertDatabaseHas('parents', [
            'school_id' => $school->id,
            'first_name' => 'Adwoa',
            'last_name' => 'Owusu',
        ]);
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'relationship' => 'Mother',
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
