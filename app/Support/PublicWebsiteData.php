<?php

namespace App\Support;

use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\WebsiteEvent;
use App\Models\WebsiteNewsPost;
use App\Models\WebsitePage;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicWebsiteData
{
    private const CACHE_VERSION_KEY = 'public-website:content-version';

    /**
     * These values keep every public route presentable before a CMS editor has
     * created its page record. Persisted content always takes precedence.
     *
     * @var array<string, array{hero_title: string, hero_subtitle: string}>
     */
    private const PAGE_DEFAULTS = [
        'home' => [
            'hero_title' => 'Where curious minds grow into confident leaders.',
            'hero_subtitle' => 'Strong academics, creative learning, and a caring community help every child thrive.',
        ],
        'about' => [
            'hero_title' => 'A school built around the whole child.',
            'hero_subtitle' => 'Discover the values, people, and purpose that shape our learning community.',
        ],
        'academics' => [
            'hero_title' => 'Learning that connects ideas to life.',
            'hero_subtitle' => 'A thoughtful curriculum helps learners build strong foundations, practical skills, and confidence.',
        ],
        'admissions' => [
            'hero_title' => 'A welcoming start to a bright school journey.',
            'hero_subtitle' => 'Our admissions team will guide your family through every step of joining our school community.',
        ],
        'teachers' => [
            'hero_title' => 'Experienced teachers, inspired learners.',
            'hero_subtitle' => 'Meet the caring educators who bring expertise, creativity, and encouragement to every classroom.',
        ],
        'news' => [
            'hero_title' => 'Stories from our school community.',
            'hero_subtitle' => 'Keep up with learner achievements, school updates, and the moments that bring us together.',
        ],
        'events' => [
            'hero_title' => 'Upcoming events and shared moments.',
            'hero_subtitle' => 'Plan ahead for activities, celebrations, and opportunities to connect with our school community.',
        ],
        'gallery' => [
            'hero_title' => 'Learning, creativity, and community in pictures.',
            'hero_subtitle' => 'Explore memorable moments from everyday school life and special occasions.',
        ],
        'contact' => [
            'hero_title' => 'Let us help with your next step.',
            'hero_subtitle' => 'Speak with our school team about admissions, visits, learning, or any question you may have.',
        ],
    ];

    private ?array $brandingData = null;

    private bool $schoolResolved = false;

    private ?School $resolvedSchool = null;

    /** @var array<string, WebsitePage> */
    private array $pages = [];

    public function __construct(private readonly SchoolBranding $branding)
    {
    }

    public function branding(): array
    {
        return $this->brandingData ??= $this->branding->data();
    }

    public function school(): ?School
    {
        if (! $this->schoolResolved) {
            $this->resolvedSchool = $this->remember('primary-school', fn (): ?School => School::query()
                ->oldest('id')
                ->first(['id', 'name', 'created_at']));
            $this->schoolResolved = true;
        }

        return $this->resolvedSchool;
    }

    public function schoolId(): ?int
    {
        return $this->school()?->id;
    }

    public function page(string $slug): WebsitePage
    {
        if (isset($this->pages[$slug])) {
            return $this->pages[$slug];
        }

        $defaults = self::PAGE_DEFAULTS[$slug] ?? [
            'hero_title' => Str::headline($slug),
            'hero_subtitle' => $this->branding()['motto'],
        ];

        if ($slug === 'home') {
            $defaults['hero_title'] = $this->branding()['hero_title'] ?: $defaults['hero_title'];
            $defaults['hero_subtitle'] = $this->branding()['hero_subtitle'] ?: $defaults['hero_subtitle'];
        }

        return $this->pages[$slug] = $this->remember(
            'page:'.$slug,
            fn (): WebsitePage => WebsitePage::query()
                ->forSlug($slug)
                ->first() ?? new WebsitePage(array_merge(['slug' => $slug], $defaults)),
        );
    }

    /**
     * Browser and social metadata shared by every public page layout.
     *
     * @return array<string, mixed>
     */
    public function metadata(
        string $section,
        WebsitePage $page,
        string $canonicalUrl,
        ?string $description = null,
        ?string $socialImagePath = null,
    ): array {
        $branding = $this->branding();
        $pageTitle = $section === 'Home'
            ? $branding['name']
            : $section;
        $pageDescription = Str::limit(trim(strip_tags(
            $description ?: $page->hero_subtitle ?: $branding['motto']
        )), 160, '');

        $socialImage = $this->publicImageUrl($socialImagePath ?: $page->hero_image_path);

        return [
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'canonicalUrl' => $canonicalUrl,
            'canonicalPath' => parse_url($canonicalUrl, PHP_URL_PATH) ?: '/',
            'socialImage' => $socialImage,
            // Layout prop aliases retain compatibility with Blade component
            // layouts while keeping descriptive keys available to callers.
            'title' => $pageTitle,
            'description' => $pageDescription,
            'canonical' => $canonicalUrl,
            'image' => $socialImage,
        ];
    }

    /** @return array<string, string> */
    public function homeStats(): array
    {
        $configuredStats = collect($this->page('home')->stats ?? [])
            ->mapWithKeys(function (mixed $stat, mixed $key): array {
                if (is_array($stat) && filled($stat['label'] ?? null) && is_scalar($stat['value'] ?? null)) {
                    return [trim((string) $stat['label']) => trim((string) $stat['value'])];
                }

                if (is_string($key) && filled($key) && is_scalar($stat)) {
                    return [trim($key) => trim((string) $stat)];
                }

                return [];
            })
            ->filter(fn (string $value, string $label): bool => $label !== '' && $value !== '')
            ->take(4)
            ->all();

        if ($configuredStats !== []) {
            return $configuredStats;
        }

        $schoolId = $this->schoolId();

        if (! $schoolId) {
            return [
                'Active learners' => '0',
                'Dedicated teachers' => '0',
                'Subjects offered' => '0',
            ];
        }

        return $this->remember('home-stats:'.$schoolId, fn (): array => [
            'Active learners' => number_format(Student::query()
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->count()),
            'Dedicated teachers' => number_format(Teacher::query()
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->count()),
            'Subjects offered' => number_format($this->school()?->subjects()
                ->where('is_active', true)
                ->count() ?? 0),
        ]);
    }

    public function latestNews(int $limit = 3): Collection
    {
        return $this->remember('latest-news:'.$limit, fn (): Collection => WebsiteNewsPost::query()
            ->published()
            ->latest('published_at')
            ->limit($limit)
            ->get());
    }

    public function upcomingEvents(int $limit = 12): Collection
    {
        return $this->remember('upcoming-events:'.$limit, fn (): Collection => WebsiteEvent::query()
            ->published()
            ->upcoming()
            ->orderBy('starts_at')
            ->limit($limit)
            ->get());
    }

    public function pastEvents(int $limit = 6): Collection
    {
        return $this->remember('past-events:'.$limit, fn (): Collection => WebsiteEvent::query()
            ->published()
            ->past()
            ->latest('starts_at')
            ->limit($limit)
            ->get());
    }

    public function remember(string $fragment, Closure $callback): mixed
    {
        return Cache::remember(
            $this->cacheKey($fragment),
            now()->addMinutes(15),
            $callback,
        );
    }

    public static function flushCache(): void
    {
        Cache::forever(self::CACHE_VERSION_KEY, (string) Str::uuid());
        SchoolBranding::flushCache();

        if (app()->resolved(self::class)) {
            app(self::class)->forgetMemoizedData();
        }
    }

    public function forgetMemoizedData(): void
    {
        $this->brandingData = null;
        $this->schoolResolved = false;
        $this->resolvedSchool = null;
        $this->pages = [];
    }

    public function publicImageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    private function cacheKey(string $fragment): string
    {
        $version = Cache::get(self::CACHE_VERSION_KEY, '1');

        return "public-website:content:{$version}:".sha1($fragment);
    }
}
