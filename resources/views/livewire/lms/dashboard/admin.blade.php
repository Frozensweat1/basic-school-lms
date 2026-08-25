<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">School overview</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">Administration dashboard</h2>
        <p class="mt-1 text-sm text-slate-600">Enrollment, attendance, academic activity, and communication at a glance.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($metrics as $label => $value)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $value }}</p>
            </article>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-ui.dashboard-chart
            id="admin-enrollment-chart"
            title="Enrollment by class"
            subtitle="Active students currently enrolled in each class."
            :config="$enrollmentChart"
            empty-message="Class enrollment data will appear here after students are enrolled."
        />
        <x-ui.dashboard-chart
            id="admin-attendance-chart"
            title="Attendance distribution"
            subtitle="Overall attendance status across recorded school days."
            :config="$attendanceChart"
            empty-message="Attendance data will appear here after registers are recorded."
        />
    </div>

    <x-ui.performance-chart-grid prefix="admin" :overview="$performanceOverview" audience-label="School performance" />

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-bold text-slate-900">Attendance totals</h2>
            <div class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                @foreach (['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'excused' => 'Excused'] as $key => $label)
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-slate-500">{{ $label }}</p>
                        <p class="mt-1 text-xl font-bold text-slate-900">{{ $attendanceSummary->get($key, 0) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-bold text-slate-900">Recent announcements</h2>
                <a href="{{ route('lms.announcements.admin.manage') }}" class="text-sm font-semibold text-blue-800 hover:text-blue-950">Manage</a>
            </div>
            <div class="mt-4 space-y-3">
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
</div>
