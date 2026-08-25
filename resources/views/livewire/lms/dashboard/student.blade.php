<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Learning overview</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">Student dashboard</h2>
        <p class="mt-1 text-sm text-slate-600">Follow your academic performance, attendance, and upcoming learning activities.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($metrics as $label => $value)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                <p class="mt-3 text-2xl font-bold text-slate-900">{{ $value }}</p>
            </article>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.dashboard-chart
            id="student-performance-chart"
            title="My subject performance"
            subtitle="Average assessment percentage for each subject."
            :config="$performanceChart"
            empty-message="Your performance chart will appear after assessment scores are published."
        />
        <x-ui.dashboard-chart
            id="student-attendance-chart"
            title="My attendance"
            subtitle="Distribution of your recorded attendance statuses."
            :config="$attendanceChart"
            empty-message="Your attendance chart will appear after attendance is recorded."
        />
    </div>

    <x-ui.performance-chart-grid prefix="student" :overview="$performanceOverview" audience-label="My performance" />

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-bold text-slate-900">Recent results</h2>
            <div class="mt-4 space-y-3">
                @forelse ($recentResults as $result)
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3">
                        <span class="font-medium text-slate-900">{{ $result->assessment->classSubject->subject->name }} &middot; {{ $result->assessment->title }}</span>
                        <span class="font-semibold text-blue-700">{{ $result->score }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No results have been published yet.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-bold text-slate-900">Announcements</h2>
                <a href="{{ route('lms.announcements.feed') }}" class="text-sm font-semibold text-blue-800 hover:text-blue-950">View all</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($announcements as $announcement)
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="font-medium text-slate-900">{{ $announcement->title }}</p>
                        <p class="text-xs text-slate-500">{{ $announcement->published_at?->diffForHumans() }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No announcements for your classes.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
