<?php

namespace App\Livewire\LMS\Profile;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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
    use AuthorizesRequests;
    use WithFileUploads;

    private function currentUser(): User
    {
        $userId = Auth::id();
        abort_unless($userId, 403);

        return User::query()->findOrFail($userId);
    }

    public string $name = '';
    public string $email = '';
    public string $currentPassword = '';
    public string $password = '';
    public string $passwordConfirmation = '';
    public string $sessionPassword = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|UploadedFile|null */
    public $photo = null;

    public function mount(): void
    {
        $user = $this->currentUser();
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
    }

    public function saveProfile(): void
    {
        $user = $this->currentUser();

        try {
            $data = $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            ]);

            $oldPhotoPath = $user->profile_photo_path;
            $newPhotoPath = $oldPhotoPath;

            if ($this->photo) {
                $newPhotoPath = $this->photo->store('profile-photos', 'public');
            }

            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->profile_photo_path = $newPhotoPath;
            $user->save();

            if ($this->photo && $oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
                Storage::disk('public')->delete($oldPhotoPath);
            }

            $this->reset('photo');
            $this->resetValidation(['name', 'email', 'photo']);

            LivewireAlert::title('Profile updated')
                ->success()
                ->asToast()
                ->position('top-end')
                ->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check your profile details')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to update profile')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function removePhoto(): void
    {
        $user = $this->currentUser();

        if (! $user->profile_photo_path) {
            return;
        }

        Storage::disk('public')->delete($user->profile_photo_path);
        $user->profile_photo_path = null;
        $user->save();
        $this->reset('photo');

        LivewireAlert::title('Profile photo removed')
            ->success()
            ->asToast()
            ->position('top-end')
            ->show();
    }

    public function updatePassword(): void
    {
        try {
            $data = $this->validate([
                'currentPassword' => ['required', 'string', 'current_password:web'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ], [], [
                'currentPassword' => 'current password',
            ]);

            $user = $this->currentUser();

            $user->password = Hash::make($data['password']);
            $user->save();

            $this->reset(['currentPassword', 'password', 'passwordConfirmation']);
            $this->resetValidation(['currentPassword', 'password']);

            LivewireAlert::title('Password changed')
                ->success()
                ->asToast()
                ->position('top-end')
                ->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Password update failed')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();

            throw $exception;
        }
    }

    public function logoutOtherSessions(): void
    {
        try {
            $data = $this->validate([
                'sessionPassword' => ['required', 'string', 'current_password:web'],
            ], [], [
                'sessionPassword' => 'password',
            ]);

            Auth::logoutOtherDevices($data['sessionPassword']);

            $sessionTable = (string) config('session.table', 'sessions');
            if (Schema::hasTable($sessionTable)) {
                $userId = Auth::id();
                abort_unless($userId, 403);

                DB::table($sessionTable)
                    ->where('user_id', $userId)
                    ->where('id', '!=', session()->getId())
                    ->delete();
            }

            $this->reset('sessionPassword');
            $this->resetValidation('sessionPassword');

            LivewireAlert::title('Other sessions signed out')
                ->success()
                ->asToast()
                ->position('top-end')
                ->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Could not sign out other sessions')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();

            throw $exception;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function activeSessions(): array
    {
        $sessionTable = (string) config('session.table', 'sessions');

        if (! Schema::hasTable($sessionTable)) {
            return [];
        }

        $userId = Auth::id();
        if (! $userId) {
            return [];
        }

        return DB::table($sessionTable)
            ->where('user_id', $userId)
            ->orderByDesc('last_activity')
            ->limit(8)
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(function ($session): array {
                $agent = (string) ($session->user_agent ?? 'Unknown browser');
                $isMobile = str_contains(strtolower($agent), 'mobile') || str_contains(strtolower($agent), 'android') || str_contains(strtolower($agent), 'iphone');

                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address ?: 'Unknown IP',
                    'user_agent' => $agent,
                    'device' => $isMobile ? 'Mobile device' : 'Desktop browser',
                    'is_current' => $session->id === session()->getId(),
                    'last_active' => now()->setTimestamp((int) $session->last_activity)->diffForHumans(),
                ];
            })
            ->values()
            ->all();
    }

    public function render()
    {
        $user = $this->currentUser();

        return view('livewire.lms.profile.index', [
            'user' => $user,
            'sessions' => $this->activeSessions(),
        ]);
    }
}
