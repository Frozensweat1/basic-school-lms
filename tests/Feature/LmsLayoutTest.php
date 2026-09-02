<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Support\LmsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LmsLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_lms_shell_uses_school_branding_and_route_aware_module_titles(): void
    {
        Role::findOrCreate('school_admin');
        $user = User::factory()->create(['name' => 'Ama Mensah']);
        $user->assignRole('school_admin');
        School::create(['name' => 'Ridgeway Basic School', 'code' => 'RBS']);
        WebsiteSetting::create(['site_name' => 'Public Website Name']);

        $this->actingAs($user)
            ->get(route('lms.notifications.index'))
            ->assertOk()
            ->assertSee('<title>Notifications | Ridgeway Basic School</title>', false)
            ->assertSee('Ridgeway Basic School')
            ->assertDontSee('Public Website Name')
            ->assertSee('Learning management system')
            ->assertSee('LMS')
            ->assertSee('id="content-sidebar-toggle"', false)
            ->assertSee('id="theme-toggle"', false)
            ->assertSee('id="sidebar-backdrop"', false)
            ->assertSee('aria-controls="lms-sidebar"', false)
            ->assertSee(route('lms.users.index'), false)
            ->assertSee(route('lms.academic-years.index'), false)
            ->assertSee(route('lms.emails.index'), false)
            ->assertSee(route('lms.students.promotions.index'), false);
    }

    public function test_non_administrators_do_not_receive_administrative_sidebar_links(): void
    {
        Role::findOrCreate('student');
        $student = User::factory()->create();
        $student->assignRole('student');
        School::create(['name' => 'Ridgeway Basic School', 'code' => 'RBS']);

        $this->actingAs($student)
            ->get(route('lms.notifications.index'))
            ->assertOk()
            ->assertDontSee(route('lms.users.index'), false)
            ->assertDontSee(route('lms.roles.index'), false)
            ->assertDontSee(route('lms.permissions.index'), false)
            ->assertDontSee(route('lms.settings.index'), false)
            ->assertDontSee(route('lms.school-setup'), false)
            ->assertDontSee(route('lms.academic-years.index'), false)
            ->assertDontSee(route('lms.students.index'), false)
            ->assertDontSee(route('lms.emails.index'), false)
            ->assertDontSee(route('lms.students.promotions.index'), false)
            ->assertDontSee('Academic Setup')
            ->assertDontSee('Administration');
    }

    public function test_lms_page_titles_cover_nested_workflows(): void
    {
        $titles = app(LmsPage::class);

        $this->assertSame('Assessment Scores', $titles->title('lms.assessments.admin.scores.index'));
        $this->assertSame('Quiz Questions', $titles->title('lms.quizzes.teacher.questions.index'));
        $this->assertSame('Assignment Submissions', $titles->title('lms.assignments.teacher.grade'));
        $this->assertSame('Timetable Entries', $titles->title('lms.timetables.entries.index'));
        $this->assertSame('Email Centre', $titles->title('lms.emails.index'));
        $this->assertSame('Student Promotions', $titles->title('lms.students.promotions.index'));
        $this->assertSame('Website Settings', $titles->title('lms.website.settings'));
    }
}
