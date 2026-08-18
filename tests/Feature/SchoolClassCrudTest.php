<?php

namespace Tests\Feature;

use App\Livewire\LMS\Classes\Index;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Stream;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolClassCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_a_class_for_an_academic_year(): void
    {
        Role::create(['name' => 'school_admin']);
        $user = User::factory()->create();
        $user->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026/2027', 'starts_at' => '2026-09-01', 'ends_at' => '2027-07-31', 'is_active' => true]);
        $stream = Stream::create(['school_id' => $school->id, 'name' => 'Blue']);

        Livewire::actingAs($user)->test(Index::class)
            ->call('create')->set('academicYearId', (string) $year->id)->set('streamId', (string) $stream->id)
            ->set('name', 'Basic 1')->set('code', 'B1')->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('school_classes', ['academic_year_id' => $year->id, 'stream_id' => $stream->id, 'name' => 'Basic 1', 'code' => 'B1']);
    }
}
