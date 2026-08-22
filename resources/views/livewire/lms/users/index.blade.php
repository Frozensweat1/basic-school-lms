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
                        <td colspan="4" class="px-5 py-12 text-center text-slate-500">
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

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit user' : 'Add user'" close-action="closeModals">
        <form wire:submit="save" class="space-y-5">
            <input wire:model.blur="name" placeholder="Name" class="w-full rounded-lg border-slate-300">
            @error('name')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror

            <input wire:model.blur="email" type="email" placeholder="Email" class="w-full rounded-lg border-slate-300">
            @error('email')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror

            <input wire:model.blur="password" type="password" placeholder="{{ $editingId ? 'New password (optional)' : 'Password' }}" class="w-full rounded-lg border-slate-300">

            <select wire:model.blur="role" class="w-full rounded-lg border-slate-300">
                <option value="">Choose role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
            </select>

            <div class="flex justify-end gap-3">
                <x-button type="button" wire:click="closeModals" variant="ghost">Cancel</x-button>
                <x-button type="submit" icon="save" target="save">Save user</x-button>
            </div>
        </form>
    </x-modal>
</div>
