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

    public function test_login_page_uses_flex_loading_state_for_submit_button(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-submit-loading')
            ->assertSee('button.is-loading [data-submit-label]')
            ->assertSee('button.is-loading [data-submit-loading]');
    }

    public function test_registration_page_guides_users_to_contact_the_administrator(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Contact the administrator')
            ->assertDontSee('<form')
            ->assertDontSee('Create account');
    }
}
