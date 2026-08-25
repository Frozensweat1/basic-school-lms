<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Scheduling</p><h2 class="mt-2 text-2xl font-bold text-slate-900">Timetables</h2><p class="mt-1 max-w-2xl text-sm text-slate-600">Build schedules manually or generate balanced sessions while avoiding class and teacher clashes.</p></div>
        @can('create', App\Models\Timetable::class)<div class="flex justify-end"><x-button wire:click="create" icon="plus" target="create" :loading="true">Create timetable</x-button></div>@endcan
    </div>

    <div class="grid grid-cols-3 gap-4">
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm"><p class="text-sm font-medium text-blue-800">Matching timetables</p><p class="mt-2 text-3xl font-bold text-blue-900">{{ $timetables->total() }}</p><p class="mt-1 text-xs text-blue-700">Across current search and filters</p></article>
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm"><p class="text-sm font-medium text-emerald-800">Published</p><p class="mt-2 text-3xl font-bold text-emerald-900">{{ $publishedCount }}</p><p class="mt-1 text-xs text-emerald-700">{{ $draftCount }} draft {{ \Illuminate\Support\Str::plural('schedule', $draftCount) }}</p></article>
        <article class="rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm"><p class="text-sm font-medium text-violet-800">Scheduled sessions</p><p class="mt-2 text-3xl font-bold text-violet-900">{{ $entryCount }}</p><p class="mt-1 text-xs text-violet-700">Entries in matching timetables</p></article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_repeat(3,minmax(0,190px))_auto] xl:items-center">
            <div class="relative"><label for="timetable-search" class="sr-only">Search timetables</label><svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg><input id="timetable-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search timetable, year, or term" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span></div>
            <select wire:model.live="filterAcademicYearId" aria-label="Filter by academic year" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">All years</option>@foreach($years as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach</select>
            <select wire:model.live="filterTermId" aria-label="Filter by term" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">All terms</option>@foreach($terms->when(filled($filterAcademicYearId), fn($items) => $items->where('academic_year_id', (int) $filterAcademicYearId)) as $term)<option value="{{ $term->id }}">{{ $term->name }}</option>@endforeach</select>
            <select wire:model.live="filterStatus" aria-label="Filter by status" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">All statuses</option>@foreach(App\Models\Timetable::STATUSES as $option)<option value="{{ $option }}">{{ ucfirst($option) }}</option>@endforeach</select>
            @if(filled($search) || filled($filterAcademicYearId) || filled($filterTermId) || filled($filterStatus))<x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>@endif
        </div>
    </section>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Timetable</th><th class="px-5 py-3">Academic context</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Sessions</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($timetables as $timetable)
                @php $statusClass = ['published'=>'bg-emerald-100 text-emerald-800','draft'=>'bg-amber-100 text-amber-800','archived'=>'bg-slate-200 text-slate-700'][$timetable->status] ?? 'bg-slate-100 text-slate-700'; @endphp
                <tr wire:key="timetable-{{ $timetable->id }}" class="hover:bg-slate-50/80">
                    <td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $timetable->name }}</p><p class="mt-1 text-xs text-slate-500">Created {{ $timetable->created_at->format('d M Y') }}</p></td>
                    <td class="px-5 py-4"><p class="font-medium text-slate-900">{{ $timetable->academicYear->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $timetable->term->name }}</p></td>
                    <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ ucfirst($timetable->status) }}</span></td>
                    <td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $timetable->entries_count }}</p><p class="mt-1 text-xs text-slate-500">Scheduled {{ \Illuminate\Support\Str::plural('session', $timetable->entries_count) }}</p></td>
                    <td class="px-5 py-4"><div class="flex flex-wrap justify-end gap-2">
                        @can('update', $timetable)
                            <a href="{{ route('lms.timetables.entries.index', $timetable) }}" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-800 transition hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-200"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>Entries</a>
                            <x-button wire:click="confirmGenerate({{ $timetable->id }})" variant="secondary" size="xs" icon="sparkles" target="confirmGenerate({{ $timetable->id }})" :loading="true">Generate</x-button>
                            <x-ui.icon-button wire:click="edit({{ $timetable->id }})" icon="edit" label="Edit {{ $timetable->name }}" target="edit({{ $timetable->id }})" />
                        @endcan
                        @can('delete', $timetable)<x-ui.icon-button wire:click="confirmDelete({{ $timetable->id }})" icon="trash" label="Delete {{ $timetable->name }}" variant="danger" target="confirmDelete({{ $timetable->id }})" />@endcan
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-14 text-center text-slate-500">{{ filled($search) || filled($filterAcademicYearId) || filled($filterTermId) || filled($filterStatus) ? 'No timetables match the current filters.' : 'No timetables have been created yet.' }}</td></tr>
            @endforelse
        </tbody>
    </table></div></div>
    <x-pagination :paginator="$timetables" />

    @if($showFormModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-slate-950/70 p-4 backdrop-blur-sm" style="background-color:rgba(2,6,23,.72)" role="dialog" aria-modal="true" aria-labelledby="timetable-form-title"><div class="my-6 w-full max-w-2xl rounded-2xl bg-white shadow-2xl ring-1 ring-black/20">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Scheduling</p><h3 id="timetable-form-title" class="mt-1 text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit timetable' : 'Create timetable' }}</h3></div><x-ui.icon-button wire:click="closeModals" icon="close" label="Close form" target="closeModals" /></div>
            <form wire:submit="save" class="space-y-5 p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div><label for="timetable-year" class="block text-sm font-medium text-slate-700">Academic year</label><select wire:model.live="academicYearId" id="timetable-year" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">Choose a year</option>@foreach($years as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach</select>@error('academicYearId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div><label for="timetable-term" class="block text-sm font-medium text-slate-700">Term</label><select wire:model.blur="termId" id="timetable-term" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">Choose a term</option>@foreach($terms->when(filled($academicYearId), fn($items) => $items->where('academic_year_id', (int) $academicYearId)) as $term)<option value="{{ $term->id }}">{{ $term->name }}</option>@endforeach</select>@error('termId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                </div>
                <div><label for="timetable-name" class="block text-sm font-medium text-slate-700">Timetable name</label><input wire:model.blur="name" id="timetable-name" type="text" maxlength="100" placeholder="Term 1 master timetable" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">@error('name')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                <div><label for="timetable-status" class="block text-sm font-medium text-slate-700">Status</label><select wire:model.blur="status" id="timetable-status" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">@foreach(App\Models\Timetable::STATUSES as $option)<option value="{{ $option }}">{{ ucfirst($option) }}</option>@endforeach</select>@error('status')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror<p class="mt-1 text-xs text-slate-500">Only published timetables are visible to teachers, learners, and parents.</p></div>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5"><x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button><x-button type="submit" icon="save" target="save" :loading="true">{{ $editingId ? 'Save changes' : 'Create timetable' }}</x-button></div>
            </form>
        </div></div>
    @endif

    @if($showGenerateModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-slate-950/70 p-4 backdrop-blur-sm" style="background-color:rgba(2,6,23,.72)" role="dialog" aria-modal="true" aria-labelledby="timetable-generate-title"><div class="my-6 w-full max-w-xl rounded-2xl bg-white shadow-2xl ring-1 ring-black/20">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Conflict-aware generation</p><h3 id="timetable-generate-title" class="mt-1 text-lg font-semibold text-slate-900">Generate {{ $generatingTimetable?->name }}</h3></div><x-ui.icon-button wire:click="closeModals" icon="close" label="Close generator" target="closeModals" /></div>
            <div class="space-y-5 p-6">
                <div class="grid grid-cols-3 gap-3"><div class="rounded-xl bg-blue-50 p-3 text-center"><p class="text-xl font-bold text-blue-900">{{ $generationSubjectCount }}</p><p class="text-xs text-blue-700">Class subjects</p></div><div class="rounded-xl bg-violet-50 p-3 text-center"><p class="text-xl font-bold text-violet-900">{{ $generationPeriodCount }}</p><p class="text-xs text-violet-700">Daily periods</p></div><div class="rounded-xl bg-emerald-50 p-3 text-center"><p class="text-xl font-bold text-emerald-900">{{ $generatingTimetable?->entries_count ?? 0 }}</p><p class="text-xs text-emerald-700">Current entries</p></div></div>
                <p class="text-sm leading-6 text-slate-600">The generator distributes sessions across Monday to Friday, avoids class and teacher clashes, and prefers different days for repeated subject sessions.</p>
                <div><label for="sessions-per-subject" class="block text-sm font-medium text-slate-700">Weekly sessions per class subject</label><input wire:model.blur="sessionsPerSubject" id="sessions-per-subject" type="number" min="1" max="5" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">@error('sessionsPerSubject')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4"><input wire:model="replaceExistingEntries" type="checkbox" class="mt-0.5 rounded border-slate-300 text-blue-800 focus:ring-blue-700"><span><span class="block text-sm font-semibold text-slate-900">Replace existing entries</span><span class="mt-1 block text-xs text-slate-500">Turn this off to preserve manual entries and fill only remaining conflict-free slots.</span></span></label>
                @error('generation')<p class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-5"><x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button><x-button wire:click="generateAutomatically" icon="sparkles" target="generateAutomatically" :loading="true">Generate timetable</x-button></div>
            </div>
        </div></div>
    @endif

    @if($showDeleteModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm" style="background-color:rgba(2,6,23,.72)" role="dialog" aria-modal="true" aria-labelledby="timetable-delete-title"><div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-black/20"><h3 id="timetable-delete-title" class="text-lg font-semibold text-slate-900">Delete timetable?</h3><p class="mt-2 text-sm text-slate-600">This permanently removes the timetable and every scheduled entry it contains.</p><div class="mt-6 flex justify-end gap-3"><x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button><x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete timetable</x-button></div></div></div>
    @endif
</div>
