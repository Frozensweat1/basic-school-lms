<?php

namespace Tests\Feature;

use App\Livewire\LMS\Settings\Index as SettingsIndex;
use App\Models\NewsletterSubscription;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Support\DatabaseMaintenance;
use App\Support\PublicWebsiteData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseResetRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_super_admin_can_log_back_in_and_reconfigure_settings_after_a_reset(): void
    {
        Role::findOrCreate('super_admin');
        Role::findOrCreate('teacher');

        $superAdmin = User::factory()->create([
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ]);
        $superAdmin->assignRole('super_admin');

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $school = School::create(['name' => 'School Before Reset', 'code' => 'OLD']);
        SchoolSetting::create([
            'school_id' => $school->id,
            'key' => 'hero_title',
            'value' => ['value' => 'Old homepage title'],
        ]);
        WebsiteSetting::create(['site_name' => 'Old Public Brand']);
        NewsletterSubscription::create(['email' => 'family@example.com']);

        $this->assertSame('Old Public Brand', app(PublicWebsiteData::class)->branding()['name']);

        $preserved = app(DatabaseMaintenance::class)->resetPreservingSuperAdmins();

        $this->assertSame(1, $preserved);
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'remember_token' => null]);
        $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => User::class,
            'model_id' => $superAdmin->id,
        ]);
        $this->assertDatabaseCount('schools', 0);
        $this->assertDatabaseCount('school_settings', 0);
        $this->assertDatabaseCount('website_settings', 0);
        $this->assertDatabaseCount('newsletter_subscriptions', 0);
        $this->assertSame('BrightStar Academy', app(PublicWebsiteData::class)->branding()['name']);

        $token = 'reset-recovery-csrf-token';
        $this->withSession(['_token' => $token])->post(route('login.store'), [
            'email' => $superAdmin->email,
            'password' => 'password',
            '_token' => $token,
        ])->assertRedirect(route('lms.dashboard'));

        $this->assertAuthenticatedAs($superAdmin->fresh());
        $this->get(route('lms.settings.index'))
            ->assertOk()
            ->assertSee('School profile ready for setup')
            ->assertSee('Create school profile');

        Livewire::actingAs($superAdmin->fresh())
            ->test(SettingsIndex::class)
            ->assertSet('isInitialSetup', true)
            ->set('name', 'School After Reset')
            ->set('code', 'NEW')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('isInitialSetup', false)
            ->set('name', 'Updated School After Reset')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('schools', 1);
        $this->assertDatabaseHas('schools', [
            'name' => 'Updated School After Reset',
            'code' => 'NEW',
        ]);
        $this->assertDatabaseHas('school_settings', [
            'key' => 'hero_title',
        ]);
    }
}
