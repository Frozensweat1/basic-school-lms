<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LmsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'school_admin', 'teacher', 'student', 'parent'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }
    }

    public function test_school_admin_can_access_school_setup_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('school_admin');

        $this->actingAs($user)
            ->get('/lms/school-setup')
            ->assertOk();
    }

    public function test_teacher_cannot_access_school_setup_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->actingAs($user)
            ->get('/lms/school-setup')
            ->assertForbidden();
    }

    public function test_parent_can_view_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('parent');

        $this->actingAs($user)
            ->get('/lms/dashboard')
            ->assertOk();
    }
}
