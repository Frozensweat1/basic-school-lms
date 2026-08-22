<?php

namespace Tests\Feature;

use App\Livewire\LMS\Teachers\Index;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_add_a_teacher(): void
    {
        [$user, $school] = $this->schoolAdmin();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->set('employeeId', 'T-001')
            ->set('firstName', 'Ama')
            ->set('lastName', 'Mensah')
            ->set('email', 'ama@example.test')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teachers', [
            'school_id' => $school->id,
            'employee_id' => 'T-001',
            'first_name' => 'Ama',
        ]);
    }

    public function test_school_admin_can_search_and_filter_teachers(): void
    {
        [$user, $school] = $this->schoolAdmin();
        Teacher::create([
            'school_id' => $school->id,
            'employee_id' => 'T-100',
            'first_name' => 'Esi',
            'last_name' => 'Acquah',
            'email' => 'esi@example.test',
            'status' => 'active',
        ]);
        Teacher::create([
            'school_id' => $school->id,
            'employee_id' => 'T-200',
            'first_name' => 'Yaw',
            'last_name' => 'Dapaah',
            'phone' => '0200000000',
            'status' => 'retired',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('search', 'esi@example.test')
            ->assertSee('Esi')
            ->assertDontSee('Yaw')
            ->set('search', '')
            ->set('filterStatus', 'retired')
            ->assertSee('Yaw')
            ->assertDontSee('Esi')
            ->call('clearFilters')
            ->assertSet('filterStatus', '')
            ->assertSee('Esi')
            ->assertSee('Yaw');
    }

    /** @return array{0: User, 1: School} */
    private function schoolAdmin(): array
    {
        Role::create(['name' => 'school_admin']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);

        return [$user, $school];
    }
}
