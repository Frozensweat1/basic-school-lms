<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Ward overview</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">Parent dashboard</h2>
        <p class="mt-1 text-sm text-slate-600">See how your wards are progressing academically and attending school.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($metrics as $label => $value)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                <p class="mt-3 text-2xl font-bold text-slate-900">{{ $value }}</p>
            </article>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.dashboard-chart
            id="parent-performance-chart"
            title="Academic performance by ward"
            subtitle="Average normalized assessment percentage for each ward."
            :config="$wardPerformanceChart"
            empty-message="Ward performance will appear after assessment scores are published."
        />
        <x-ui.dashboard-chart
            id="parent-attendance-chart"
            title="Ward attendance"
            subtitle="Combined attendance distribution for your wards."
            :config="$attendanceChart"
            empty-message="Ward attendance will appear after attendance is recorded."
        />
    </div>

    <x-ui.performance-chart-grid prefix="parent" :overview="$performanceOverview" audience-label="Ward performance" />

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-bold text-slate-900">School announcements</h2>
            <a href="{{ route('lms.announcements.feed') }}" class="text-sm font-semibold text-blue-800 hover:text-blue-950">View all</a>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @forelse ($announcements as $announcement)
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="font-medium text-slate-900">{{ $announcement->title }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $announcement->published_at?->diffForHumans() }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No recent announcements.</p>
            @endforelse
        </div>
    </section>
</div>
