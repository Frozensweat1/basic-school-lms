@props(['report', 'showStudent' => false])

<article class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex min-w-0 items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-800"><svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 2.5h9l3 3v12H4v-15Z"></path><path d="M13 2.5v3h3M7 9h6M7 12h6M7 15h4"></path></svg></span>
            <div class="min-w-0">
                @if ($showStudent)<p class="truncate text-xs font-bold uppercase tracking-wide text-blue-700">{{ $report->student->first_name }} {{ $report->student->last_name }}</p>@endif
                <h3 class="mt-0.5 font-bold text-slate-900">{{ $report->academicYear->name }} &middot; {{ $report->term->name }}</h3>
                <p class="mt-1 text-sm text-slate-600">{{ $report->schoolClass->name }}</p>
            </div>
        </div>
        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Published</span>
    </div>
    <div class="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-3 text-sm"><div><p class="text-xs font-semibold text-slate-400">Attendance</p><p class="mt-1 font-bold text-slate-800">{{ $report->attendance_percentage === null ? 'Not recorded' : number_format((float) $report->attendance_percentage, 1).'%' }}</p></div><div><p class="text-xs font-semibold text-slate-400">Published</p><p class="mt-1 font-bold text-slate-800">{{ $report->published_at?->format('d M Y') ?: 'Date unavailable' }}</p></div></div>
    <p class="mt-4 line-clamp-2 min-h-10 text-sm leading-5 text-slate-600">{{ $report->teacher_comment ?: 'Open the report card to review academic performance and official comments.' }}</p>
    <div class="mt-4 flex justify-end"><a href="{{ route('lms.reports.show', $report) }}" wire:navigate class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-blue-900 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800">Review and print <span aria-hidden="true">&rarr;</span></a></div>
</article>
