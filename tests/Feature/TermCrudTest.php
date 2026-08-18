<?php

namespace Tests\Feature;

use App\Livewire\LMS\Terms\Index;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TermCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_an_active_term_for_the_active_year(): void
    {
        Role::create(['name' => 'school_admin']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026/2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-07-31', 'is_active' => true]);

        Livewire::actingAs($user)->test(Index::class)
            ->call('create')->set('academicYearId', (string) $year->id)->set('name', 'Term 1')->set('sequence', '1')
            ->set('startsAt', '2026-09-01')->set('endsAt', '2026-12-18')->set('isActive', true)->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('terms', ['academic_year_id' => $year->id, 'name' => 'Term 1', 'is_active' => true]);
    }
}
