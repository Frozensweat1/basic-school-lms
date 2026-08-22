<?php

namespace Tests\Feature;

use App\Livewire\LMS\Subjects\Index;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubjectCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_a_subject(): void
    {
        Role::create(['name' => 'school_admin']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->set('name', 'Mathematics')
            ->set('code', 'MATH')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('subjects', [
            'school_id' => $school->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
        ]);
    }

    public function test_subject_search_matches_name_code_description_and_status(): void
    {
        Role::create(['name' => 'school_admin']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);

        Subject::create([
            'school_id' => $school->id,
            'name' => 'Civics',
            'code' => 'CIV',
            'description' => 'Responsible citizenship',
            'is_active' => true,
        ]);
        Subject::create([
            'school_id' => $school->id,
            'name' => 'Robotics',
            'code' => 'ROB',
            'description' => 'Archived specialist course',
            'is_active' => false,
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('search', 'CIV')
            ->assertSee('Civics')
            ->assertDontSee('Robotics')
            ->set('search', 'archived')
            ->assertSee('Robotics')
            ->assertDontSee('Civics')
            ->call('clearSearch')
            ->assertSet('search', '')
            ->assertSee('Civics')
            ->assertSee('Robotics');
    }
}
