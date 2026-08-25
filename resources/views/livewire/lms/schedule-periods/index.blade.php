<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Scheduling setup</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Schedule periods</h2>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">Define the daily lesson slots used by manual and automatically generated timetables.</p>
        </div>
        @can('create', App\Models\SchedulePeriod::class)
            <div class="flex justify-end">
                <x-button wire:click="create" icon="plus" target="create" :loading="true">Add period</x-button>
            </div>
        @endcan
    </div>

    <div class="grid grid-cols-3 gap-4">
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-blue-800">Configured periods</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ $configuredCount }}</p>
            <p class="mt-1 text-xs text-blue-700">Matching the current filters</p>
        </article>
        <article class="rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-violet-800">Daily span</p>
            <p class="mt-2 text-2xl font-bold text-violet-900">{{ $dayStartsAt && $dayEndsAt ? $dayStartsAt.' – '.$dayEndsAt : '—' }}</p>
            <p class="mt-1 text-xs text-violet-700">First start to final end time</p>
        </article>
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-800">Timetable use</p>
            <p class="mt-2 text-3xl font-bold text-emerald-900">{{ $entryCount }}</p>
            <p class="mt-1 text-xs text-emerald-700">Entries across {{ $usedCount }} used {{ \Illuminate\Support\Str::plural('period', $usedCount) }}</p>
        </article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto] lg:items-center">
            <div class="relative">
                <label for="schedule-period-search" class="sr-only">Search schedule periods</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                <input id="schedule-period-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search by period name" autocomplete="off" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>
            </div>
            <select wire:model.live="filterUsage" aria-label="Filter periods by use" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All periods</option>
                <option value="used">Used in timetables</option>
                <option value="unused">Not used yet</option>
            </select>
            @if (filled($search) || filled($filterUsage))
                <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>
            @endif
        </div>
    </section>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">Period</th><th class="px-5 py-3">Time</th><th class="px-5 py-3">Duration</th><th class="px-5 py-3">Usage</th><th class="px-5 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($periods as $period)
                        <tr wire:key="schedule-period-{{ $period->id }}" class="hover:bg-slate-50/80">
                            <td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $period->name }}</p><p class="mt-1 text-xs text-slate-500">Sequence {{ $period->sequence }}</p></td>
                            <td class="px-5 py-4 font-medium text-slate-800">{{ $period->formattedStart() }} – {{ $period->formattedEnd() }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $period->durationMinutes() }} minutes</td>
                            <td class="px-5 py-4">
                                @if ($period->timetable_entries_count)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">{{ $period->timetable_entries_count }} {{ \Illuminate\Support\Str::plural('entry', $period->timetable_entries_count) }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Not used</span>
                                @endif
                            </td>
                            <td class="px-5 py-4"><div class="flex justify-end gap-2">
                                @can('update', $period)<x-ui.icon-button wire:click="edit({{ $period->id }})" icon="edit" label="Edit {{ $period->name }}" target="edit({{ $period->id }})" />@endcan
                                @can('delete', $period)<x-ui.icon-button wire:click="confirmDelete({{ $period->id }})" icon="trash" label="Delete {{ $period->name }}" variant="danger" target="confirmDelete({{ $period->id }})" />@endcan
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-14 text-center text-slate-500">{{ filled($search) || filled($filterUsage) ? 'No schedule periods match the current filters.' : 'No schedule periods have been configured yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$periods" />

    @if ($showFormModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-slate-950/70 p-4 backdrop-blur-sm" style="background-color:rgba(2,6,23,.72)" role="dialog" aria-modal="true" aria-labelledby="schedule-period-form-title">
            <div class="my-6 w-full max-w-xl rounded-2xl bg-white shadow-2xl ring-1 ring-black/20">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Scheduling setup</p><h3 id="schedule-period-form-title" class="mt-1 text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit schedule period' : 'Add schedule period' }}</h3></div>
                    <x-ui.icon-button wire:click="closeModals" icon="close" label="Close form" target="closeModals" />
                </div>
                <form wire:submit="save" class="space-y-5 p-6">
                    <div><label for="schedule-period-name" class="block text-sm font-medium text-slate-700">Period name</label><input wire:model.blur="name" id="schedule-period-name" type="text" maxlength="100" placeholder="Period 1" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">@error('name')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label for="schedule-period-start" class="block text-sm font-medium text-slate-700">Starts at</label><input wire:model.blur="startsAt" id="schedule-period-start" type="time" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">@error('startsAt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><label for="schedule-period-end" class="block text-sm font-medium text-slate-700">Ends at</label><input wire:model.blur="endsAt" id="schedule-period-end" type="time" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">@error('endsAt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    </div>
                    <p class="-mt-2 text-xs text-slate-500">Periods cannot overlap. Breaks can be represented by leaving a gap between periods.</p>
                    <div><label for="schedule-period-sequence" class="block text-sm font-medium text-slate-700">Display sequence</label><input wire:model.blur="sequence" id="schedule-period-sequence" type="number" min="0" max="9999" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">@error('sequence')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5"><x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button><x-button type="submit" icon="save" target="save" :loading="true">{{ $editingId ? 'Save changes' : 'Add period' }}</x-button></div>
                </form>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm" style="background-color:rgba(2,6,23,.72)" role="dialog" aria-modal="true" aria-labelledby="schedule-period-delete-title">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-black/20">
                <h3 id="schedule-period-delete-title" class="text-lg font-semibold text-slate-900">Delete schedule period?</h3><p class="mt-2 text-sm text-slate-600">The period can only be deleted when no timetable entry uses it.</p>
                @error('delete')<p class="mt-4 rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                <div class="mt-6 flex justify-end gap-3"><x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button><x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete period</x-button></div>
            </div>
        </div>
    @endif
</div>
