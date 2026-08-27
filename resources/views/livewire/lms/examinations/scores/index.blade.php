<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Examination grading</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $examination->title }}</h2>
            <p class="mt-1 text-sm text-slate-600">Enter each enrolled student's score out of {{ rtrim(rtrim((string) $examination->max_score, '0'), '.') }}.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (Route::has($questionsRouteName))
                <a href="{{ route($questionsRouteName, $examination) }}" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 5h9M9 9h9M9 13h5M5 5h.01M5 9h.01M5 13h.01"></path><path d="M4 3h16v18H4z" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    Exam questions
                </a>
            @endif
            <a href="{{ route($listRouteName) }}" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
                Back to examinations
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-blue-800">Students</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ $allStudentCount }}</p>
            <p class="mt-1 text-xs text-blue-700">Active class enrolments</p>
        </article>
        <article class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-800">Graded</p>
            <p class="mt-2 text-3xl font-bold text-emerald-900">{{ $gradedCount }} / {{ $allStudentCount }}</p>
            <p class="mt-1 text-xs text-emerald-700">Students with a score on record</p>
        </article>
        <article class="rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-violet-800">Questions set</p>
            <p class="mt-2 text-3xl font-bold text-violet-900">{{ $questionItems->count() }}</p>
            <p class="mt-1 text-xs text-violet-700">
                @if($questionItems->isEmpty())
                    No questions attached yet
                @else
                    Total: {{ rtrim(rtrim((string) $questionItems->sum('marks'), '0'), '.') }} marks
                @endif
            </p>
        </article>
    </div>

    {{-- Examination context --}}
    <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Class subject</p>
            <p class="mt-1 font-semibold text-slate-900">{{ $examination->classSubject->schoolClass->name }} · {{ $examination->classSubject->subject->name }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $examination->term->name }} · {{ $examination->exam_date->format('d M Y') }}
                {{ $examination->duration_minutes ? '· '.$examination->duration_minutes.' min' : '' }}</p>
        </div>
        <div class="lg:border-l lg:border-slate-200 lg:pl-5">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Status</p>
            @php $statusMap = ['scheduled' => ['class' => 'text-blue-800', 'label' => 'Scheduled'], 'completed' => ['class' => 'text-emerald-800', 'label' => 'Completed'], 'cancelled' => ['class' => 'text-rose-700', 'label' => 'Cancelled'], 'draft' => ['class' => 'text-amber-700', 'label' => 'Draft']]; @endphp
            <p class="mt-1 font-semibold {{ ($statusMap[$examination->status] ?? ['class' => 'text-slate-700'])['class'] }}">
                {{ ($statusMap[$examination->status] ?? ['label' => ucfirst($examination->status)])['label'] }}
            </p>
            @if($examination->teacher)
                <p class="mt-1 text-sm text-slate-600">Teacher: {{ $examination->teacher->first_name }} {{ $examination->teacher->last_name }}</p>
            @endif
        </div>
    </section>

    @if($questionItems->isNotEmpty())
        {{-- Question reference for graders --}}
        <details class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <summary class="cursor-pointer select-none px-5 py-4 text-sm font-semibold text-slate-800 hover:bg-slate-50/60">
                <span>Question breakdown ({{ $questionItems->count() }} questions, {{ rtrim(rtrim((string) $questionItems->sum('marks'), '0'), '.') }} total marks)</span>
            </summary>
            <div class="divide-y divide-slate-100 border-t border-slate-100">
                @foreach($questionItems as $examQuestion)
                    @php
                        $q = $examQuestion->question;
                        $typeBadge = match($q->type) {
                            'multiple_choice' => ['label' => 'Objectives', 'class' => 'bg-blue-100 text-blue-800'],
                            'true_false' => ['label' => 'Boolean', 'class' => 'bg-violet-100 text-violet-800'],
                            'short_answer' => ['label' => 'Fill-in', 'class' => 'bg-amber-100 text-amber-800'],
                            'essay' => ['label' => 'Essay', 'class' => 'bg-emerald-100 text-emerald-800'],
                            default => ['label' => ucfirst($q->type), 'class' => 'bg-slate-100 text-slate-700'],
                        };
                    @endphp
                    <div class="flex items-start gap-3 px-5 py-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $typeBadge['class'] }}">{{ $typeBadge['label'] }}</span>
                                <span class="text-xs font-semibold text-slate-600">{{ rtrim(rtrim((string) $examQuestion->marks, '0'), '.') }} mark{{ (float) $examQuestion->marks !== 1.0 ? 's' : '' }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-800">{!! Str::limit(strip_tags($q->prompt), 180) !!}</p>
                            @if($q->type !== 'essay')
                                <p class="mt-0.5 text-xs text-slate-500">Answer: <span class="font-medium">{{ $q->grading_key['answer'] ?? '—' }}</span></p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </details>
    @endif

    @if($scales->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            No grading scales configured. Scores can still be entered, but grades will not appear until grading scales are added.
            @can('viewAny', App\Models\GradingScale::class)
                <a href="{{ route('lms.grading-scales.index') }}" class="ml-1 font-semibold underline hover:text-amber-950">Configure grading scales</a>.
            @endcan
        </div>
    @endif

    {{-- Grading table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-xl">
                <label for="exam-score-search" class="sr-only">Search students</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                <input id="exam-score-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search by student name, ID, or admission number" autocomplete="off" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700">
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <p class="text-sm text-slate-500" aria-live="polite">{{ $students->count() }} of {{ $allStudentCount }} students</p>
                <x-button wire:click="bulkGrade" variant="primary" icon="check" target="bulkGrade" :loading="true">Bulk grade all</x-button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Student</th>
                        <th class="px-5 py-3">Score <span class="font-normal normal-case text-slate-400">/ {{ rtrim(rtrim((string) $examination->max_score, '0'), '.') }}</span></th>
                        <th class="px-5 py-3">Percentage</th>
                        <th class="px-5 py-3">Grade</th>
                        <th class="px-5 py-3">Comment</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($students as $enrollment)
                        @php
                            $studentId = $enrollment->student_id;
                            $score = $scores[$studentId] ?? '';
                            $percentage = ($score !== '' && $score !== null && (float) $examination->max_score > 0)
                                ? ((float) $score / (float) $examination->max_score) * 100
                                : null;
                            $grade = $this->gradeFor($score, $scales);
                            $isGraded = in_array((int) $studentId, $gradedStudentIds, true);
                        @endphp
                        <tr wire:key="exam-student-{{ $studentId }}" class="hover:bg-slate-50/80 @if($isGraded) bg-emerald-50/30 @endif">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    @if($isGraded)
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100" title="Graded">
                                            <svg class="h-3 w-3 text-emerald-700" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m2 6 3 3 5-5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        </span>
                                    @endif
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $enrollment->student->student_id }} · {{ $enrollment->student->admission_number }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <label for="exam-score-{{ $studentId }}" class="sr-only">Score for {{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</label>
                                <input wire:model.blur="scores.{{ $studentId }}" id="exam-score-{{ $studentId }}" type="number" min="0" max="{{ $examination->max_score }}" step="0.01" class="w-28 rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700 @if($isGraded) border-emerald-300 @endif">
                                @error("scores.$studentId") <p class="mt-1 max-w-36 text-xs text-rose-700">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-5 py-4 font-medium text-slate-700">{{ $percentage === null ? '—' : number_format($percentage, 1).'%' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex min-w-9 justify-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 @if($grade) bg-blue-100 text-blue-800 @endif">{{ $grade ?: '—' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <label for="exam-comment-{{ $studentId }}" class="sr-only">Comment for {{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</label>
                                <input wire:model.blur="comments.{{ $studentId }}" id="exam-comment-{{ $studentId }}" type="text" maxlength="1000" placeholder="Optional feedback" class="w-full min-w-48 rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                                @error("comments.$studentId") <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if($isGraded)
                                    <x-button wire:click="saveStudentScore({{ $studentId }})" variant="secondary" size="sm" icon="edit" target="saveStudentScore({{ $studentId }})" :loading="true">Change grade</x-button>
                                @else
                                    <x-button wire:click="saveStudentScore({{ $studentId }})" variant="primary" size="sm" icon="check" target="saveStudentScore({{ $studentId }})" :loading="true">Grade</x-button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center text-slate-500">
                                @if(filled($search))
                                    No enrolled students match "{{ $search }}".
                                @else
                                    There are no active enrolments in this class.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between border-t border-slate-200 px-5 py-4">
            <p class="text-xs text-slate-500">{{ $gradedCount }} of {{ $allStudentCount }} students graded</p>
            <x-button wire:click="bulkGrade" variant="primary" icon="check" target="bulkGrade" :loading="true">Bulk grade all</x-button>
        </div>
    </div>
</div>
