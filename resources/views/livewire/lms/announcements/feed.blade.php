<div class="space-y-6">
    <div><p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Communication</p><h2 class="mt-2 text-2xl font-bold text-slate-900">Announcement feed</h2><p class="mt-1 text-sm text-slate-600">Current school, class, subject, and teacher updates relevant to you.</p></div>

    <div class="grid grid-cols-3 gap-4">
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm"><p class="text-sm font-medium text-blue-800">Current notices</p><p class="mt-2 text-3xl font-bold text-blue-900">{{ $announcementCount }}</p><p class="mt-1 text-xs text-blue-700">Matching the current filters</p></article>
        <article class="rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm"><p class="text-sm font-medium text-violet-800">School-wide</p><p class="mt-2 text-3xl font-bold text-violet-900">{{ $schoolCount }}</p><p class="mt-1 text-xs text-violet-700">Updates for the whole school</p></article>
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm"><p class="text-sm font-medium text-emerald-800">Targeted updates</p><p class="mt-2 text-3xl font-bold text-emerald-900">{{ $targetedCount }}</p><p class="mt-1 text-xs text-emerald-700">Relevant class, subject, or teacher notices</p></article>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_240px_auto] lg:items-center">
        <div class="relative"><label for="feed-search" class="sr-only">Search announcement feed</label><svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg><input id="feed-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search announcements or authors" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching...</span></div>
        <select wire:model.live="filterAudience" aria-label="Filter by audience" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">All relevant audiences</option><option value="school">School-wide</option><option value="teachers">Teachers</option><option value="class">Class</option><option value="subject">Subject</option></select>
        @if (filled($search) || filled($filterAudience))<x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>@endif
    </div></section>

    <div class="grid gap-4">
        @forelse ($announcements as $announcement)<x-ui.announcement-card wire:key="feed-announcement-{{ $announcement->id }}" :announcement="$announcement" />@empty<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">{{ filled($search) || filled($filterAudience) ? 'No announcements match the current search and filter.' : 'No current announcements are relevant to you.' }}</div>@endforelse
    </div>
    @if ($announcements->hasPages())<div class="rounded-2xl border border-slate-200 bg-white px-5 py-4">{{ $announcements->links() }}</div>@endif
</div>
