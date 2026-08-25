<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <a href="{{ route('lms.timetables.admin.index') }}" wire:navigate class="text-sm font-semibold text-blue-800 hover:text-blue-950">&larr; All timetables</a>
            <p class="mt-3 text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Timetable entries</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $timetable->name }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $timetable->academicYear->name }} &middot; {{ $timetable->term->name }}. Each class, teacher, and room can only occupy one slot at a time.</p>
        </div>
        <div class="flex justify-end">
            <x-button wire:click="create" icon="plus" target="create" :loading="true">Add entry</x-button>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-blue-800">Scheduled entries</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ $matchingCount }}</p>
            <p class="mt-1 text-xs text-blue-700">Matching the current search and filters</p>
        </article>
        <article class="rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-violet-800">Classes scheduled</p>
            <p class="mt-2 text-3xl font-bold text-violet-900">{{ $classCount }}</p>
            <p class="mt-1 text-xs text-violet-700">Distinct classes with at least one lesson</p>
        </article>
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-800">Teachers scheduled</p>
            <p class="mt-2 text-3xl font-bold text-emerald-900">{{ $teacherCount }}</p>
            <p class="mt-1 text-xs text-emerald-700">Distinct assigned teachers</p>
        </article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_200px_180px_220px_auto] xl:items-center">
            <div class="relative">
                <label for="entry-search" class="sr-only">Search timetable entries</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                <input id="entry-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search class, subject, teacher, period, or room" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching...</span>
            </div>
            <select wire:model.live="filterClassId" aria-label="Filter by class" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All classes</option>
                @foreach ($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach
            </select>
            <select wire:model.live="filterDay" aria-label="Filter by day" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All days</option>
                @foreach (App\Models\TimetableEntry::DAYS as $number => $day)<option value="{{ $number }}">{{ $day }}</option>@endforeach
            </select>
            <select wire:model.live="filterPeriodId" aria-label="Filter by period" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All schedule periods</option>
                @foreach ($periods as $period)<option value="{{ $period->id }}">{{ $period->name }}</option>@endforeach
            </select>
            @if (filled($search) || filled($filterClassId) || filled($filterDay) || filled($filterPeriodId))
                <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>
            @endif
        </div>
    </section>

    <div class="flex justify-end"><x-ui.timetable-view-toggle :view-mode="$viewMode" /></div>

    @if ($viewMode === 'calendar')
        <x-ui.timetable-grid :entries="$gridEntries" :periods="$periods" :day-filter="$filterDay" :period-filter="$filterPeriodId" :show-class="true" :show-teacher="true" :editable="true" />
    @else
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">Day and time</th><th class="px-5 py-3">Class subject</th><th class="px-5 py-3">Teacher</th><th class="px-5 py-3">Room</th><th class="px-5 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($entries as $entry)
                        <tr wire:key="entry-{{ $entry->id }}" class="hover:bg-slate-50/80">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ $entry->dayName() }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $entry->schedulePeriod->name }} &middot; {{ $entry->schedulePeriod->formattedStart() }}-{{ $entry->schedulePeriod->formattedEnd() }}</p>
                            </td>
                            <td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $entry->classSubject->subject->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $entry->schoolClass->name }}</p></td>
                            <td class="px-5 py-4 text-slate-700">{{ $entry->teacher?->full_name ?? trim(($entry->teacher?->first_name ?? '').' '.($entry->teacher?->last_name ?? '')) ?: 'Not assigned' }}</td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $entry->room ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">{{ $entry->room ?: 'No room' }}</span></td>
                            <td class="px-5 py-4"><div class="flex justify-end gap-2"><x-ui.icon-button wire:click="edit({{ $entry->id }})" icon="edit" label="Edit timetable entry" target="edit({{ $entry->id }})" /><x-ui.icon-button wire:click="confirmDelete({{ $entry->id }})" icon="trash" label="Delete timetable entry" variant="danger" target="confirmDelete({{ $entry->id }})" /></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-14 text-center text-slate-500">{{ filled($search) || filled($filterClassId) || filled($filterDay) || filled($filterPeriodId) ? 'No entries match the current search and filters.' : 'No timetable entries have been added yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($entries->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $entries->links() }}</div>@endif
    </div>
    @endif

    @if ($showFormModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-slate-950/70 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="entry-modal-title">
            <button type="button" class="absolute inset-0 cursor-default" wire:click="closeModal" aria-label="Close timetable entry form"></button>
            <div class="relative z-10 w-full max-w-2xl rounded-2xl border border-white/20 bg-white shadow-2xl">
                <div class="border-b border-slate-200 px-6 py-5"><h3 id="entry-modal-title" class="text-lg font-bold text-slate-900">{{ $editingId ? 'Edit timetable entry' : 'Add timetable entry' }}</h3><p class="mt-1 text-sm text-slate-500">Conflict checks are applied to the class, teacher, and room.</p></div>
                <form wire:submit="save" class="space-y-5 p-6">
                    <div><label for="class-subject" class="block text-sm font-medium text-slate-700">Class subject</label><select id="class-subject" wire:model.blur="classSubjectId" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">Choose a class subject</option>@foreach ($classSubjects as $subject)<option value="{{ $subject->id }}">{{ $subject->schoolClass->name }} - {{ $subject->subject->name }}@if ($subject->teacher) ({{ $subject->teacher->first_name }} {{ $subject->teacher->last_name }})@endif</option>@endforeach</select>@error('classSubjectId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label for="entry-day" class="block text-sm font-medium text-slate-700">Day</label><select id="entry-day" wire:model.blur="dayOfWeek" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">@foreach (App\Models\TimetableEntry::DAYS as $number => $day)<option value="{{ $number }}">{{ $day }}</option>@endforeach</select>@error('dayOfWeek')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><label for="entry-period" class="block text-sm font-medium text-slate-700">Schedule period</label><select id="entry-period" wire:model.blur="periodId" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">Choose a period</option>@foreach ($periods as $period)<option value="{{ $period->id }}">{{ $period->name }} ({{ $period->formattedStart() }}-{{ $period->formattedEnd() }})</option>@endforeach</select>@error('periodId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    </div>
                    <div><label for="entry-room" class="block text-sm font-medium text-slate-700">Room <span class="font-normal text-slate-400">(optional)</span></label><input id="entry-room" wire:model.blur="room" maxlength="100" placeholder="e.g. Science Lab" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">@error('room')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5"><x-button type="button" wire:click="closeModal" variant="secondary" target="closeModal" :loading="true">Cancel</x-button><x-button type="submit" icon="save" target="save" :loading="true">{{ $editingId ? 'Save changes' : 'Add entry' }}</x-button></div>
                </form>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="delete-entry-title">
            <button type="button" class="absolute inset-0 cursor-default" wire:click="closeDeleteModal" aria-label="Close delete confirmation"></button>
            <div class="relative z-10 w-full max-w-md rounded-2xl border border-white/20 bg-white p-6 shadow-2xl">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m19 6-1 14H6L5 6"></path><path d="M10 11v5M14 11v5"></path></svg></div>
                <h3 id="delete-entry-title" class="mt-4 text-lg font-bold text-slate-900">Remove timetable entry?</h3>
                <p class="mt-2 text-sm text-slate-600">This removes the selected lesson from the timetable. The class-subject assignment remains available.</p>
                <div class="mt-6 flex justify-end gap-3"><x-button wire:click="closeDeleteModal" variant="secondary" target="closeDeleteModal" :loading="true">Cancel</x-button><x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Remove entry</x-button></div>
            </div>
        </div>
    @endif
</div>
