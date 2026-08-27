<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Account</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Profile settings</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Manage your personal details, security, and active sessions.</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Profile information</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Keep your identity and contact details up to date.</p>

            <form wire:submit="saveProfile" class="mt-6 space-y-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="h-20 w-20 overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" alt="New profile photo" class="h-full w-full object-cover">
                        @elseif ($user->profile_photo_url)
                            <img src="{{ $user->profile_photo_url }}" alt="Profile photo" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-lg font-black text-slate-500 dark:text-slate-300">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1 space-y-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Profile photo</label>
                        <input wire:model="photo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-700 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-semibold file:text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:file:bg-slate-700 dark:file:text-slate-100">
                        <p wire:loading wire:target="photo" class="text-xs font-medium text-blue-700">Preparing image preview...</p>
                        @error('photo')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                        @if ($user->profile_photo_path)
                            <button type="button" wire:click="removePhoto" class="text-xs font-semibold text-rose-700 hover:text-rose-800">Remove current photo</button>
                        @endif
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="profile-name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Full name</label>
                        <input id="profile-name" wire:model.blur="name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @error('name')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="profile-email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
                        <input id="profile-email" wire:model.blur="email" type="email" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @error('email')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-100 pt-5 dark:border-slate-800">
                    <x-button type="submit" icon="save" target="saveProfile" :loading="true">Save profile</x-button>
                </div>
            </form>
        </section>

        <section class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Change password</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Use a strong password that you do not reuse elsewhere.</p>

                <form wire:submit="updatePassword" class="mt-5 space-y-4">
                    <div>
                        <label for="current-password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Current password</label>
                        <input id="current-password" wire:model.blur="currentPassword" type="password" autocomplete="current-password" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @error('currentPassword')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="new-password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">New password</label>
                        <input id="new-password" wire:model.blur="password" type="password" autocomplete="new-password" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @error('password')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="new-password-confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Confirm new password</label>
                        <input id="new-password-confirmation" wire:model.blur="passwordConfirmation" type="password" autocomplete="new-password" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>

                    <div class="flex justify-end border-t border-slate-100 pt-4 dark:border-slate-800">
                        <x-button type="submit" icon="save" target="updatePassword" :loading="true">Update password</x-button>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Active sessions</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Review your recent devices and sign out from other sessions.</p>

                <div class="mt-5 space-y-3">
                    @forelse ($sessions as $session)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/60">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $session['device'] }}
                                    @if ($session['is_current'])
                                        <span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Current</span>
                                    @endif
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $session['last_active'] }}</p>
                            </div>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">IP: {{ $session['ip_address'] }}</p>
                            <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400" title="{{ $session['user_agent'] }}">{{ $session['user_agent'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400">Session tracking is unavailable right now.</p>
                    @endforelse
                </div>

                <form wire:submit="logoutOtherSessions" class="mt-5 space-y-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <div>
                        <label for="session-password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Confirm your password</label>
                        <input id="session-password" wire:model.blur="sessionPassword" type="password" autocomplete="current-password" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" placeholder="Required to sign out other sessions">
                        @error('sessionPassword')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end">
                        <x-button type="submit" variant="danger" target="logoutOtherSessions" :loading="true">Sign out other sessions</x-button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
