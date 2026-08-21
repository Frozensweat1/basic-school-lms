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
            ->assertSet('showForm', false)
            ->call('create')
            ->assertSet('showForm', true)
            ->assertSee('New academic year')
            ->set('name', '2026/2027')
            ->set('startsAt', '2026-09-01')
            ->set('endsAt', '2027-07-31')
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('academic_years', ['school_id' => $school->id, 'name' => '2026/2027', 'is_active' => true]);
        $this->assertFalse($oldYear->fresh()->is_active);
    }

    public function test_academic_years_are_paginated_within_the_current_school(): void
    {
        Role::create(['name' => 'school_admin']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);

        foreach (range(1, 16) as $year) {
            AcademicYear::create([
                'school_id' => $school->id,
                'name' => sprintf('20%02d/20%02d', $year, $year + 1),
                'starts_at' => now()->subYears($year)->startOfYear(),
                'ends_at' => now()->subYears($year)->endOfYear(),
            ]);
        }

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertViewHas('years', fn ($years) => $years->count() === 15 && $years->total() === 16);
    }
}
