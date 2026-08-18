<?php

namespace Tests\Feature;

use App\Livewire\LMS\AcademicYears\Index;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicYearCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_and_activate_an_academic_year(): void
    {
        Role::create(['name' => 'school_admin']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);
        $oldYear = AcademicYear::create(['school_id' => $school->id, 'name' => '2025/2026', 'starts_at' => '2025-09-01', 'ends_at' => '2026-07-31', 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->set('name', '2026/2027')
            ->set('startsAt', '2026-09-01')
            ->set('endsAt', '2027-07-31')
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('academic_years', ['school_id' => $school->id, 'name' => '2026/2027', 'is_active' => true]);
        $this->assertFalse($oldYear->fresh()->is_active);
    }
}
