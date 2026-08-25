@props(['viewMode' => 'calendar'])

<div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm" role="group" aria-label="Timetable view">
    <button
        type="button"
        wire:click="showCalendar"
        wire:loading.attr="disabled"
        wire:target="showCalendar"
        @class([
            'inline-flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60',
            'bg-blue-900 text-white shadow-sm' => $viewMode === 'calendar',
            'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => $viewMode !== 'calendar',
        ])
        aria-pressed="{{ $viewMode === 'calendar' ? 'true' : 'false' }}"
    >
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2.5" y="3.5" width="15" height="14" rx="2"></rect><path d="M6 2v3M14 2v3M2.5 8h15M7.5 11h.01M12.5 11h.01M7.5 14.5h.01M12.5 14.5h.01"></path></svg>
        Calendar
    </button>
    <button
        type="button"
        wire:click="showList"
        wire:loading.attr="disabled"
        wire:target="showList"
        @class([
            'inline-flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60',
            'bg-blue-900 text-white shadow-sm' => $viewMode === 'list',
            'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => $viewMode !== 'list',
        ])
        aria-pressed="{{ $viewMode === 'list' ? 'true' : 'false' }}"
    >
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 5h10M7 10h10M7 15h10M3 5h.01M3 10h.01M3 15h.01"></path></svg>
        List
    </button>
</div>
