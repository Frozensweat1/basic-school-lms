<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Academic records</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">My report cards</h2>
    </div>

    <div class="grid gap-4">
        @forelse($reports as $report)
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $report->academicYear->name }} · {{ $report->term->name }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $report->schoolClass->name }} · Attendance {{ $report->attendance_percentage }}%</p>
                    </div>
                    <a href="{{ route('lms.reports.show', $report) }}" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700">Review and print</a>
                </div>
                <p class="mt-3 text-sm text-slate-700">{{ $report->teacher_comment ?: 'No teacher comment.' }}</p>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">No published report cards yet.</div>
        @endforelse
    </div>
<x-pagination :paginator="$reports" /></div>
