<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Academic records</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Report cards</h2>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">Generate, review, and publish term report cards. Students and parents only see published reports.</p>
        </div>
    </div>

    <div class="grid grid-cols-4 gap-4">
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm"><p class="text-sm font-medium text-blue-800">Report cards</p><p class="mt-2 text-3xl font-bold text-blue-900">{{ $metrics['reports'] }}</p><p class="mt-1 text-xs text-blue-700">Matching the current search and filters</p></article>
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm"><p class="text-sm font-medium text-emerald-800">Published</p><p class="mt-2 text-3xl font-bold text-emerald-900">{{ $metrics['published'] }}</p><p class="mt-1 text-xs text-emerald-700">{{ $metrics['draft'] }} still in draft review</p></article>
        <article class="rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm"><p class="text-sm font-medium text-violet-800">Average attendance</p><p class="mt-2 text-3xl font-bold text-violet-900">{{ number_format($metrics['attendance'], 1) }}%</p><p class="mt-1 text-xs text-violet-700">Across the matching report cards</p></article>
        <article class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-sm"><p class="text-sm font-medium text-amber-800">Average score</p><p class="mt-2 text-3xl font-bold text-amber-900">{{ number_format($metrics['averageScore'], 1) }}</p><p class="mt-1 text-xs text-amber-700">{{ $metrics['atRisk'] }} at-risk {{ Illuminate\Support\Str::plural('student', $metrics['atRisk']) }} below 50</p></article>
    </div>

    <section class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-950 to-blue-800 p-6 text-white shadow-sm">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between"><div><p class="text-xs font-bold uppercase tracking-[.2em] text-blue-200">Generation workspace</p><h3 class="mt-2 text-lg font-bold">Prepare term reports</h3><p class="mt-1 text-sm text-blue-100">Generate one student synchronously for immediate review, or queue an entire active class.</p></div><span class="text-xs font-semibold text-blue-200">Published subject results are required before publication.</span></div>
        <div class="mt-5 grid gap-4 lg:grid-cols-3">
            <div><label for="generation-term" class="block text-sm font-semibold text-blue-50">Academic term</label><select id="generation-term" wire:model.live="generationTermId" class="mt-1 block w-full rounded-xl border-blue-700 bg-white text-sm text-slate-900 shadow-sm focus:border-amber-400 focus:ring-amber-400"><option value="">Choose a term</option>@foreach ($terms as $term)<option value="{{ $term->id }}">{{ $term->academicYear->name }} - {{ $term->name }}</option>@endforeach</select>@error('generationTermId')<p class="mt-1 text-sm text-rose-200">{{ $message }}</p>@enderror</div>
            <div><label for="generation-class" class="block text-sm font-semibold text-blue-50">Class</label><select id="generation-class" wire:model.live="generationClassId" class="mt-1 block w-full rounded-xl border-blue-700 bg-white text-sm text-slate-900 shadow-sm focus:border-amber-400 focus:ring-amber-400"><option value="">{{ $generationTermId ? 'Choose a class' : 'Select a term first' }}</option>@foreach ($generationClasses as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select>@error('generationClassId')<p class="mt-1 text-sm text-rose-200">{{ $message }}</p>@enderror</div>
            <div><label for="generation-student" class="block text-sm font-semibold text-blue-50">Student <span class="font-normal text-blue-200">(single report)</span></label><select id="generation-student" wire:model.blur="generationStudentId" class="mt-1 block w-full rounded-xl border-blue-700 bg-white text-sm text-slate-900 shadow-sm focus:border-amber-400 focus:ring-amber-400"><option value="">{{ $generationClassId ? 'Choose an enrolled student' : 'Select a class first' }}</option>@foreach ($generationStudents as $student)<option value="{{ $student->id }}">{{ $student->last_name }}, {{ $student->first_name }} - {{ $student->admission_number }}</option>@endforeach</select>@error('generationStudentId')<p class="mt-1 text-sm text-rose-200">{{ $message }}</p>@enderror</div>
        </div>
        <div class="mt-5 flex flex-wrap justify-end gap-3 border-t border-blue-700 pt-5">
            <x-button wire:click="generateSingle" variant="success" icon="save" target="generateSingle" :loading="true">Generate single</x-button>
            <x-button wire:click="generateBulk" variant="secondary" icon="sparkles" target="generateBulk" :loading="true">Queue class reports</x-button>
            <x-button wire:click="confirmPublishBulk" variant="primary" target="confirmPublishBulk" :loading="true">Publish ready drafts</x-button>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_260px_220px_180px_auto] xl:items-center">
            <div class="relative"><label for="report-search" class="sr-only">Search report cards</label><svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg><input id="report-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search student, admission number, class, or term" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching...</span></div>
            <select wire:model.live="filterTermId" aria-label="Filter by term" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">All terms</option>@foreach ($terms as $term)<option value="{{ $term->id }}">{{ $term->academicYear->name }} - {{ $term->name }}</option>@endforeach</select>
            <select wire:model.live="filterClassId" aria-label="Filter by class" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">All classes</option>@foreach ($classes as $class)@if (! $filterTermId || $class->academic_year_id === $terms->firstWhere('id', (int) $filterTermId)?->academic_year_id)<option value="{{ $class->id }}">{{ $class->name }}</option>@endif @endforeach</select>
            <select wire:model.live="filterStatus" aria-label="Filter by status" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"><option value="">All statuses</option><option value="draft">Draft</option><option value="published">Published</option></select>
            @if (filled($search) || filled($filterTermId) || filled($filterClassId) || filled($filterStatus))<x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>@endif
        </div>
    </section>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Student</th><th class="px-5 py-3">Term and class</th><th class="px-5 py-3">Results</th><th class="px-5 py-3">Attendance</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Generated</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($reportCards as $reportCard)
                    @php($resultCount = $resultCounts->get($reportCard->id, 0))
                    <tr wire:key="report-card-{{ $reportCard->id }}" class="hover:bg-slate-50/80">
                        <td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $reportCard->student->first_name }} {{ $reportCard->student->last_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $reportCard->student->admission_number }}</p></td>
                        <td class="px-5 py-4"><p class="font-semibold text-slate-800">{{ $reportCard->term->name }} &middot; {{ $reportCard->academicYear->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $reportCard->schoolClass->name }}</p></td>
                        <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $resultCount ? 'bg-blue-100 text-blue-800' : 'bg-rose-100 text-rose-800' }}">{{ $resultCount ? $resultCount.' '.Illuminate\Support\Str::plural('subject', $resultCount) : 'No published results' }}</span></td>
                        <td class="px-5 py-4"><p class="font-semibold text-slate-800">{{ $reportCard->attendance_percentage === null ? 'Not recorded' : number_format((float) $reportCard->attendance_percentage, 1).'%' }}</p></td>
                        <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $reportCard->status === 'published' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ ucfirst($reportCard->status) }}</span></td>
                        <td class="px-5 py-4 text-slate-600">{{ $reportCard->generated_at?->format('d M Y, H:i') ?: 'Not recorded' }}</td>
                        <td class="px-5 py-4"><div class="flex justify-end gap-2"><a href="{{ route('lms.reports.show', $reportCard) }}" wire:navigate class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 10s2.5-5 7.5-5 7.5 5 7.5 5-2.5 5-7.5 5-7.5-5-7.5-5Z"></path><circle cx="10" cy="10" r="2"></circle></svg>Review</a>@if ($reportCard->status !== 'published')<x-button wire:click="confirmPublish({{ $reportCard->id }})" size="xs" variant="success" target="confirmPublish({{ $reportCard->id }})" :loading="true">Publish</x-button>@endif</div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-14 text-center text-slate-500">{{ filled($search) || filled($filterTermId) || filled($filterClassId) || filled($filterStatus) ? 'No report cards match the current search and filters.' : 'No report cards have been generated yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if ($reportCards->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $reportCards->links() }}</div>@endif
    </div>

    @if ($showPublishModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="publish-report-title">
            <button type="button" class="absolute inset-0 cursor-default" wire:click="closePublishModal" aria-label="Close publication confirmation"></button>
            <div class="relative z-10 w-full max-w-md rounded-2xl border border-white/20 bg-white p-6 shadow-2xl">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"></path></svg></div>
                <h3 id="publish-report-title" class="mt-4 text-lg font-bold text-slate-900">{{ $publishMode === 'bulk' ? 'Publish ready class reports?' : 'Publish this report card?' }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ $publishMode === 'bulk' ? 'Every ready draft in the selected term and class will become visible. Drafts without published results will be skipped.' : 'The report will become immediately available to the student and linked parents.' }}</p>
                @error('publish')<p class="mt-3 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $message }}</p>@enderror
                <div class="mt-6 flex justify-end gap-3"><x-button wire:click="closePublishModal" variant="secondary" target="closePublishModal" :loading="true">Cancel</x-button><x-button wire:click="publishConfirmed" variant="success" target="publishConfirmed" :loading="true">Publish {{ $publishMode === 'bulk' ? 'ready reports' : 'report' }}</x-button></div>
            </div>
        </div>
    @endif
</div>
