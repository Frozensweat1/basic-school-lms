<?php

namespace Tests\Feature;

use App\Jobs\SendAnnouncementNotificationsJob;
use App\Livewire\LMS\Announcements\Admin\Manage as AdminAnnouncements;
use App\Livewire\LMS\Announcements\Feed as AnnouncementFeed;
use App\Livewire\LMS\Notifications\Index as NotificationInbox;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\LmsNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnnouncementAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_search_and_attach_a_file_to_an_announcement(): void
    {
        Storage::fake('local');
        [$admin, $school] = $this->adminAndSchool();
        $teacher = $this->teacherUser($school);

        Livewire::actingAs($admin)
            ->test(AdminAnnouncements::class)
            ->call('create')
            ->assertSee('Create announcement')
            ->assertSeeHtml('z-[100]')
            ->set('title', 'Family engagement meeting')
            ->set('content', '<p>Please attend on Friday.</p><script>alert(1)</script>')
            ->set('audience', 'teachers')
            ->set('publicationMode', 'publish_now')
            ->set('attachmentFiles', [UploadedFile::fake()->create('agenda.pdf', 100, 'application/pdf')])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->set('search', 'Family engagement')
            ->assertSee('Family engagement meeting');

        $announcement = Announcement::query()->where('title', 'Family engagement meeting')->firstOrFail();
        $this->assertStringNotContainsString('<script', $announcement->content);
        $this->assertNotNull($announcement->notified_at);
        $this->assertCount(1, $announcement->attachments);
        Storage::disk('local')->assertExists($announcement->attachments->first()->path);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $teacher->id,
            'notifiable_type' => User::class,
        ]);
        $this->actingAs($admin)
            ->get(route('lms.announcements.admin.manage'))
            ->assertOk()
            ->assertSee('New announcement')
            ->assertSee('Notifications');
    }

    public function test_scheduled_announcement_delivery_is_idempotent(): void
    {
        [$admin, $school] = $this->adminAndSchool();
        $teacher = $this->teacherUser($school);
        $announcement = Announcement::create([
            'school_id' => $school->id,
            'created_by' => $admin->id,
            'title' => 'Scheduled staff briefing',
            'content' => '<p>Staff briefing details.</p>',
            'audience' => 'teachers',
            'published_at' => now()->subMinute(),
        ]);

        $job = new SendAnnouncementNotificationsJob($announcement->id);
        $job->handle();
        $job->handle();

        $this->assertNotNull($announcement->fresh()->notified_at);
        $this->assertSame(1, $teacher->notifications()->count());
    }

    public function test_student_feed_only_contains_current_relevant_announcements(): void
    {
        [$admin, $school] = $this->adminAndSchool();
        [$studentUser, $class, $subject] = $this->enrolledStudent($school);
        $otherClass = SchoolClass::create([
            'academic_year_id' => $class->academic_year_id,
            'name' => 'Basic 6',
            'status' => 'active',
        ]);

        $this->announcement($admin, $school, 'School notice', 'school');
        $this->announcement($admin, $school, 'Class notice', 'class', $class->id);
        $this->announcement($admin, $school, 'Subject notice', 'subject', null, $subject->id);
        $this->announcement($admin, $school, 'Other class notice', 'class', $otherClass->id);
        $this->announcement($admin, $school, 'Future notice', 'school', null, null, now()->addDay());
        $this->announcement($admin, $school, 'Expired notice', 'school', null, null, now()->subDay(), now()->subHour());

        Livewire::actingAs($studentUser)
            ->test(AnnouncementFeed::class)
            ->assertSee('School notice')
            ->assertSee('Class notice')
            ->assertSee('Subject notice')
            ->assertDontSee('Other class notice')
            ->assertDontSee('Future notice')
            ->assertDontSee('Expired notice')
            ->set('search', 'Subject')
            ->assertSee('Subject notice')
            ->assertDontSee('Class notice');
    }

    public function test_notification_inbox_supports_filters_read_state_and_safe_deletion(): void
    {
        [$admin] = $this->adminAndSchool();
        $admin->notify(new LmsNotification('New announcement', 'Please review it.', '/lms/announcements', 'announcement'));
        $admin->notify(new LmsNotification('Attendance update', 'Attendance was recorded.', '/lms/attendance', 'attendance'));
        $admin->notify(new LmsNotification('Unsafe redirect', 'This URL must not open.', '//external.example', 'info'));
        $announcementNotification = $admin->notifications()->where('data->kind', 'announcement')->firstOrFail();
        $unsafeNotification = $admin->notifications()->where('data->title', 'Unsafe redirect')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(NotificationInbox::class)
            ->assertSee('New announcement')
            ->assertSee('Attendance update')
            ->call('openNotification', $unsafeNotification->id)
            ->assertNoRedirect()
            ->set('filterKind', 'announcement')
            ->assertSee('New announcement')
            ->assertDontSee('Attendance update')
            ->call('markRead', $announcementNotification->id)
            ->set('filterState', 'read')
            ->assertSee('New announcement')
            ->call('markUnread', $announcementNotification->id)
            ->set('filterState', 'unread')
            ->assertSee('New announcement')
            ->call('confirmDelete', $announcementNotification->id)
            ->assertSet('showDeleteModal', true)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('notifications', ['id' => $announcementNotification->id]);
    }

    private function adminAndSchool(): array
    {
        Role::findOrCreate('school_admin');
        $admin = User::factory()->create();
        $admin->assignRole('school_admin');
        $school = School::create(['name' => 'BrightStar Academy', 'code' => 'BSA']);

        return [$admin, $school];
    }

    private function teacherUser(School $school): User
    {
        Role::findOrCreate('teacher');
        $user = User::factory()->create();
        $user->assignRole('teacher');
        Teacher::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'employee_id' => 'TCH-'.$user->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'status' => 'active',
        ]);

        return $user;
    }

    private function enrolledStudent(School $school): array
    {
        Role::findOrCreate('student');
        $user = User::factory()->create();
        $user->assignRole('student');
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-07-31',
            'is_active' => true,
        ]);
        $class = SchoolClass::create(['academic_year_id' => $year->id, 'name' => 'Basic 5', 'status' => 'active']);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Science', 'code' => 'SCI', 'is_active' => true]);
        ClassSubject::create(['school_class_id' => $class->id, 'subject_id' => $subject->id]);
        $student = Student::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'student_id' => 'STU-'.$user->id,
            'admission_number' => 'ADM-'.$user->id,
            'first_name' => 'Kojo',
            'last_name' => 'Owusu',
            'date_of_birth' => '2015-01-01',
            'gender' => 'male',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
        ClassEnrollment::create([
            'school_class_id' => $class->id,
            'student_id' => $student->id,
            'enrolled_at' => '2026-09-01',
            'status' => 'active',
        ]);

        return [$user, $class, $subject];
    }

    private function announcement(
        User $author,
        School $school,
        string $title,
        string $audience,
        ?int $classId = null,
        ?int $subjectId = null,
        mixed $publishedAt = null,
        mixed $expiresAt = null,
    ): Announcement {
        return Announcement::create([
            'school_id' => $school->id,
            'school_class_id' => $classId,
            'subject_id' => $subjectId,
            'created_by' => $author->id,
            'title' => $title,
            'content' => '<p>'.$title.' content.</p>',
            'audience' => $audience,
            'published_at' => $publishedAt ?? now()->subMinute(),
            'expires_at' => $expiresAt,
        ]);
    }
}
