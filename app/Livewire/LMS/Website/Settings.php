<?php

namespace App\Livewire\LMS\Website;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.lms')]
class Settings extends Component
{
    use WithFileUploads;

    public string $siteName = '';
    public string $tagline = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $latitude = '';
    public string $longitude = '';
    public string $primaryColor = '#1e3a8a';
    public string $secondaryColor = '#0f172a';
    public string $accentColor = '#f59e0b';
    public string $facebook = '';
    public string $instagram = '';
    public string $youtube = '';
    public string $x = '';
    public string $whatsapp = '';
    public string $currentLogoPath = '';
    public bool $removeLogo = false;
    public $logo;

    public function mount(): void
    {
        $this->authorizeWebsite();
        $settings = WebsiteSetting::query()->first()
            ?? WebsiteSetting::query()->create(['site_name' => config('app.name', 'School')]);
        $socials = is_array($settings->social_links) ? $settings->social_links : [];

        $this->siteName = (string) ($settings->site_name ?? config('app.name', 'School'));
        $this->tagline = (string) ($settings->tagline ?? '');
        $this->email = (string) ($settings->email ?? '');
        $this->phone = (string) ($settings->phone ?? '');
        $this->address = (string) ($settings->address ?? '');
        $this->latitude = (string) ($settings->map_latitude ?? '');
        $this->longitude = (string) ($settings->map_longitude ?? '');
        $this->primaryColor = (string) ($settings->primary_color ?: '#1e3a8a');
        $this->secondaryColor = (string) ($settings->secondary_color ?: '#0f172a');
        $this->accentColor = (string) ($settings->accent_color ?: '#f59e0b');
        $this->facebook = (string) ($socials['facebook'] ?? '');
        $this->instagram = (string) ($socials['instagram'] ?? '');
        $this->youtube = (string) ($socials['youtube'] ?? '');
        $this->x = (string) ($socials['x'] ?? $socials['twitter'] ?? '');
        $this->whatsapp = (string) ($socials['whatsapp'] ?? '');
        $this->currentLogoPath = (string) ($settings->logo_path ?? '');
    }

    public function save(): void
    {
        $this->authorizeWebsite();
        $newLogoPath = null;

        try {
            $data = $this->validate([
                'siteName' => ['required', 'string', 'max:255'],
                'tagline' => ['nullable', 'string', 'max:500'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'address' => ['nullable', 'string', 'max:1000'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'primaryColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'secondaryColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'accentColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'facebook' => ['nullable', 'url:http,https', 'max:500'],
                'instagram' => ['nullable', 'url:http,https', 'max:500'],
                'youtube' => ['nullable', 'url:http,https', 'max:500'],
                'x' => ['nullable', 'url:http,https', 'max:500'],
                'whatsapp' => ['nullable', 'url:http,https', 'max:500'],
                'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'removeLogo' => ['boolean'],
            ]);

            $settings = WebsiteSetting::query()->firstOrFail();
            $oldLogoPath = $settings->logo_path;
            $logoPath = $oldLogoPath;

            if ($this->logo) {
                $newLogoPath = $this->logo->store('website/branding', 'public');
                $logoPath = $newLogoPath;
            } elseif ($data['removeLogo']) {
                $logoPath = null;
            }

            $settings->update([
                'site_name' => $data['siteName'],
                'tagline' => $data['tagline'] ?: null,
                'email' => $data['email'] ?: null,
                'phone' => $data['phone'] ?: null,
                'address' => $data['address'] ?: null,
                'map_latitude' => $data['latitude'] !== '' ? $data['latitude'] : null,
                'map_longitude' => $data['longitude'] !== '' ? $data['longitude'] : null,
                'primary_color' => strtolower($data['primaryColor']),
                'secondary_color' => strtolower($data['secondaryColor']),
                'accent_color' => strtolower($data['accentColor']),
                'social_links' => array_filter([
                    'facebook' => $data['facebook'] ?: null,
                    'instagram' => $data['instagram'] ?: null,
                    'youtube' => $data['youtube'] ?: null,
                    'x' => $data['x'] ?: null,
                    'whatsapp' => $data['whatsapp'] ?: null,
                ]),
                'logo_path' => $logoPath,
            ]);

            if ($oldLogoPath && $oldLogoPath !== $logoPath) {
                Storage::disk('public')->delete($oldLogoPath);
            }

            $this->logo = null;
            $this->removeLogo = false;
            $this->currentLogoPath = (string) ($logoPath ?? '');
            LivewireAlert::title('Website settings saved')->success()->asToast()->show();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            if ($newLogoPath) {
                Storage::disk('public')->delete($newLogoPath);
            }

            report($exception);
            LivewireAlert::title('Unable to save website settings')->error()->asToast()->show();
        }
    }

    public function render()
    {
        return view('livewire.lms.website.settings');
    }

    private function authorizeWebsite(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('manage website content'), 403);
    }
}
