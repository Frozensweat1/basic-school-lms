<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_sign_in_and_reach_the_lms_dashboard(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $token = 'test-csrf-token';

        $this->withSession(['_token' => $token])->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            '_token' => $token,
        ])->assertRedirect(route('lms.dashboard'));

        $this->assertAuthenticatedAs($user);
    }
}
