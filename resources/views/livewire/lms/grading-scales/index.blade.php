<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Assessment setup</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Grading scales</h2>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">Set the score ranges used to translate calculated subject results into grades.</p>
        </div>

        @can('create', App\Models\GradingScale::class)
            <div class="flex justify-end">
                <x-button wire:click="create" variant="primary" icon="plus" target="create" :loading="true">Add grading scale</x-button>
            </div>
        @endcan
    </div>

    <div class="grid grid-cols-3 gap-4">
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-blue-800">Configured grades</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ $configuredCount }}</p>
            <p class="mt-1 text-xs text-blue-700">Matching current search and filters</p>
        </article>
        <article class="rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-violet-800">Score coverage</p>
            <p class="mt-2 text-3xl font-bold text-violet-900">
                @if ($minimumScore !== null && $maximumScore !== null)
                    {{ rtrim(rtrim(number_format((float) $minimumScore, 2, '.', ''), '0'), '.') }}–{{ rtrim(rtrim(number_format((float) $maximumScore, 2, '.', ''), '0'), '.') }}
                @else
                    —
                @endif
            </p>
            <p class="mt-1 text-xs text-violet-700">Lowest to highest configured score</p>
        </article>
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-800">Applied grades</p>
            <p class="mt-2 text-3xl font-bold text-emerald-900">{{ $usedCount }}</p>
            <p class="mt-1 text-xs text-emerald-700">{{ $publishedResultCount }} published {{ \Illuminate\Support\Str::plural('result', $publishedResultCount) }} in this school</p>
        </article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto] lg:items-center">
            <div class="relative">
                <label for="grading-scale-search" class="sr-only">Search grading scales</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input id="grading-scale-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search grade, score, or remark" autocomplete="off" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700">
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>
            </div>

            <select wire:model.live="filterUsage" aria-label="Filter by use" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All grading scales</option>
                <option value="used">Used by results</option>
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
                    <tr>
                        <th class="px-5 py-3">Grade</th>
                        <th class="px-5 py-3">Score range</th>
                        <th class="px-5 py-3">Remark</th>
                        <th class="px-5 py-3">Results</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($scales as $scale)
                        <tr wire:key="grading-scale-{{ $scale->id }}" class="hover:bg-slate-50/80">
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-sm font-bold text-blue-800">{{ $scale->grade }}</span>
                                <p class="mt-1 text-xs text-slate-500">Sequence {{ $scale->sequence }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ rtrim(rtrim(number_format((float) $scale->minimum, 2, '.', ''), '0'), '.') }}–{{ rtrim(rtrim(number_format((float) $scale->maximum, 2, '.', ''), '0'), '.') }}</p>
                                <p class="mt-1 text-xs text-slate-500">Inclusive score range</p>
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ $scale->remark ?: '—' }}</td>
                            <td class="px-5 py-4">
                                @if ($scale->subject_results_count)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">{{ $scale->subject_results_count }} {{ \Illuminate\Support\Str::plural('result', $scale->subject_results_count) }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Not used yet</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @can('update', $scale)
                                        <x-ui.icon-button wire:click="edit({{ $scale->id }})" icon="edit" label="Edit grade {{ $scale->grade }}" target="edit({{ $scale->id }})" />
                                    @endcan
                                    @can('delete', $scale)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $scale->id }})" icon="trash" label="Delete grade {{ $scale->grade }}" variant="danger" target="confirmDelete({{ $scale->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center text-slate-500">
                                @if (filled($search) || filled($filterUsage))
                                    No grading scales match the current search and filters.
                                @else
                                    No grading scales have been configured yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$scales" />

    @if ($showFormModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-slate-950/70 p-4 backdrop-blur-sm" style="background-color:rgba(2,6,23,.72)" role="dialog" aria-modal="true" aria-labelledby="grading-scale-form-title">
            <div class="my-6 w-full max-w-xl rounded-2xl bg-white shadow-2xl ring-1 ring-black/20">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Assessment setup</p>
                        <h3 id="grading-scale-form-title" class="mt-1 text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit grading scale' : 'Add grading scale' }}</h3>
                    </div>
                    <x-ui.icon-button wire:click="closeModals" icon="close" label="Close form" target="closeModals" />
                </div>

                <form wire:submit="save" class="space-y-5 p-6">
                    <div>
                        <label for="grading-scale-grade" class="block text-sm font-medium text-slate-700">Grade</label>
                        <input wire:model.blur="grade" id="grading-scale-grade" type="text" maxlength="20" placeholder="A" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                        @error('grade') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="grading-scale-minimum" class="block text-sm font-medium text-slate-700">Minimum score</label>
                            <input wire:model.blur="minimum" id="grading-scale-minimum" type="number" min="0" max="100" step="0.01" placeholder="80" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            @error('minimum') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="grading-scale-maximum" class="block text-sm font-medium text-slate-700">Maximum score</label>
                            <input wire:model.blur="maximum" id="grading-scale-maximum" type="number" min="0" max="100" step="0.01" placeholder="100" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            @error('maximum') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <p class="-mt-2 text-xs text-slate-500">Ranges are inclusive and cannot overlap another configured grade.</p>

                    <div>
                        <label for="grading-scale-remark" class="block text-sm font-medium text-slate-700">Remark <span class="font-normal text-slate-500">(optional)</span></label>
                        <input wire:model.blur="remark" id="grading-scale-remark" type="text" maxlength="255" placeholder="Excellent" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                        @error('remark') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="grading-scale-sequence" class="block text-sm font-medium text-slate-700">Display sequence</label>
                        <input wire:model.blur="sequence" id="grading-scale-sequence" type="number" min="0" max="9999" step="1" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                        @error('sequence') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                        <x-button type="submit" variant="primary" icon="save" target="save" :loading="true">{{ $editingId ? 'Save changes' : 'Add grading scale' }}</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-slate-950/70 p-4 backdrop-blur-sm" style="background-color:rgba(2,6,23,.72)" role="dialog" aria-modal="true" aria-labelledby="grading-scale-delete-title">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-black/20">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700">!</div>
                    <div>
                        <h3 id="grading-scale-delete-title" class="text-lg font-semibold text-slate-900">Delete grading scale?</h3>
                        <p class="mt-1 text-sm text-slate-600">This cannot be undone. Grades already attached to subject results are protected and must be updated instead.</p>
                    </div>
                </div>

                @error('delete') <p class="mt-4 rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p> @enderror

                <div class="mt-6 flex justify-end gap-3">
                    <x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                    <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete scale</x-button>
                </div>
            </div>
        </div>
    @endif
</div>
