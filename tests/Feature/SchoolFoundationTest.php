<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_renders(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('BrightStar Academy');
    }

    public function test_lms_dashboard_requires_authentication(): void
    {
        $response = $this->get('/lms/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_roles_are_available_for_school_users(): void
    {
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'school_admin']);
        Role::firstOrCreate(['name' => 'teacher']);
        Role::firstOrCreate(['name' => 'student']);
        Role::firstOrCreate(['name' => 'parent']);

        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->assertTrue($user->hasRole('teacher'));
        $this->assertTrue($user->hasRole('teacher'));
    }
}
