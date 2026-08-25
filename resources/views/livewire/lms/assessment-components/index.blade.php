<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Assessment setup</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Assessment components</h2>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">Define the weighted parts of each term, then attach assessments to the appropriate component.</p>
        </div>

        <div class="flex flex-wrap justify-end gap-3">
            <a href="{{ route($assessmentRouteName) }}" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M4 19V5M9 19v-7M14 19V9M19 19V3"></path>
                </svg>
                Assessments
            </a>
            @can('create', App\Models\AssessmentComponent::class)
                <x-button wire:click="create" variant="primary" icon="plus" target="create" :loading="true">Add component</x-button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-blue-800">Components</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ $components->total() }}</p>
            <p class="mt-1 text-xs text-blue-700">Matching current search and filters</p>
        </article>
        <article class="rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-violet-800">Configured weight</p>
            <p class="mt-2 text-3xl font-bold text-violet-900">{{ rtrim(rtrim((string) $totalWeight, '0'), '.') }}%</p>
            <p class="mt-1 text-xs text-violet-700">Across all school terms</p>
        </article>
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-800">Linked assessments</p>
            <p class="mt-2 text-3xl font-bold text-emerald-900">{{ $components->sum('assessments_count') }}</p>
            <p class="mt-1 text-xs text-emerald-700">On this page of components</p>
        </article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_repeat(2,minmax(0,200px))_auto] xl:items-center">
            <div class="relative">
                <label for="assessment-component-search" class="sr-only">Search components</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input id="assessment-component-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search component, term, or academic year" autocomplete="off" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700">
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>
            </div>

            <select wire:model.live="filterAcademicYearId" aria-label="Filter by academic year" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All years</option>
                @foreach ($years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterTermId" aria-label="Filter by term" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All terms</option>
                @foreach ($terms->when(filled($filterAcademicYearId), fn ($items) => $items->where('academic_year_id', (int) $filterAcademicYearId)) as $term)
                    <option value="{{ $term->id }}">{{ $term->academicYear->name }} · {{ $term->name }}</option>
                @endforeach
            </select>

            @if (filled($search) || filled($filterAcademicYearId) || filled($filterTermId))
                <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>
            @endif
        </div>
    </section>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Component</th>
                        <th class="px-5 py-3">Term</th>
                        <th class="px-5 py-3">Weight</th>
                        <th class="px-5 py-3">Linked assessments</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($components as $assessmentComponent)
                        <tr wire:key="assessment-component-{{ $assessmentComponent->id }}" class="hover:bg-slate-50/80">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ $assessmentComponent->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">Sequence {{ $assessmentComponent->sequence }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900">{{ $assessmentComponent->term->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $assessmentComponent->term->academicYear->name }}</p>
                            </td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-800">{{ $assessmentComponent->weight }}%</span></td>
                            <td class="px-5 py-4">
                                <a href="{{ route($assessmentRouteName, ['component' => $assessmentComponent->id]) }}" class="font-semibold text-blue-700 hover:text-blue-900 hover:underline">
                                    {{ $assessmentComponent->assessments_count }} {{ \Illuminate\Support\Str::plural('assessment', $assessmentComponent->assessments_count) }}
                                </a>
                                <p class="mt-1 text-xs text-slate-500">View connected assessments</p>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @can('update', $assessmentComponent)
                                        <x-ui.icon-button wire:click="edit({{ $assessmentComponent->id }})" icon="edit" label="Edit {{ $assessmentComponent->name }}" target="edit({{ $assessmentComponent->id }})" />
                                    @endcan
                                    @can('delete', $assessmentComponent)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $assessmentComponent->id }})" icon="trash" label="Delete {{ $assessmentComponent->name }}" variant="danger" target="confirmDelete({{ $assessmentComponent->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center text-slate-500">
                                @if (filled($search) || filled($filterAcademicYearId) || filled($filterTermId))
                                    No assessment components match the current search and filters.
                                @else
                                    No assessment components have been configured yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$components" />

    @if ($showFormModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-slate-950/70 p-4 backdrop-blur-sm" style="background-color:rgba(2,6,23,.72)" role="dialog" aria-modal="true" aria-labelledby="assessment-component-form-title">
            <div class="w-full max-w-xl rounded-2xl bg-white shadow-2xl ring-1 ring-black/20">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Assessment setup</p>
                        <h3 id="assessment-component-form-title" class="mt-1 text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit component' : 'Add component' }}</h3>
                    </div>
                    <x-ui.icon-button wire:click="closeModals" icon="close" label="Close form" target="closeModals" />
                </div>

                <form wire:submit="save" class="space-y-5 p-6">
                    <div>
                        <label for="assessment-component-term" class="block text-sm font-medium text-slate-700">Term</label>
                        <select wire:model.blur="termId" id="assessment-component-term" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            <option value="">Choose a term</option>
                            @foreach ($terms as $term)
                                <option value="{{ $term->id }}">{{ $term->academicYear->name }} · {{ $term->name }}</option>
                            @endforeach
                        </select>
                        @error('termId') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="assessment-component-name" class="block text-sm font-medium text-slate-700">Component name</label>
                        <input wire:model.blur="name" id="assessment-component-name" type="text" placeholder="Class Exercise" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                        @error('name') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="assessment-component-weight" class="block text-sm font-medium text-slate-700">Weight (%)</label>
                            <input wire:model.blur="weight" id="assessment-component-weight" type="number" min="0.01" max="100" step="0.01" placeholder="20" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            <p class="mt-1 text-xs text-slate-500">All component weights in a term must total no more than 100%.</p>
                            @error('weight') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="assessment-component-sequence" class="block text-sm font-medium text-slate-700">Display order</label>
                            <input wire:model.blur="sequence" id="assessment-component-sequence" type="number" min="0" max="9999" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                            @error('sequence') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <x-button type="button" wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                        <x-button type="submit" variant="primary" icon="save" target="save" :loading="true">{{ $editingId ? 'Save changes' : 'Save component' }}</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm" style="background-color:rgba(2,6,23,.72)" role="dialog" aria-modal="true" aria-labelledby="delete-assessment-component-title">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-black/20">
                <h3 id="delete-assessment-component-title" class="text-lg font-semibold text-slate-900">Delete component?</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Components linked to assessments are retained to protect historic calculation records.</p>
                @error('delete')
                    <p class="mt-3 rounded-xl bg-rose-50 p-3 text-sm text-rose-700">{{ $message }}</p>
                @enderror
                <div class="mt-6 flex justify-end gap-3">
                    <x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                    <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete component</x-button>
                </div>
            </div>
        </div>
    @endif
</div>
