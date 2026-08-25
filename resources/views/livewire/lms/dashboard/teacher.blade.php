<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Teaching overview</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">Teacher dashboard</h2>
        <p class="mt-1 text-sm text-slate-600">Track your workload, learner performance, and submissions requiring attention.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($metrics as $label => $value)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $value }}</p>
            </article>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.dashboard-chart
            id="teacher-workload-chart"
            title="Teaching workload"
            subtitle="Learning activities and submissions currently assigned to you."
            :config="$workloadChart"
            empty-message="Your teaching workload will appear after activities are created."
        />
        <x-ui.dashboard-chart
            id="teacher-performance-chart"
            title="Subject performance"
            subtitle="Average normalized assessment scores for your subjects."
            :config="$performanceChart"
            empty-message="Performance data will appear after assessment scores are recorded."
        />
    </div>

    <x-ui.performance-chart-grid prefix="teacher" :overview="$performanceOverview" audience-label="Learner performance" />

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-bold text-slate-900">Recent submissions</h2>
            <div class="mt-4 space-y-3">
                @forelse ($recentSubmissions as $submission)
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3">
                        <div>
                            <p class="font-medium text-slate-900">{{ $submission->student->first_name }} {{ $submission->student->last_name }}</p>
                            <p class="text-xs text-slate-500">{{ $submission->assignment->title }}</p>
                        </div>
                        <span class="text-xs text-slate-500">{{ $submission->submitted_at?->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No submissions waiting for review.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-bold text-slate-900">Relevant announcements</h2>
                <a href="{{ route('lms.announcements.teacher.manage') }}" class="text-sm font-semibold text-blue-800 hover:text-blue-950">View all</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($announcements as $announcement)
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="font-medium text-slate-900">{{ $announcement->title }}</p>
                        <p class="text-xs text-slate-500">{{ $announcement->published_at?->diffForHumans() }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No recent announcements.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
