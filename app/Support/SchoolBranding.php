<?php

namespace App\Support;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SchoolBranding
{
    private const CACHE_VERSION_KEY = 'public-website:branding-version';

    private ?array $publicData = null;

    private ?array $lmsData = null;

    private bool $publicSchoolResolved = false;

    private ?School $resolvedPublicSchool = null;

    private bool $schoolResolved = false;

    private ?School $resolvedSchool = null;

    public function data(): array
    {
        $school = $this->resolvePublicSchool();

        return $this->publicData ??= Cache::remember(
            $this->cacheKey('public', $school?->id),
            now()->addMinutes(15),
            fn (): array => $this->build($school, true),
        );
    }

    public function forLms(): array
    {
        $school = $this->resolveSchool();

        return $this->lmsData ??= Cache::remember(
            $this->cacheKey('lms', $school?->id),
            now()->addMinutes(15),
            fn (): array => $this->build($school, false),
        );
    }

    public static function flushCache(): void
    {
        Cache::forever(self::CACHE_VERSION_KEY, (string) Str::uuid());

        if (app()->resolved(self::class)) {
            app(self::class)->forgetMemoizedData();
        }
    }

    public function forgetMemoizedData(): void
    {
        $this->publicData = null;
        $this->lmsData = null;
        $this->publicSchoolResolved = false;
        $this->resolvedPublicSchool = null;
        $this->schoolResolved = false;
        $this->resolvedSchool = null;
    }

    private function build(?School $school, bool $preferWebsite): array
    {
        $website = WebsiteSetting::query()->first();
        $settings = $school ? SchoolSetting::query()->where('school_id', $school->id)->pluck('value', 'key') : collect();
        $value = fn (string $key, mixed $default = null) => $this->value($settings->get($key), $default);
        $name = $preferWebsite
            ? ($website?->site_name ?: ($school?->name ?: 'BrightStar Academy'))
            : ($school?->name ?: ($website?->site_name ?: 'BrightStar Academy'));
        $motto = $preferWebsite
            ? ($website?->tagline ?: ($school?->motto ?: 'Nurturing excellence'))
            : ($school?->motto ?: ($website?->tagline ?: 'Nurturing excellence'));
        $logoPath = $preferWebsite
            ? ($website?->logo_path ?: $school?->logo_path)
            : ($school?->logo_path ?: $website?->logo_path);

        return [
            'name' => $name,
            'motto' => $motto,
            'email' => $website?->email ?: ($school?->email ?: 'hello@brightstar.academy'),
            'phone' => $website?->phone ?: ($school?->phone ?: '+234 800 000 0000'),
            'address' => $website?->address ?: ($school?->address ?: '12 School Avenue'),
            'map_latitude' => $website?->map_latitude,
            'map_longitude' => $website?->map_longitude,
            'logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
            'initials' => $this->initials($name),
            'hero_title' => $value('hero_title', 'Where curious minds grow into confident leaders.'),
            'hero_subtitle' => $value('hero_subtitle', 'Strong academics, creative learning, and a caring community help every child thrive.'),
            'footer_text' => $value('footer_text', 'A caring learning community dedicated to academic excellence, creativity, and character.'),
            'colors' => [
                'primary' => $website?->primary_color ?: $value('brand_primary', '#1e3a8a'),
                'secondary' => $website?->secondary_color ?: $value('brand_secondary', '#0f172a'),
                'accent' => $website?->accent_color ?: $value('brand_accent', '#f59e0b'),
            ],
            'socials' => [
                'facebook' => $website?->social_links['facebook'] ?? $value('social_facebook'),
                'instagram' => $website?->social_links['instagram'] ?? $value('social_instagram'),
                'youtube' => $website?->social_links['youtube'] ?? $value('social_youtube'),
                'x' => $website?->social_links['x'] ?? $value('social_x'),
                'whatsapp' => $website?->social_links['whatsapp'] ?? $value('social_whatsapp'),
            ],
        ];
    }

    private function resolveSchool(): ?School
    {
        if ($this->schoolResolved) {
            return $this->resolvedSchool;
        }

        $user = auth()->user();
        $school = $user?->teacher?->school
            ?? $user?->student?->school
            ?? $user?->parentGuardian?->school;

        $this->schoolResolved = true;

        return $this->resolvedSchool = $school ?? School::query()->oldest('id')->first();
    }

    private function resolvePublicSchool(): ?School
    {
        if (! $this->publicSchoolResolved) {
            $this->resolvedPublicSchool = School::query()->oldest('id')->first();
            $this->publicSchoolResolved = true;
        }

        return $this->resolvedPublicSchool;
    }

    private function value(mixed $value, mixed $default = null): mixed
    {
        if (is_array($value)) {
            return $value['value'] ?? $default;
        }

        return $value ?? $default;
    }

    private function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return strtoupper(collect($words)->take(2)->map(fn (string $word) => mb_substr($word, 0, 1))->implode('')) ?: 'BS';
    }

    private function cacheKey(string $context, ?int $schoolId): string
    {
        $version = Cache::get(self::CACHE_VERSION_KEY, '1');

        return "public-website:branding:{$version}:{$context}:".($schoolId ?? 0);
    }
}
