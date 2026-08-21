<?php

namespace App\Support;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Storage;

class SchoolBranding
{
    public function data(): array
    {
        $school = School::query()->first();
        $website = WebsiteSetting::query()->first();
        $settings = $school ? SchoolSetting::query()->where('school_id', $school->id)->pluck('value', 'key') : collect();
        $value = fn (string $key, mixed $default = null) => $this->value($settings->get($key), $default);

        return [
            'name' => $website?->site_name ?: ($school?->name ?: 'BrightStar Academy'),
            'motto' => $website?->tagline ?: ($school?->motto ?: 'Nurturing excellence'),
            'email' => $website?->email ?: ($school?->email ?: 'hello@brightstar.academy'),
            'phone' => $website?->phone ?: ($school?->phone ?: '+234 800 000 0000'),
            'address' => $website?->address ?: ($school?->address ?: '12 School Avenue'),
            'map_latitude' => $website?->map_latitude,
            'map_longitude' => $website?->map_longitude,
            'logo_url' => ($website?->logo_path ?: $school?->logo_path) ? Storage::disk('public')->url($website?->logo_path ?: $school?->logo_path) : null,
            'initials' => $this->initials($website?->site_name ?: ($school?->name ?: 'BrightStar Academy')),
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

    private function value(mixed $value, mixed $default = null): mixed
    {
        if (is_array($value)) return $value['value'] ?? $default;
        return $value ?? $default;
    }

    private function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return strtoupper(collect($words)->take(2)->map(fn (string $word) => mb_substr($word, 0, 1))->implode('')) ?: 'BS';
    }
}
