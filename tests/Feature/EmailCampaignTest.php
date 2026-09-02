<?php

namespace Tests\Feature;

use App\Jobs\DispatchEmailCampaignJob;
use App\Jobs\SendEmailRecipientJob;
use App\Livewire\LMS\Emails\Index;
use App\Mail\SchoolBroadcastMail;
use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\EmailCampaign;
use App\Models\EmailRecipient;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Emails\EmailCampaignService;
use App\Services\Emails\EmailRecipientResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailCampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_access_the_email_centre(): void
    {
        $setup = $this->schoolSetup();

        $this->actingAs($setup['admin'])
            ->get(route('lms.emails.index'))
            ->assertOk();

        Livewire::actingAs($setup['admin'])
            ->test(Index::class)
            ->assertOk();

        $this->actingAs($setup['teacherUser'])
            ->get(route('lms.emails.index'))
            ->assertForbidden();
    }

    public function test_bulk_resolution_is_class_scoped_uses_profile_fallbacks_and_privately_deduplicates_emails(): void
    {
        $setup = $this->schoolSetup();

        $profileTeacher = $this->createTeacher(
            $setup['school'],
            'EMP-EMAIL-001',
            'Shared@Example.test',
            User::factory()->create(['email' => 'unused-teacher@example.test']),
        );
        $fallbackTeacherUser = User::factory()->create(['email' => 'teacher-fallback@example.test']);
        $fallbackTeacher = $this->createTeacher(
            $setup['school'],
            'EMP-EMAIL-002',
            'not-a-valid-address',
            $fallbackTeacherUser,
        );
        $outsideTeacher = $this->createTeacher(
            $setup['school'],
            'EMP-EMAIL-003',
            'outside-teacher@example.test',
        );
        $setup['class']->teachers()->attach([$profileTeacher->id, $fallbackTeacher->id]);
        $setup['otherClass']->teachers()->attach($outsideTeacher->id);

        $uniqueStudent = $this->createStudent(
            $setup['school'],
            'EMAIL-001',
            User::factory()->create(['email' => 'student@example.test']),
        );
        $duplicateStudent = $this->createStudent(
            $setup['school'],
            'EMAIL-002',
            User::factory()->create(['email' => 'shared@example.TEST']),
        );
        $missingStudent = $this->createStudent($setup['school'], 'EMAIL-003');
        $outsideStudent = $this->createStudent(
            $setup['school'],
            'EMAIL-004',
            User::factory()->create(['email' => 'outside-student@example.test']),
        );

        foreach ([$uniqueStudent, $duplicateStudent, $missingStudent] as $student) {
            $this->enroll($student, $setup['class']);
        }
        $this->enroll($outsideStudent, $setup['otherClass']);

        $fallbackParent = ParentGuardian::create([
            'school_id' => $setup['school']->id,
            'user_id' => User::factory()->create(['email' => 'parent-fallback@example.test'])->id,
            'first_name' => 'Parent',
            'last_name' => 'Fallback',
            'email' => 'invalid-parent-address',
        ]);
        $fallbackParent->students()->attach($uniqueStudent->id, [
            'relationship' => 'mother',
            'is_primary_contact' => true,
        ]);

        $missingParent = ParentGuardian::create([
            'school_id' => $setup['school']->id,
            'first_name' => 'Parent',
            'last_name' => 'Missing',
        ]);
        $missingParent->students()->attach($missingStudent->id, [
            'relationship' => 'guardian',
            'is_primary_contact' => true,
        ]);

        $outsideParent = ParentGuardian::create([
            'school_id' => $setup['school']->id,
            'first_name' => 'Parent',
            'last_name' => 'Outside',
            'email' => 'outside-parent@example.test',
        ]);
        $outsideParent->students()->attach($outsideStudent->id, [
            'relationship' => 'father',
            'is_primary_contact' => true,
        ]);

        $resolution = app(EmailRecipientResolver::class)->resolveBulk(
            $setup['school']->id,
            ['students', 'staff', 'parents'],
            $setup['class']->id,
        );

        $this->assertSame(4, $resolution['recipients']->count());
        $this->assertSame(2, $resolution['missing_count']);
        $this->assertSame(1, $resolution['duplicate_count']);
        $this->assertSame([
            'shared@example.test',
            'teacher-fallback@example.test',
            'parent-fallback@example.test',
            'student@example.test',
        ], $resolution['recipients']->pluck('normalized_email')->all());
        $this->assertFalse($resolution['recipients']->pluck('email')->contains('outside-teacher@example.test'));
        $this->assertFalse($resolution['recipients']->pluck('email')->contains('outside-parent@example.test'));
        $this->assertFalse($resolution['recipients']->pluck('email')->contains('outside-student@example.test'));
        $this->assertTrue($resolution['skipped']->contains(
            fn (array $recipient): bool => $recipient['audience'] === 'students'
                && $recipient['recipient_id'] === $duplicateStudent->id
                && $recipient['skip_reason'] === 'Duplicate email address',
        ));
    }

    public function test_single_email_campaign_is_sanitized_snapshotted_and_queued(): void
    {
        Queue::fake();
        $setup = $this->schoolSetup();
        $recipient = $this->createTeacher(
            $setup['school'],
            'EMP-SINGLE-001',
            'single@example.test',
        );
        $this->actingAs($setup['admin']);

        $campaign = app(EmailCampaignService::class)->queueCampaign(
            schoolId: $setup['school']->id,
            createdBy: $setup['admin']->id,
            mode: 'single',
            audiences: [],
            schoolClassId: null,
            singleRecipientKey: 'staff:'.$recipient->id,
            subject: '  Parent meeting  ',
            body: '<p>Hello <strong>team</strong>.</p><script>alert("bad")</script><img src="javascript:alert(1)">',
        );

        $this->assertSame('Parent meeting', $campaign->subject);
        $this->assertSame('single', $campaign->mode);
        $this->assertSame(['staff'], $campaign->audiences);
        $this->assertSame(1, $campaign->recipient_count);
        $this->assertSame(0, $campaign->skipped_count);
        $this->assertStringContainsString('<strong>team</strong>', $campaign->body);
        $this->assertStringNotContainsStringIgnoringCase('<script', $campaign->body);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $campaign->body);
        $this->assertDatabaseHas('email_recipients', [
            'email_campaign_id' => $campaign->id,
            'audience' => 'staff',
            'recipient_id' => $recipient->id,
            'email' => 'single@example.test',
            'normalized_email' => 'single@example.test',
            'status' => EmailRecipient::STATUS_QUEUED,
        ]);
        Queue::assertPushedOn('emails', DispatchEmailCampaignJob::class, fn (DispatchEmailCampaignJob $job): bool => $job->campaignId === $campaign->id);
    }

    public function test_single_recipient_cannot_be_tampered_to_another_school(): void
    {
        Queue::fake();
        $setup = $this->schoolSetup();
        $otherSchool = School::create(['name' => 'Other Academy', 'code' => 'OTHER-EMAIL']);
        $otherTeacher = $this->createTeacher(
            $otherSchool,
            'EMP-FOREIGN-001',
            'foreign@example.test',
        );

        try {
            app(EmailCampaignService::class)->queueCampaign(
                schoolId: $setup['school']->id,
                createdBy: $setup['admin']->id,
                mode: 'single',
                audiences: [],
                schoolClassId: null,
                singleRecipientKey: 'staff:'.$otherTeacher->id,
                subject: 'Private notice',
                body: '<p>This must not cross schools.</p>',
            );
            $this->fail('A recipient from another school was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('singleRecipientKey', $exception->errors());
        }

        $this->assertDatabaseCount('email_campaigns', 0);
        $this->assertDatabaseCount('email_recipients', 0);
        Queue::assertNothingPushed();
    }

    public function test_recipient_job_sends_one_private_message_and_updates_delivery_totals(): void
    {
        Mail::fake();
        $setup = $this->schoolSetup();
        $campaign = $this->createCampaign($setup['school'], $setup['admin'], [
            'subject' => 'School update',
            'body' => '<p>The school will close at 2 PM.</p>',
            'recipient_count' => 1,
        ]);
        $recipient = EmailRecipient::create([
            'email_campaign_id' => $campaign->id,
            'audience' => 'parents',
            'recipient_type' => ParentGuardian::class,
            'recipient_id' => 44,
            'recipient_name' => 'Akosua Mensah',
            'email' => 'akosua@example.test',
            'normalized_email' => 'akosua@example.test',
            'status' => EmailRecipient::STATUS_QUEUED,
        ]);

        (new SendEmailRecipientJob($recipient->id))->handle(app(EmailCampaignService::class));
        (new SendEmailRecipientJob($recipient->id))->handle(app(EmailCampaignService::class));

        Mail::assertSentCount(1);
        Mail::assertSent(SchoolBroadcastMail::class, function (SchoolBroadcastMail $mail): bool {
            return $mail->hasTo('akosua@example.test')
                && count($mail->to) === 1
                && $mail->cc === []
                && $mail->bcc === []
                && $mail->campaign->subject === 'School update'
                && $mail->recipient->recipient_name === 'Akosua Mensah';
        });

        $recipient->refresh();
        $campaign->refresh();
        $this->assertSame(EmailRecipient::STATUS_SENT, $recipient->status);
        $this->assertSame(1, $recipient->attempts);
        $this->assertNotNull($recipient->sent_at);
        $this->assertSame(EmailCampaign::STATUS_COMPLETED, $campaign->status);
        $this->assertSame(1, $campaign->sent_count);
        $this->assertSame(0, $campaign->failed_count);
        $this->assertNotNull($campaign->completed_at);
    }

    public function test_failed_deliveries_can_be_requeued_without_resending_successes(): void
    {
        Queue::fake();
        $setup = $this->schoolSetup();
        $campaign = $this->createCampaign($setup['school'], $setup['admin'], [
            'status' => EmailCampaign::STATUS_PARTIAL,
            'recipient_count' => 2,
            'sent_count' => 1,
            'failed_count' => 1,
            'completed_at' => now(),
        ]);
        $sent = EmailRecipient::create([
            'email_campaign_id' => $campaign->id,
            'audience' => 'students',
            'recipient_type' => Student::class,
            'recipient_name' => 'Delivered Student',
            'email' => 'delivered@example.test',
            'normalized_email' => 'delivered@example.test',
            'status' => EmailRecipient::STATUS_SENT,
            'attempts' => 1,
            'sent_at' => now(),
        ]);
        $failed = EmailRecipient::create([
            'email_campaign_id' => $campaign->id,
            'audience' => 'students',
            'recipient_type' => Student::class,
            'recipient_name' => 'Failed Student',
            'email' => 'failed@example.test',
            'normalized_email' => 'failed@example.test',
            'status' => EmailRecipient::STATUS_FAILED,
            'attempts' => 3,
            'last_error' => 'Mailbox unavailable',
            'failed_at' => now(),
        ]);
        $this->actingAs($setup['admin']);

        $retried = app(EmailCampaignService::class)->retryFailed($campaign);

        $this->assertSame(1, $retried);
        $this->assertSame(EmailRecipient::STATUS_SENT, $sent->fresh()->status);
        $this->assertSame(EmailRecipient::STATUS_QUEUED, $failed->fresh()->status);
        $this->assertNull($failed->fresh()->last_error);
        $this->assertNull($failed->fresh()->failed_at);
        $this->assertSame(EmailCampaign::STATUS_QUEUED, $campaign->fresh()->status);
        $this->assertSame(0, $campaign->fresh()->failed_count);
        $this->assertNull($campaign->fresh()->completed_at);
        Queue::assertPushedOn('emails', DispatchEmailCampaignJob::class, fn (DispatchEmailCampaignJob $job): bool => $job->campaignId === $campaign->id);
    }

    /** @return array{admin: User, teacherUser: User, school: School, year: AcademicYear, class: SchoolClass, otherClass: SchoolClass} */
    private function schoolSetup(): array
    {
        Role::findOrCreate('school_admin');
        Role::findOrCreate('teacher');

        $admin = User::factory()->create();
        $admin->assignRole('school_admin');
        $teacherUser = User::factory()->create();
        $teacherUser->assignRole('teacher');

        $school = School::create([
            'name' => 'BrightStar Academy',
            'code' => 'BSA-EMAIL',
            'email' => 'office@brightstar.test',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-07-31',
            'is_active' => true,
        ]);
        $class = SchoolClass::create([
            'academic_year_id' => $year->id,
            'name' => 'Basic 4 East',
            'code' => 'B4-E',
            'status' => 'active',
        ]);
        $otherClass = SchoolClass::create([
            'academic_year_id' => $year->id,
            'name' => 'Basic 4 West',
            'code' => 'B4-W',
            'status' => 'active',
        ]);

        return compact('admin', 'teacherUser', 'school', 'year', 'class', 'otherClass');
    }

    private function createTeacher(School $school, string $employeeId, ?string $email, ?User $user = null): Teacher
    {
        return Teacher::create([
            'school_id' => $school->id,
            'user_id' => $user?->id,
            'employee_id' => $employeeId,
            'first_name' => 'Staff',
            'last_name' => $employeeId,
            'email' => $email,
            'status' => 'active',
        ]);
    }

    private function createStudent(School $school, string $identifier, ?User $user = null): Student
    {
        return Student::create([
            'school_id' => $school->id,
            'user_id' => $user?->id,
            'student_id' => 'STU-'.$identifier,
            'admission_number' => 'ADM-'.$identifier,
            'first_name' => 'Student',
            'last_name' => $identifier,
            'date_of_birth' => '2015-01-01',
            'gender' => 'female',
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ]);
    }

    private function enroll(Student $student, SchoolClass $schoolClass): void
    {
        ClassEnrollment::create([
            'school_class_id' => $schoolClass->id,
            'student_id' => $student->id,
            'enrolled_at' => '2026-09-01',
            'status' => ClassEnrollment::STATUS_ACTIVE,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function createCampaign(School $school, User $creator, array $overrides = []): EmailCampaign
    {
        return EmailCampaign::create(array_merge([
            'school_id' => $school->id,
            'created_by' => $creator->id,
            'mode' => 'bulk',
            'audiences' => ['parents'],
            'subject' => 'School notice',
            'body' => '<p>School notice body.</p>',
            'status' => EmailCampaign::STATUS_QUEUED,
            'recipient_count' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
            'skipped_count' => 0,
            'queued_at' => now(),
        ], $overrides));
    }
}
