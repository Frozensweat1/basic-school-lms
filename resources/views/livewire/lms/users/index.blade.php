<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Administration</p>
            <h2 class="mt-2 text-2xl font-bold">Users</h2>
            <p class="mt-1 text-sm text-slate-600">Manage LMS accounts and roles.</p>
        </div>

        <x-button wire:click="create" target="create" :loading="true" icon="plus">Add user</x-button>
    </div>

    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-xl">
            <label for="user-search" class="sr-only">Search users</label>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input
                id="user-search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name, email, or role"
                autocomplete="off"
                class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700"
            >

            <span wire:loading wire:target="search" class="absolute right-20 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">
                Searching…
            </span>

            @if (filled($search))
                <x-button
                    type="button"
                    wire:click="clearSearch"
                    variant="ghost"
                    size="sm"
                    class="absolute right-1.5 top-1/2 -translate-y-1/2 !px-2.5 !py-1.5"
                    target="clearSearch"
                    :loading="true"
                >
                    Clear
                </x-button>
            @endif
        </div>

        <p class="shrink-0 text-sm text-slate-500" aria-live="polite">
            <span wire:loading.remove wire:target="search">{{ $users->total() }} {{ \Illuminate\Support\Str::plural('user', $users->total()) }}</span>
            <span wire:loading wire:target="search">Updating results…</span>
        </p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left">Name</th>
                    <th class="px-5 py-3 text-left">Email</th>
                    <th class="px-5 py-3 text-left">Role</th>
                    <th class="px-5 py-3 text-left">Profile</th>
                    <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-5 py-4 font-medium">{{ $user->name }}</td>
                        <td class="px-5 py-4">{{ $user->email }}</td>
                        <td class="px-5 py-4">{{ $user->roles->pluck('name')->join(', ') ?: 'No role' }}</td>
                        <td class="px-5 py-4">
                            @php
                                $personaRole = $user->roles->pluck('name')->first(fn ($name) => in_array($name, ['teacher', 'student', 'parent'], true));
                                $hasProfile = match ($personaRole) {
                                    'teacher' => (bool) $user->teacher,
                                    'student' => (bool) $user->student,
                                    'parent' => (bool) $user->parentGuardian,
                                    default => null,
                                };
                            @endphp
                            @if ($hasProfile === true)
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Linked</span>
                            @elseif ($hasProfile === false)
                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Needs profile</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.icon-button wire:click="edit({{ $user->id }})" icon="edit" label="Edit user" target="edit({{ $user->id }})" />
                                @if ($user->id !== auth()->id())
                                    <x-ui.icon-button wire:click="confirmDelete({{ $user->id }})" icon="trash" variant="danger" label="Delete user" target="confirmDelete({{ $user->id }})" />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                            @if (filled($search))
                                No users match “{{ $search }}”.
                            @else
                                No users found.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-pagination :paginator="$users" />

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit user and profile' : 'Add user'" close-action="closeModals" max-width="3xl">
        <form wire:submit="save" class="space-y-6">
            <section class="space-y-4" aria-labelledby="account-details-heading">
                <div>
                    <h3 id="account-details-heading" class="font-semibold text-slate-900">Account details</h3>
                    <p class="mt-1 text-xs text-slate-500">Teacher, student, and parent roles automatically create their matching school profile.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="user-role" class="block text-sm font-medium text-slate-700">Role</label>
                        <select id="user-role" wire:model.live="role" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            <option value="">Choose role</option>
                            @foreach ($roles as $availableRole)
                                <option value="{{ $availableRole->name }}">{{ str($availableRole->name)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                        @error('role')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="user-email" class="block text-sm font-medium text-slate-700">Login email</label>
                        <input id="user-email" wire:model.blur="email" type="email" autocomplete="email" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                        @error('email')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </div>

                @if (! in_array($role, ['teacher', 'student', 'parent'], true))
                    <div>
                        <label for="user-name" class="block text-sm font-medium text-slate-700">Display name</label>
                        <input id="user-name" wire:model.blur="name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                        @error('name')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div>
                    <label for="user-password" class="block text-sm font-medium text-slate-700">{{ $editingId ? 'New password (optional)' : 'Initial password' }}</label>
                    <input id="user-password" wire:model.blur="password" type="password" autocomplete="new-password" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    <p class="mt-1 text-xs text-slate-500">{{ $editingId ? 'Leave blank to keep the current password.' : 'Use at least 10 characters.' }}</p>
                    @error('password')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            </section>

            @if (in_array($role, ['teacher', 'student', 'parent'], true))
                <section wire:key="profile-fields-{{ $role }}" class="space-y-5 border-t border-slate-200 pt-5" aria-labelledby="person-profile-heading">
                    <div>
                        <h3 id="person-profile-heading" class="font-semibold text-slate-900">{{ str($role)->title() }} profile</h3>
                        <p class="mt-1 text-xs text-slate-500">These details will also appear in the {{ $role }} module.</p>
                    </div>

                    @if ($role === 'teacher')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="profile-employee-id" class="block text-sm font-medium">Employee ID</label>
                                <input id="profile-employee-id" wire:model.blur="employeeId" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                                @error('employeeId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="profile-employment-date" class="block text-sm font-medium">Employment date</label>
                                <input id="profile-employment-date" wire:model.blur="employmentDate" type="date" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                                @error('employmentDate')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @elseif ($role === 'student')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="profile-student-id" class="block text-sm font-medium">Student ID</label>
                                <input id="profile-student-id" wire:model.blur="studentId" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                                @error('studentId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="profile-admission-number" class="block text-sm font-medium">Admission number</label>
                                <input id="profile-admission-number" wire:model.blur="admissionNumber" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                                @error('admissionNumber')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="profile-first-name" class="block text-sm font-medium">First name</label>
                            <input id="profile-first-name" wire:model.blur="firstName" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            @error('firstName')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="profile-middle-name" class="block text-sm font-medium">Middle name</label>
                            <input id="profile-middle-name" wire:model.blur="middleName" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            @error('middleName')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="profile-last-name" class="block text-sm font-medium">Last name</label>
                            <input id="profile-last-name" wire:model.blur="lastName" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            @error('lastName')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    @if ($role === 'student')
                        <x-lms.student-admission-fields :classes="$classes" prefix="user-student-form" :has-allergies="$hasAllergies" />
                    @else
                        <div>
                            <label for="profile-phone" class="block text-sm font-medium">Phone</label>
                            <input id="profile-phone" wire:model.blur="phone" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                            @error('phone')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    @if ($role === 'teacher')
                        <div>
                            <label for="profile-status" class="block text-sm font-medium">Status</label>
                            <select id="profile-status" wire:model.blur="profileStatus" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="retired">Retired</option>
                            </select>
                            @error('profileStatus')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    @elseif ($role === 'parent')
                        <div>
                            <label for="profile-address" class="block text-sm font-medium">Address</label>
                            <textarea id="profile-address" wire:model.blur="address" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm"></textarea>
                            @error('address')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="profile-relationship" class="block text-sm font-medium">Relationship</label>
                                <input id="profile-relationship" wire:model.blur="relationship" placeholder="Mother, Father, Guardian" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                                @error('relationship')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <span class="block text-sm font-medium">Link students (optional)</span>
                                <div class="mt-1 max-h-32 space-y-2 overflow-y-auto rounded-lg border border-slate-300 bg-white p-3">
                                    @forelse ($students as $student)
                                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                                            <input wire:model="studentIds" type="checkbox" value="{{ $student->id }}" class="rounded border-slate-300 text-blue-700">
                                            <span>{{ $student->first_name }} {{ $student->last_name }} <span class="text-slate-500">({{ $student->admission_number }})</span></span>
                                        </label>
                                    @empty
                                        <p class="text-xs text-slate-500">No active students are available.</p>
                                    @endforelse
                                </div>
                                @error('studentIds.*')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @endif
                </section>
            @endif

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                <x-button type="button" wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                <x-button type="submit" icon="save" target="save" :loading="true">Save user</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Delete user and archive profile?" close-action="closeModals" max-width="md">
        <p class="text-sm text-slate-600">The login account will be removed and its linked teacher, student, or parent profile will be archived. Historical school records are preserved.</p>
        @error('delete')<p class="mt-3 text-sm text-rose-700">{{ $message }}</p>@enderror
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button type="button" wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                <x-button type="button" wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete user</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
