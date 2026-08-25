@props([
    'entries',
    'periods',
    'dayFilter' => '',
    'periodFilter' => '',
    'showClass' => true,
    'showTeacher' => false,
    'showTerm' => false,
    'editable' => false,
])

@php
    $entryCollection = collect($entries);
    $periodCollection = collect($periods);
    $visibleDays = collect(App\Models\TimetableEntry::DAYS)
        ->when(filled($dayFilter), fn ($days) => $days->only([(int) $dayFilter]));
    $visiblePeriods = $periodCollection
        ->when(filled($periodFilter), fn ($items) => $items->where('id', (int) $periodFilter));
    $entriesBySlot = $entryCollection->groupBy(fn ($entry) => $entry->schedule_period_id.':'.$entry->day_of_week);
    $today = now()->dayOfWeekIso;
    $cardStyles = [
        'border-blue-200 bg-blue-50 text-blue-950',
        'border-violet-200 bg-violet-50 text-violet-950',
        'border-emerald-200 bg-emerald-50 text-emerald-950',
        'border-amber-200 bg-amber-50 text-amber-950',
        'border-cyan-200 bg-cyan-50 text-cyan-950',
    ];
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-label="Weekly timetable calendar">
    <div class="flex flex-col gap-2 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h3 class="font-bold text-slate-900">Weekly calendar</h3><p class="mt-1 text-xs text-slate-500">Scroll horizontally on smaller screens to see every school day.</p></div>
        <span class="inline-flex w-fit rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">{{ $entryCollection->count() }} {{ Illuminate\Support\Str::plural('lesson', $entryCollection->count()) }}</span>
    </div>

    @if ($visiblePeriods->isEmpty())
        <div class="px-6 py-14 text-center text-sm text-slate-500">No schedule periods are available for this calendar.</div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full table-fixed border-collapse {{ $visibleDays->count() === 1 ? 'min-w-[520px]' : 'min-w-[1180px]' }}">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-20 w-44 border-b border-r border-slate-200 bg-slate-100 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600">Period</th>
                        @foreach ($visibleDays as $dayNumber => $dayName)
                            <th class="border-b border-r border-slate-200 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide {{ $today === $dayNumber ? 'bg-blue-100 text-blue-900' : 'bg-slate-50 text-slate-600' }}">
                                <span>{{ $dayName }}</span>
                                @if ($today === $dayNumber)<span class="ml-1 rounded-full bg-blue-900 px-2 py-0.5 text-[10px] text-white">Today</span>@endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($visiblePeriods as $period)
                        <tr wire:key="calendar-period-{{ $period->id }}">
                            <th class="sticky left-0 z-10 border-b border-r border-slate-200 bg-white px-4 py-4 text-left align-top">
                                <p class="text-sm font-bold text-slate-900">{{ $period->name }}</p>
                                <p class="mt-1 text-xs font-medium text-slate-500">{{ $period->formattedStart() }}-{{ $period->formattedEnd() }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $period->durationMinutes() }} minutes</p>
                            </th>
                            @foreach ($visibleDays as $dayNumber => $dayName)
                                @php($slotEntries = $entriesBySlot->get($period->id.':'.$dayNumber, collect()))
                                <td class="border-b border-r border-slate-200 p-2 align-top {{ $today === $dayNumber ? 'bg-blue-50/40' : 'bg-white' }}">
                                    @if ($slotEntries->isEmpty())
                                        <div class="flex min-h-24 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/60 text-xs font-medium text-slate-300">Available</div>
                                    @else
                                        <div class="space-y-2">
                                            @foreach ($slotEntries as $entry)
                                                <article wire:key="calendar-entry-{{ $entry->id }}" class="rounded-xl border p-3 shadow-sm {{ $cardStyles[$loop->index % count($cardStyles)] }}">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <p class="text-sm font-bold leading-tight">{{ $entry->classSubject?->subject?->name ?? 'Scheduled lesson' }}</p>
                                                        @if ($editable)
                                                            <div class="flex shrink-0 gap-1"><x-ui.icon-button wire:click="edit({{ $entry->id }})" icon="edit" label="Edit timetable entry" target="edit({{ $entry->id }})" /><x-ui.icon-button wire:click="confirmDelete({{ $entry->id }})" icon="trash" label="Delete timetable entry" variant="danger" target="confirmDelete({{ $entry->id }})" /></div>
                                                        @endif
                                                    </div>
                                                    @if ($showClass && $entry->schoolClass)<p class="mt-1 text-xs font-semibold opacity-80">{{ $entry->schoolClass->name }}</p>@endif
                                                    @if ($showTeacher)<p class="mt-1 text-xs opacity-75">{{ $entry->teacher ? $entry->teacher->first_name.' '.$entry->teacher->last_name : 'Teacher not assigned' }}</p>@endif
                                                    <div class="mt-2 flex flex-wrap gap-1.5 text-[11px] font-semibold">
                                                        @if ($entry->room)<span class="rounded-full bg-white/75 px-2 py-0.5">Room {{ $entry->room }}</span>@endif
                                                        @if ($showTerm && $entry->timetable?->term)<span class="rounded-full bg-white/75 px-2 py-0.5">{{ $entry->timetable->term->name }}</span>@endif
                                                    </div>
                                                </article>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
