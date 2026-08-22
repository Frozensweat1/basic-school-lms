<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Community</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Parents & guardians</h2>
            <p class="mt-1 text-sm text-slate-600">Link guardians to their children for secure family access.</p>
        </div>

        @can('create', App\Models\ParentGuardian::class)
            <x-button wire:click="create" target="create" :loading="true" icon="plus">Add parent</x-button>
        @endcan
    </div>

    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-xl">
            <label for="parent-search" class="sr-only">Search parents and guardians</label>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input
                id="parent-search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search guardian, contact, or linked student"
                autocomplete="off"
                class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700"
            >

            <span wire:loading wire:target="search" class="absolute right-20 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>

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
            <span wire:loading.remove wire:target="search">{{ $parents->total() }} {{ \Illuminate\Support\Str::plural('guardian', $parents->total()) }}</span>
            <span wire:loading wire:target="search">Updating results…</span>
        </p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Parent/guardian</th>
                        <th class="px-5 py-3 font-semibold">Contact</th>
                        <th class="px-5 py-3 font-semibold">Children</th>
                        <th class="px-5 py-3 text-right font-semibold"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($parents as $parent)
                        <tr wire:key="parent-{{ $parent->id }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4 font-medium text-slate-900">{{ $parent->first_name }} {{ $parent->last_name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $parent->email ?: $parent->phone ?: '—' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $parent->students_count }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    @can('update', $parent)
                                        <x-ui.icon-button wire:click="edit({{ $parent->id }})" icon="edit" label="Edit {{ $parent->first_name }} {{ $parent->last_name }}" target="edit({{ $parent->id }})" />
                                    @endcan
                                    @can('delete', $parent)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $parent->id }})" icon="trash" variant="danger" label="Archive {{ $parent->first_name }} {{ $parent->last_name }}" target="confirmDelete({{ $parent->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center">
                                <p class="font-medium text-slate-700">{{ $search ? 'No parents or guardians match your search.' : 'No parents or guardians have been linked yet.' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $search ? 'Try a guardian name, contact detail, or linked student.' : 'Add a guardian and link them to a student to get started.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$parents" />

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit parent or guardian' : 'Add parent or guardian'" close-action="closeModals" max-width="xl">
        <form wire:submit="save" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="first-name" class="block text-sm font-medium">First name</label>
                    <input wire:model.blur="firstName" id="first-name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    @error('firstName')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="last-name" class="block text-sm font-medium">Last name</label>
                    <input wire:model.blur="lastName" id="last-name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    @error('lastName')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="email" class="block text-sm font-medium">Email</label>
                    <input wire:model.blur="email" id="email" type="email" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    @error('email')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium">Phone</label>
                    <input wire:model.blur="phone" id="phone" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                    @error('phone')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="relationship" class="block text-sm font-medium">Relationship to child</label>
                <input wire:model.blur="relationship" id="relationship" placeholder="Mother, Father, Guardian" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                @error('relationship')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="address" class="block text-sm font-medium">Address</label>
                <textarea wire:model.blur="address" id="address" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm"></textarea>
                @error('address')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <fieldset>
                <legend class="text-sm font-medium">Children</legend>
                <p class="mt-1 text-xs text-slate-500">Choose every student linked to this guardian.</p>
                <div class="mt-3 max-h-44 space-y-2 overflow-y-auto rounded-lg border border-slate-200 p-3">
                    @forelse ($students as $student)
                        <label wire:key="parent-student-{{ $student->id }}" class="flex items-center gap-3 text-sm">
                            <input wire:model="studentIds" type="checkbox" value="{{ $student->id }}" class="rounded border-slate-300 text-blue-700">
                            {{ $student->first_name }} {{ $student->last_name }} <span class="text-slate-400">({{ $student->admission_number }})</span>
                        </label>
                    @empty
                        <p class="text-sm text-slate-500">Add students before linking a guardian.</p>
                    @endforelse
                </div>
                @error('studentIds.*')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
            </fieldset>

            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" type="button" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                <x-button type="submit" icon="save" target="save" :loading="true">Save parent</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Archive parent or guardian?" close-action="closeModals" max-width="md">
        <p class="text-sm text-slate-600">The profile will be archived while linked student history remains intact.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Archive parent</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
