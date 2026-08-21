<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Academic structure</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Terms</h2>
            <p class="mt-1 text-sm text-slate-600">Configure the terms that sit within each academic year.</p>
        </div>

        @can('create', App\Models\Term::class)
            <x-button wire:click="create" icon="plus" target="create" :loading="true">New term</x-button>
        @endcan
    </div>

    @if ($showForm)
        <section class="overflow-hidden rounded-2xl border border-blue-200 bg-white shadow-sm" aria-labelledby="term-form-title">
            <div class="flex items-start justify-between gap-4 border-b border-blue-100 bg-blue-50/70 px-5 py-4 sm:px-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Term form</p>
                    <h3 id="term-form-title" class="mt-1 text-lg font-bold text-slate-900">
                        {{ $editingId ? 'Edit term' : 'New term' }}
                    </h3>
                </div>
                <x-button wire:click="closeForm" variant="ghost" size="sm" target="closeForm" :loading="true">Cancel</x-button>
            </div>

            <form wire:submit="save" class="space-y-5 p-5 sm:p-6">
                <div>
                    <label for="academic-year" class="block text-sm font-medium text-slate-700">Academic year</label>
                    <select wire:model.blur="academicYearId" id="academic-year"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="">Select an academic year</option>
                        @foreach ($years as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}{{ $year->is_active ? ' (Active)' : '' }}</option>
                        @endforeach
                    </select>
                    @error('academicYearId')
                        <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label for="term-name" class="block text-sm font-medium text-slate-700">Term name</label>
                        <input wire:model.blur="name" id="term-name" type="text" placeholder="Term 1"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                        @error('name')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="sequence" class="block text-sm font-medium text-slate-700">Order</label>
                        <input wire:model.blur="sequence" id="sequence" type="number" min="1" max="20"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                        @error('sequence')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="starts-at" class="block text-sm font-medium text-slate-700">Start date</label>
                        <input wire:model.blur="startsAt" id="starts-at" type="date"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                        @error('startsAt')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="ends-at" class="block text-sm font-medium text-slate-700">End date</label>
                        <input wire:model.blur="endsAt" id="ends-at" type="date"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                        @error('endsAt')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex items-center gap-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                        <input wire:model="isActive" type="checkbox" class="rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                        Set as the active term
                    </label>
                    <label class="flex items-center gap-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                        <input wire:model="isLocked" type="checkbox" class="rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                        Lock this term
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                    <x-button wire:click="closeForm" type="button" variant="ghost" target="closeForm" :loading="true">Cancel</x-button>
                    <x-button type="submit" icon="save" target="save" :loading="true">Save term</x-button>
                </div>
            </form>
        </section>
    @endif

    @if ($showDeleteConfirmation)
        <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm" aria-labelledby="delete-term-title">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 id="delete-term-title" class="font-semibold text-rose-950">Delete this term?</h3>
                    <p class="mt-1 text-sm text-rose-800">Only inactive terms without assessments or attendance records can be deleted.</p>
                    @error('delete')
                        <p class="mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex shrink-0 gap-3">
                    <x-button wire:click="cancelDelete" variant="ghost" target="cancelDelete" :loading="true">Cancel</x-button>
                    <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete term</x-button>
                </div>
            </div>
        </section>
    @endif

    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-sm">
            <label for="term-search" class="sr-only">Search terms</label>
            <input id="term-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search terms or academic years..."
                class="w-full rounded-xl border-slate-300 py-2.5 pl-4 pr-10 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
            <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">Searching...</span>
        </div>
        <p class="text-sm text-slate-500">
            <span wire:loading.remove wire:target="search">{{ $terms->total() }} {{ \Illuminate\Support\Str::plural('term', $terms->total()) }}</span>
            <span wire:loading wire:target="search">Updating results...</span>
        </p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Term</th>
                        <th class="px-5 py-3 font-semibold">Academic year</th>
                        <th class="px-5 py-3 font-semibold">Date range</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                        <th class="px-5 py-3 text-right font-semibold"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($terms as $term)
                        <tr wire:key="term-{{ $term->id }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900">{{ $term->name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">Term {{ $term->sequence }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $term->academicYear->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $term->starts_at->format('M d, Y') }} – {{ $term->ends_at->format('M d, Y') }}</td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <x-badge :variant="$term->is_active ? 'success' : 'default'">{{ $term->is_active ? 'Active' : 'Inactive' }}</x-badge>
                                    @if ($term->is_locked)
                                        <x-badge variant="warning">Locked</x-badge>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    @can('update', $term)
                                        <x-ui.icon-button wire:click="edit({{ $term->id }})" icon="edit" label="Edit {{ $term->name }}" target="edit({{ $term->id }})" />
                                    @endcan
                                    @can('delete', $term)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $term->id }})" icon="trash" variant="danger" label="Delete {{ $term->name }}" target="confirmDelete({{ $term->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <p class="font-medium text-slate-700">{{ $search ? 'No terms match your search.' : 'No terms yet.' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $search ? 'Try a term or academic year name.' : 'Create the first term for an academic year to get started.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$terms" />
</div>
