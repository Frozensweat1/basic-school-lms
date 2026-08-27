<?php

namespace Tests\Feature;

use App\Livewire\Website\Contact;
use App\Livewire\Website\Gallery;
use App\Livewire\Website\HomePage;
use App\Livewire\Website\NewsletterSignup;
use App\Livewire\Website\Teachers;
use App\Models\NewsletterSubscription;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\WebsiteGalleryAlbum;
use App\Models\WebsiteGalleryImage;
use App\Models\WebsiteInquiry;
use App\Models\WebsiteEvent;
use App\Models\WebsiteNewsPost;
use App\Models\WebsitePage;
use App\Models\WebsiteSetting;
use App\Support\PublicWebsiteData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class PublicWebsiteDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_home_metrics_are_scoped_to_the_primary_school(): void
    {
        $primary = School::create(['name' => 'Primary School', 'code' => 'PRIMARY']);
        $other = School::create(['name' => 'Other School', 'code' => 'OTHER']);

        $this->createStudent($primary, 'P-001');
        $this->createStudent($primary, 'P-002');
        $this->createStudent($other, 'O-001');
        $this->createTeacher($primary, 'TP-001');
        $this->createTeacher($other, 'TO-001');

        $primary->subjects()->create(['name' => 'Mathematics', 'code' => 'MATH', 'is_active' => true]);
        $primary->subjects()->create(['name' => 'Archived', 'code' => 'ARCH', 'is_active' => false]);
        $other->subjects()->create(['name' => 'Science', 'code' => 'SCI', 'is_active' => true]);

        Livewire::test(HomePage::class)
            ->assertViewHas('stats', fn (array $stats): bool => $stats === [
                'Active learners' => '2',
                'Dedicated teachers' => '1',
                'Subjects offered' => '1',
            ]);
    }

    public function test_missing_cms_pages_receive_safe_public_fallback_content(): void
    {
        School::create(['name' => 'Primary School', 'code' => 'PRIMARY']);

        $page = app(PublicWebsiteData::class)->page('gallery');

        $this->assertFalse($page->exists);
        $this->assertSame('gallery', $page->slug);
        $this->assertNotEmpty($page->hero_title);
        $this->assertNotEmpty($page->hero_subtitle);
    }

    public function test_public_cache_is_invalidated_immediately_after_cms_writes(): void
    {
        School::create(['name' => 'Primary School', 'code' => 'PRIMARY']);
        $site = app(PublicWebsiteData::class);

        $this->assertFalse($site->page('about')->exists);
        $this->assertSame('Primary School', $site->branding()['name']);

        WebsitePage::create([
            'slug' => 'about',
            'hero_title' => 'Our new story',
            'hero_subtitle' => 'Fresh CMS content.',
        ]);
        WebsiteSetting::create([
            'site_name' => 'New Public Brand',
            'primary_color' => '#123b63',
            'secondary_color' => '#0b1f33',
            'accent_color' => '#f2a93b',
        ]);

        $this->assertTrue($site->page('about')->exists);
        $this->assertSame('Our new story', $site->page('about')->hero_title);
        $this->assertSame('New Public Brand', $site->branding()['name']);
    }

    public function test_featured_teacher_directory_is_scoped_and_paginated(): void
    {
        $primary = School::create(['name' => 'Primary School', 'code' => 'PRIMARY']);
        $other = School::create(['name' => 'Other School', 'code' => 'OTHER']);

        foreach (range(1, 13) as $number) {
            $this->createTeacher($primary, sprintf('TP-%03d', $number), true);
        }
        $this->createTeacher($other, 'TO-001', true);

        Livewire::test(Teachers::class)
            ->assertViewHas('teachers', fn ($teachers): bool => $teachers->count() === 12 && $teachers->total() === 13);
    }

    public function test_gallery_albums_and_eager_loaded_images_are_bounded(): void
    {
        foreach (range(1, 7) as $albumNumber) {
            $album = WebsiteGalleryAlbum::create(['title' => 'Album '.$albumNumber]);

            foreach (range(1, 10) as $imageNumber) {
                WebsiteGalleryImage::create([
                    'album_id' => $album->id,
                    'path' => 'gallery/'.$albumNumber.'-'.$imageNumber.'.jpg',
                    'sort_order' => $imageNumber,
                ]);
            }
        }

        Livewire::test(Gallery::class)
            ->assertViewHas('albums', fn ($albums): bool => $albums->count() === 6
                && $albums->total() === 7
                && $albums->every(fn ($album): bool => $album->images->count() <= 8));
    }

    public function test_contact_rate_limit_returns_validation_feedback_instead_of_an_http_error(): void
    {
        RateLimiter::clear('website-contact:'.hash('sha256', '127.0.0.1|family@example.com'));
        $component = Livewire::test(Contact::class);

        foreach (range(1, 5) as $attempt) {
            $component
                ->set('name', 'Family Contact')
                ->set('email', 'family@example.com')
                ->set('message', 'Please send us admissions information for attempt '.$attempt.'.')
                ->call('submit')
                ->assertHasNoErrors();
        }

        $component
            ->set('name', 'Family Contact')
            ->set('email', 'family@example.com')
            ->set('message', 'One more admissions question.')
            ->call('submit')
            ->assertHasErrors('message')
            ->assertSet('retryAfterSeconds', fn (int $seconds): bool => $seconds > 0);

        $this->assertSame(5, WebsiteInquiry::query()->count());
    }

    public function test_newsletter_subscription_and_contact_breadcrumbs_work(): void
    {
        Livewire::test(NewsletterSignup::class)
            ->set('newsletterEmail', 'family@example.com')
            ->call('subscribeNewsletter')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('newsletter_subscriptions', ['email' => 'family@example.com']);

        $this->get(route('website.contact'))
            ->assertOk()
            ->assertSee('Home')
            ->assertSee('Contact');
    }

    public function test_only_published_news_slugs_are_publicly_resolvable(): void
    {
        $author = User::factory()->create();
        $published = WebsiteNewsPost::create([
            'title' => 'Published story',
            'slug' => 'published-story',
            'body' => '<p>Published content.</p>',
            'published_at' => now()->subMinute(),
            'created_by' => $author->id,
        ]);
        WebsiteNewsPost::create([
            'title' => 'Scheduled story',
            'slug' => 'scheduled-story',
            'body' => '<p>Not public yet.</p>',
            'published_at' => now()->addDay(),
            'created_by' => $author->id,
        ]);

        $this->assertSame(1, WebsiteNewsPost::query()->published()->count());
        $this->get(route('website.news.show', $published->slug))
            ->assertOk()
            ->assertSee('Published story');
        $this->get(route('website.news.show', 'scheduled-story'))->assertNotFound();
    }

    public function test_published_event_details_and_sitemap_exclude_drafts(): void
    {
        $author = User::factory()->create();
        $published = WebsiteEvent::create([
            'title' => 'Open day',
            'slug' => 'open-day',
            'description' => 'Visit our learning spaces.',
            'starts_at' => now()->addWeek(),
            'location' => 'Main campus',
            'is_published' => true,
            'created_by' => $author->id,
        ]);
        WebsiteEvent::create([
            'title' => 'Planning meeting',
            'slug' => 'planning-meeting',
            'starts_at' => now()->addWeeks(2),
            'is_published' => false,
            'created_by' => $author->id,
        ]);

        $this->get(route('website.events.show', $published->slug))
            ->assertOk()
            ->assertSee('Open day');
        $this->get(route('website.events.show', 'planning-meeting'))->assertNotFound();

        $this->get(route('website.sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('/events/open-day', false)
            ->assertDontSee('/events/planning-meeting', false);

        $robots = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('Disallow: /lms/', $robots);
        $this->assertStringContainsString('Sitemap: /sitemap.xml', $robots);
    }

    private function createStudent(School $school, string $identifier): Student
    {
        return Student::create([
            'school_id' => $school->id,
            'student_id' => $identifier,
            'admission_number' => 'ADM-'.$identifier,
            'first_name' => 'Learner',
            'last_name' => $identifier,
            'date_of_birth' => '2015-01-01',
            'gender' => 'female',
            'admission_date' => '2024-09-01',
            'status' => 'active',
        ]);
    }

    private function createTeacher(School $school, string $identifier, bool $featured = false): Teacher
    {
        return Teacher::create([
            'school_id' => $school->id,
            'employee_id' => $identifier,
            'first_name' => 'Teacher',
            'last_name' => $identifier,
            'status' => 'active',
            'is_featured_on_website' => $featured,
        ]);
    }
}
