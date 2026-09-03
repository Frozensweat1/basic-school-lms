<?php

namespace App\Livewire\LMS\Settings;

use App\Models\School;
use App\Jobs\QueueWorkerHealthCheckJob;
use App\Models\SchoolSetting;
use App\Support\DatabaseMaintenance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $code = '';

    public string $motto = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $timezone = 'Africa/Accra';

    public string $weekStartsOn = 'monday';

    public bool $notificationsEnabled = true;

    public bool $lateSubmissionsEnabled = true;

    public $logo;

    public bool $removeLogo = false;

    public string $logoPath = '';

    public string $brandPrimary = '#1e3a8a';

    public string $brandSecondary = '#0f172a';

    public string $brandAccent = '#f59e0b';

    public string $heroTitle = '';

    public string $heroSubtitle = '';

    public string $footerText = '';

    public string $socialFacebook = '';

    public string $socialInstagram = '';

    public string $socialYoutube = '';

    public string $socialX = '';

    public string $socialWhatsapp = '';

    public bool $isInitialSetup = false;

    public string $queueHealthToken = '';

    public string $queueHealthStatus = '';

    public int $queueHealthStartedAt = 0;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'school_admin']), 403);
        $school = School::query()->oldest('id')->first();
        $this->isInitialSetup = $school === null;

        // Eloquent returns NULL for optional profile fields; Livewire cannot hydrate
        // a typed string property with NULL, so normalize these values at the boundary.
        $this->name = (string) ($school?->name ?? '');
        $this->code = (string) ($school?->code ?? '');
        $this->motto = (string) ($school?->motto ?? '');
        $this->email = (string) ($school?->email ?? '');
        $this->phone = (string) ($school?->phone ?? '');
        $this->address = (string) ($school?->address ?? '');
        $this->logoPath = (string) ($school?->logo_path ?? '');
        $settings = $school
            ? SchoolSetting::where('school_id', $school->id)->pluck('value', 'key')
            : collect();
        $this->timezone = (string) $this->settingValue($settings, 'timezone', $this->timezone);
        $this->weekStartsOn = (string) $this->settingValue($settings, 'week_starts_on', $this->weekStartsOn);
        $this->notificationsEnabled = (bool) $this->settingValue($settings, 'notifications_enabled', true);
        $this->lateSubmissionsEnabled = (bool) $this->settingValue($settings, 'late_submissions_enabled', true);
        $this->brandPrimary = (string) $this->settingValue($settings, 'brand_primary', $this->brandPrimary);
        $this->brandSecondary = (string) $this->settingValue($settings, 'brand_secondary', $this->brandSecondary);
        $this->brandAccent = (string) $this->settingValue($settings, 'brand_accent', $this->brandAccent);
        $this->heroTitle = (string) $this->settingValue($settings, 'hero_title', 'Where curious minds grow into confident leaders.');
        $this->heroSubtitle = (string) $this->settingValue($settings, 'hero_subtitle', 'Strong academics, creative learning, and a caring community help every child thrive.');
        $this->footerText = (string) $this->settingValue($settings, 'footer_text', 'A caring learning community dedicated to academic excellence, creativity, and character.');
        $this->socialFacebook = (string) $this->settingValue($settings, 'social_facebook', '');
        $this->socialInstagram = (string) $this->settingValue($settings, 'social_instagram', '');
        $this->socialYoutube = (string) $this->settingValue($settings, 'social_youtube', '');
        $this->socialX = (string) $this->settingValue($settings, 'social_x', '');
        $this->socialWhatsapp = (string) $this->settingValue($settings, 'social_whatsapp', '');
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'school_admin']), 403);

        try {
            $school = School::query()->oldest('id')->first();
            $data = $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:50', Rule::unique('schools', 'code')->ignore($school?->id)],
                'motto' => ['nullable', 'string', 'max:500'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'address' => ['nullable', 'string', 'max:1000'],
                'timezone' => ['required', 'timezone'],
                'weekStartsOn' => ['required', 'in:monday,sunday'],
                'notificationsEnabled' => ['boolean'],
                'lateSubmissionsEnabled' => ['boolean'],
                'logo' => ['nullable', 'image', 'max:2048'],
                'brandPrimary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'brandSecondary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'brandAccent' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'heroTitle' => ['required', 'string', 'max:180'],
                'heroSubtitle' => ['required', 'string', 'max:500'],
                'footerText' => ['required', 'string', 'max:500'],
                'socialFacebook' => ['nullable', 'url', 'max:500'],
                'socialInstagram' => ['nullable', 'url', 'max:500'],
                'socialYoutube' => ['nullable', 'url', 'max:500'],
                'socialX' => ['nullable', 'url', 'max:500'],
                'socialWhatsapp' => ['nullable', 'string', 'max:50'],
            ]);
            $school ??= new School;
            $oldLogoPath = $school->logo_path;
            $newLogoPath = $this->removeLogo ? null : $oldLogoPath;
            if ($this->logo) {
                $newLogoPath = $this->logo->store('branding', 'public');
            }

            DB::transaction(function () use ($school, $data, $newLogoPath): void {
                $school->fill(collect($data)->only(['name', 'code', 'motto', 'email', 'phone', 'address'])->merge(['logo_path' => $newLogoPath])->all());
                $school->save();
                foreach ([
                    'timezone' => $data['timezone'],
                    'week_starts_on' => $data['weekStartsOn'],
                    'notifications_enabled' => (bool) $data['notificationsEnabled'],
                    'late_submissions_enabled' => (bool) $data['lateSubmissionsEnabled'],
                    'brand_primary' => $data['brandPrimary'],
                    'brand_secondary' => $data['brandSecondary'],
                    'brand_accent' => $data['brandAccent'],
                    'hero_title' => $data['heroTitle'],
                    'hero_subtitle' => $data['heroSubtitle'],
                    'footer_text' => $data['footerText'],
                    'social_facebook' => $data['socialFacebook'],
                    'social_instagram' => $data['socialInstagram'],
                    'social_youtube' => $data['socialYoutube'],
                    'social_x' => $data['socialX'],
                    'social_whatsapp' => $data['socialWhatsapp'],
                ] as $key => $value) {
                    SchoolSetting::updateOrCreate(['school_id' => $school->id, 'key' => $key], ['value' => ['value' => $value]]);
                }
            });
            if ($newLogoPath !== $oldLogoPath && $oldLogoPath) {
                Storage::disk('public')->delete($oldLogoPath);
            }
            $this->logo = null;
            $this->removeLogo = false;
            $this->logoPath = (string) $newLogoPath;
            $this->isInitialSetup = false;

            LivewireAlert::title('Settings saved')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the settings form')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save settings')->error()->asToast()->position('top-end')->show();
        }
    }

    public function render()
    {
        return view('livewire.lms.settings.index');
    }

    public function clearLogo(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'school_admin']), 403);
        $this->logo = null;
        $this->logoPath = '';
        $this->removeLogo = true;
    }

    public function testQueueWorker(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'school_admin']), 403);

        $this->queueHealthToken = (string) str()->uuid();
        $this->queueHealthStatus = 'pending';
        $this->queueHealthStartedAt = now()->timestamp;

        cache()->put('queue-health-check:'.$this->queueHealthToken, [
            'status' => 'queued',
        ], now()->addMinutes(10));

        try {
            QueueWorkerHealthCheckJob::dispatch($this->queueHealthToken)
                ->onQueue(config('sms.queue', 'sms'));
        } catch (Throwable $exception) {
            $this->queueHealthStatus = 'failed';
            LivewireAlert::title('Unable to test the queue worker')->error()->asToast()->position('top-end')->show();
            report($exception);
        }
    }

    public function checkQueueWorker(): void
    {
        if ($this->queueHealthToken === '' || $this->queueHealthStatus !== 'pending') {
            return;
        }

        $result = cache()->get('queue-health-check:'.$this->queueHealthToken, []);
        $status = (string) ($result['status'] ?? 'pending');

        if ($status === 'completed') {
            $this->queueHealthStatus = 'completed';
            LivewireAlert::title('Queue worker is running')->success()->asToast()->position('top-end')->show();

            return;
        }

        if ($status === 'failed' || now()->timestamp - $this->queueHealthStartedAt >= 15) {
            $this->queueHealthStatus = 'failed';
            LivewireAlert::title('Queue worker is not running')->error()->asToast()->position('top-end')->show();
        }
    }

    public function backupDatabase()
    {
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'school_admin']), 403);
        try {
            $path = app(DatabaseMaintenance::class)->backup();
            LivewireAlert::title('Backup created')->success()->asToast()->position('top-end')->show();

            return response()->download(Storage::disk('local')->path($path), basename($path), ['Content-Type' => 'application/json'])->deleteFileAfterSend(false);
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to create backup')->error()->asToast()->position('top-end')->show();
        }
    }

    public function resetDatabase(): void
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);
        try {
            $preserved = app(DatabaseMaintenance::class)->resetPreservingSuperAdmins();
            auth()->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            session()->flash('status', "Database reset completed. {$preserved} super administrator account(s) were preserved.");
            $this->redirectRoute('login', navigate: true);
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Database reset failed')->error()->asToast()->position('top-end')->show();
        }
    }

    private function settingValue($settings, string $key, mixed $default): mixed
    {
        $value = $settings->get($key, $default);

        return is_array($value) ? ($value['value'] ?? $default) : $value;
    }
}
