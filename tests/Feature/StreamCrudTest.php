<?php

namespace Tests\Feature;

use App\Livewire\LMS\Streams\Index;
use App\Models\School;
use App\Models\Stream;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StreamCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_a_stream(): void
    {
        Role::create(['name' => 'school_admin']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->set('name', 'Blue')
            ->set('description', 'Blue stream')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('streams', [
            'school_id' => $school->id,
            'name' => 'Blue',
            'is_active' => true,
        ]);
    }

    public function test_stream_search_matches_name_description_and_status(): void
    {
        Role::create(['name' => 'school_admin']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);

        Stream::create([
            'school_id' => $school->id,
            'name' => 'Crimson',
            'description' => 'Upper primary learners',
            'is_active' => true,
        ]);
        Stream::create([
            'school_id' => $school->id,
            'name' => 'Silver',
            'description' => 'Archived section',
            'is_active' => false,
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('search', 'Crimson')
            ->assertSee('Crimson')
            ->assertDontSee('Silver')
            ->set('search', 'archived')
            ->assertSee('Silver')
            ->assertDontSee('Crimson')
            ->call('clearSearch')
            ->assertSet('search', '')
            ->assertSee('Crimson')
            ->assertSee('Silver');
    }
}
