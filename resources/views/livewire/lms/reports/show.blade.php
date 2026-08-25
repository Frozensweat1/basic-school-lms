<div class="report-page mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between print:hidden">
        <a href="{{ $backRoute }}" wire:navigate class="inline-flex w-fit cursor-pointer items-center gap-2 text-sm font-semibold text-blue-800 hover:text-blue-950"><span aria-hidden="true">&larr;</span> Back to report cards</a>
        <div class="flex items-center gap-3"><span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $reportCard->status === 'published' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ ucfirst($reportCard->status) }}</span><button type="button" onclick="window.print()" class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 7V3h10v4M5 14H3V8h14v6h-2M5 11h10v6H5v-6Z"></path></svg>Print report</button></div>
    </div>

    @can('update', $reportCard)
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm print:hidden">
            <div><h3 class="font-bold text-slate-900">Review comments</h3><p class="mt-1 text-sm text-slate-500">These comments appear on the printable report card.</p></div>
            <form wire:submit="saveComments" class="mt-4 space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div><label for="teacher-comment" class="block text-sm font-semibold text-slate-700">Class teacher comment</label><textarea id="teacher-comment" wire:model.blur="teacherComment" rows="4" maxlength="2000" placeholder="Comment on the student's progress..." class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"></textarea>@error('teacherComment')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div><label for="headteacher-comment" class="block text-sm font-semibold text-slate-700">Headteacher comment</label><textarea id="headteacher-comment" wire:model.blur="headteacherComment" rows="4" maxlength="2000" placeholder="Add the headteacher's final comment..." class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"></textarea>@error('headteacherComment')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                </div>
                <div class="flex justify-end"><x-button type="submit" icon="save" target="saveComments" :loading="true">Save comments</x-button></div>
            </form>
        </section>
    @endcan

    <article class="report-sheet overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl print:rounded-none print:border-0 print:shadow-none">
        <header class="border-b-4 border-blue-900 bg-slate-50 px-8 py-7">
            <div class="flex items-start justify-between gap-6">
                <div class="flex items-center gap-4">
                    @if ($branding['logo_url'])
                        <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['name'] }} logo" class="h-16 w-16 rounded-xl object-contain">
                    @else
                        <span class="flex h-16 w-16 items-center justify-center rounded-xl bg-blue-900 text-xl font-black text-white">{{ $branding['initials'] }}</span>
                    @endif
                    <div><p class="text-xl font-black uppercase tracking-wide text-slate-950">{{ $branding['name'] }}</p><p class="mt-1 text-sm italic text-slate-600">{{ $branding['motto'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $branding['address'] }}@if ($branding['phone']) &middot; {{ $branding['phone'] }}@endif</p></div>
                </div>
                <div class="text-right"><p class="text-xs font-bold uppercase tracking-[.2em] text-blue-800">Student report card</p><p class="mt-2 text-lg font-black text-slate-900">{{ $reportCard->term->name }}</p><p class="text-sm text-slate-500">{{ $reportCard->academicYear->name }}</p></div>
            </div>
        </header>

        <div class="space-y-7 px-8 py-7">
            <section class="grid gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Student</p><p class="mt-1 font-bold text-slate-900">{{ $reportCard->student->first_name }} {{ $reportCard->student->last_name }}</p></div>
                <div class="bg-white p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Admission number</p><p class="mt-1 font-bold text-slate-900">{{ $reportCard->student->admission_number }}</p></div>
                <div class="bg-white p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Class</p><p class="mt-1 font-bold text-slate-900">{{ $reportCard->schoolClass->name }}</p></div>
                <div class="bg-white p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Attendance</p><p class="mt-1 font-bold text-slate-900">{{ $reportCard->attendance_percentage === null ? 'Not recorded' : number_format((float) $reportCard->attendance_percentage, 1).'%' }}</p></div>
            </section>

            <section class="grid grid-cols-4 gap-3">
                <div class="rounded-xl bg-blue-50 p-4 text-center"><p class="text-xs font-semibold text-blue-700">Average</p><p class="mt-1 text-2xl font-black text-blue-950">{{ $metrics['average'] === null ? '-' : number_format($metrics['average'], 1) }}</p></div>
                <div class="rounded-xl bg-emerald-50 p-4 text-center"><p class="text-xs font-semibold text-emerald-700">Highest</p><p class="mt-1 text-2xl font-black text-emerald-950">{{ $metrics['highest'] === null ? '-' : number_format($metrics['highest'], 1) }}</p></div>
                <div class="rounded-xl bg-amber-50 p-4 text-center"><p class="text-xs font-semibold text-amber-700">Lowest</p><p class="mt-1 text-2xl font-black text-amber-950">{{ $metrics['lowest'] === null ? '-' : number_format($metrics['lowest'], 1) }}</p></div>
                <div class="rounded-xl bg-violet-50 p-4 text-center"><p class="text-xs font-semibold text-violet-700">Subjects passed</p><p class="mt-1 text-2xl font-black text-violet-950">{{ $metrics['passed'] }}/{{ $results->count() }}</p></div>
            </section>

            <section>
                <h3 class="mb-3 text-sm font-black uppercase tracking-[.16em] text-slate-800">Academic performance</h3>
                <div class="overflow-hidden rounded-xl border border-slate-200"><table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-blue-950 text-left text-xs font-bold uppercase tracking-wide text-white"><tr><th class="px-4 py-3">Subject</th><th class="px-4 py-3 text-center">Score</th><th class="px-4 py-3 text-center">Grade</th><th class="px-4 py-3">Remark</th><th class="px-4 py-3">Teacher comment</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($results as $result)
                            <tr class="odd:bg-white even:bg-slate-50"><td class="px-4 py-3 font-bold text-slate-900">{{ $result->classSubject->subject->name }}</td><td class="px-4 py-3 text-center font-semibold text-slate-800">{{ $result->total_score === null ? '-' : number_format((float) $result->total_score, 1) }}</td><td class="px-4 py-3 text-center"><span class="inline-flex min-w-8 justify-center rounded-full bg-blue-100 px-2 py-1 text-xs font-black text-blue-900">{{ $result->grade ?: '-' }}</span></td><td class="px-4 py-3 text-slate-600">{{ $result->gradingScale?->remark ?: '-' }}</td><td class="px-4 py-3 text-slate-600">{{ $result->teacher_comment ?: '-' }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No published subject results are available for this report.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </section>

            <section class="grid gap-4 sm:grid-cols-2">
                <div class="min-h-28 rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-wide text-slate-500">Class teacher comment</p><p class="mt-3 text-sm leading-6 text-slate-700">{{ $reportCard->teacher_comment ?: 'No class teacher comment has been provided.' }}</p></div>
                <div class="min-h-28 rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-wide text-slate-500">Headteacher comment</p><p class="mt-3 text-sm leading-6 text-slate-700">{{ $reportCard->headteacher_comment ?: 'No headteacher comment has been provided.' }}</p></div>
            </section>

            <footer class="grid grid-cols-2 gap-16 pt-8 text-center text-xs text-slate-500"><div class="border-t border-slate-500 pt-2">Class teacher signature</div><div class="border-t border-slate-500 pt-2">Headteacher signature and stamp</div></footer>
            <p class="text-center text-[10px] text-slate-400">Report #{{ str_pad((string) $reportCard->id, 6, '0', STR_PAD_LEFT) }} &middot; Generated {{ $reportCard->generated_at?->format('d M Y, H:i') ?: 'date unavailable' }}@if ($reportCard->published_at) &middot; Published {{ $reportCard->published_at->format('d M Y, H:i') }}@endif</p>
        </div>
    </article>

    <style>
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            html, body { height: auto !important; overflow: visible !important; background: white !important; }
            #lms-sidebar, #sidebar-backdrop, #lms-content-shell > header, #lms-content-shell > footer { display: none !important; }
            #lms-content-shell { height: auto !important; overflow: visible !important; padding-left: 0 !important; }
            #lms-content-shell main { overflow: visible !important; padding: 0 !important; }
            .report-page { max-width: none !important; }
            .report-sheet { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</div>
