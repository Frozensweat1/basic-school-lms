<div class="space-y-6">
    <div><p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Academic records</p><h2 class="mt-2 text-2xl font-bold text-slate-900">My report cards</h2><p class="mt-1 text-sm text-slate-600">Review and print your officially published term reports.</p></div>

    <div class="grid grid-cols-3 gap-4">
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm"><p class="text-sm font-medium text-blue-800">Published reports</p><p class="mt-2 text-3xl font-bold text-blue-900">{{ $reportCount }}</p><p class="mt-1 text-xs text-blue-700">Matching the current filters</p></article>
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm"><p class="text-sm font-medium text-emerald-800">Average attendance</p><p class="mt-2 text-3xl font-bold text-emerald-900">{{ number_format($attendanceAverage, 1) }}%</p><p class="mt-1 text-xs text-emerald-700">Across available report cards</p></article>
        <article class="rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm"><p class="text-sm font-medium text-violet-800">Latest report</p><p class="mt-2 text-lg font-bold text-violet-900">{{ $latestReport?->term?->name ?: 'Not available' }}</p><p class="mt-1 text-xs text-violet-700">{{ $latestReport?->academicYear?->name ?: 'No published reports yet' }}</p></article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_280px_auto] lg:items-center">
        <div class="relative"><label for="student-report-search" class="sr-only">Search report cards</label><svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg><input id="student-report-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search term, academic year, or class" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching...</span></div>
        <select wire:model.live="filterTermId" aria-label="Filter by term" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">All terms</option>@foreach ($terms as $term)<option value="{{ $term->id }}">{{ $term->academicYear->name }} - {{ $term->name }}</option>@endforeach</select>
        @if (filled($search) || filled($filterTermId))<x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>@endif
    </div></section>

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($reports as $report)<x-ui.report-card-summary :report="$report" />@empty<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500 lg:col-span-2">{{ filled($search) || filled($filterTermId) ? 'No report cards match the current search and filter.' : 'No published report cards are available yet.' }}</div>@endforelse
    </div>
    @if ($reports->hasPages())<div class="rounded-2xl border border-slate-200 bg-white px-5 py-4">{{ $reports->links() }}</div>@endif
</div>
