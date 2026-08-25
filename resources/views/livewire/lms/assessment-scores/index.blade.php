<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Assessment scoring</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $assessment->title }}</h2>
            <p class="mt-1 text-sm text-slate-600">Enter each enrolled student’s score out of {{ rtrim(rtrim((string) $assessment->max_score, '0'), '.') }}.</p>
        </div>

        <a href="{{ route($assessmentListRouteName) }}" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            Back to assessments
        </a>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-blue-800">Students</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ $allStudentCount }}</p>
            <p class="mt-1 text-xs text-blue-700">Active class enrolments</p>
        </article>
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-800">Scores entered</p>
            <p class="mt-2 text-3xl font-bold text-emerald-900">{{ $enteredScoreCount }} / {{ $allStudentCount }}</p>
            <p class="mt-1 text-xs text-emerald-700">Save updates this progress immediately</p>
        </article>
        <article class="rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-violet-800">Contribution</p>
            <p class="mt-2 text-3xl font-bold text-violet-900">{{ $assessment->component?->weight ?? 0 }}%</p>
            <p class="mt-1 text-xs text-violet-700">{{ $assessment->component?->name ?? 'No assessment component' }}</p>
        </article>
    </div>

    <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Class subject</p>
            <p class="mt-1 font-semibold text-slate-900">{{ $assessment->classSubject->schoolClass->name }} · {{ $assessment->classSubject->subject->name }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $assessment->term->academicYear->name }} · {{ $assessment->term->name }} · {{ $assessment->assessed_at->format('d M Y') }}</p>
        </div>
        <div class="lg:border-l lg:border-slate-200 lg:pl-5">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Result behaviour</p>
            @if ($assessment->status === 'published')
                <p class="mt-1 font-semibold text-emerald-800">Published — saving scores recalculates subject results.</p>
            @elseif ($assessment->status === 'locked')
                <p class="mt-1 font-semibold text-slate-700">Locked — scores are read-only until the assessment is unlocked.</p>
            @else
                <p class="mt-1 font-semibold text-amber-800">Draft — scores can be entered but are not used in results yet.</p>
            @endif
            @if ($assessment->teacher)
                <p class="mt-1 text-sm text-slate-600">Responsible teacher: {{ $assessment->teacher->first_name }} {{ $assessment->teacher->last_name }}</p>
            @endif
        </div>
    </section>

    @if ($scales->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            No grading scales are configured yet. Scores can still be saved, but grades will not be shown until grading scales are added.
            @can('viewAny', App\Models\GradingScale::class)
                <a href="{{ route('lms.grading-scales.index') }}" class="ml-1 font-semibold underline hover:text-amber-950">Configure grading scales</a>.
            @endcan
        </div>
    @endif

    <form wire:submit="save" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-xl">
                <label for="assessment-score-search" class="sr-only">Search students</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input id="assessment-score-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search by student name, ID, or admission number" autocomplete="off" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700">
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>
            </div>
            <p class="shrink-0 text-sm text-slate-500" aria-live="polite">{{ $students->count() }} of {{ $allStudentCount }} students</p>
        </div>

        @error('locked')
            <p class="m-4 rounded-xl bg-rose-50 p-3 text-sm text-rose-700">{{ $message }}</p>
        @enderror

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Student</th>
                        <th class="px-5 py-3">Score</th>
                        <th class="px-5 py-3">Percentage</th>
                        <th class="px-5 py-3">Grade</th>
                        <th class="px-5 py-3">Comment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($students as $enrollment)
                        @php
                            $studentId = $enrollment->student_id;
                            $score = $scores[$studentId] ?? '';
                            $percentage = $score === '' || $score === null
                                ? null
                                : ((float) $score / (float) $assessment->max_score) * 100;
                            $grade = $this->gradeFor($score, $scales);
                        @endphp
                        <tr wire:key="assessment-student-{{ $studentId }}" class="hover:bg-slate-50/80">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $enrollment->student->student_id }} · {{ $enrollment->student->admission_number }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <label for="score-{{ $studentId }}" class="sr-only">Score for {{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</label>
                                <input wire:model.blur="scores.{{ $studentId }}" id="score-{{ $studentId }}" type="number" min="0" max="{{ $assessment->max_score }}" step="0.01" @disabled($assessment->status === 'locked') class="w-28 rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700 disabled:cursor-not-allowed disabled:bg-slate-100">
                                @error("scores.$studentId") <p class="mt-1 max-w-36 text-xs text-rose-700">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-5 py-4 font-medium text-slate-700">{{ $percentage === null ? '—' : number_format($percentage, 1).'%' }}</td>
                            <td class="px-5 py-4"><span class="inline-flex min-w-9 justify-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $grade ?: '—' }}</span></td>
                            <td class="px-5 py-4">
                                <label for="comment-{{ $studentId }}" class="sr-only">Comment for {{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</label>
                                <input wire:model.blur="comments.{{ $studentId }}" id="comment-{{ $studentId }}" type="text" maxlength="1000" placeholder="Optional feedback" @disabled($assessment->status === 'locked') class="w-full min-w-56 rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700 disabled:cursor-not-allowed disabled:bg-slate-100">
                                @error("comments.$studentId") <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center text-slate-500">
                                @if (filled($search))
                                    No enrolled students match “{{ $search }}”.
                                @else
                                    There are no active enrolments in this class.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-200 px-5 py-4">
            <a href="{{ route($assessmentListRouteName) }}" class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50">Cancel</a>
            @if ($assessment->status !== 'locked')
                <x-button type="submit" variant="primary" icon="save" target="save" :loading="true">Save scores</x-button>
            @endif
        </div>
    </form>
</div>
