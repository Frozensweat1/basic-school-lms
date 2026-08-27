<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Examination — {{ $examination->title }}</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Question bank</h2>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">
                {{ $examination->classSubject->schoolClass->name }} · {{ $examination->classSubject->subject->name }} ·
                {{ $examination->exam_date->format('d M Y') }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (Route::has($scoresRouteName))
                <a href="{{ route($scoresRouteName, $examination) }}" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 11l3 3L22 4" stroke-linecap="round" stroke-linejoin="round"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke-linecap="round"></path></svg>
                    Grade students
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
            <p class="text-sm font-medium text-blue-800">Questions set</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ $attachedItems->count() }}</p>
            <p class="mt-1 text-xs text-blue-700">Questions attached to this exam</p>
        </article>
        <article class="rounded-2xl border @if($marksMismatch) border-amber-200 bg-amber-50 @else border-emerald-100 bg-emerald-50 @endif p-5 shadow-sm">
            <p class="text-sm font-medium @if($marksMismatch) text-amber-800 @else text-emerald-800 @endif">Total marks</p>
            <p class="mt-2 text-3xl font-bold @if($marksMismatch) text-amber-900 @else text-emerald-900 @endif">{{ rtrim(rtrim((string) $totalAttachedMarks, '0'), '.') }}</p>
            <p class="mt-1 text-xs @if($marksMismatch) text-amber-700 @else text-emerald-700 @endif">
                @if($marksMismatch)
                    Exam max score is {{ rtrim(rtrim((string) $examination->max_score, '0'), '.') }} — adjust marks or exam max
                @else
                    Matches exam max score of {{ rtrim(rtrim((string) $examination->max_score, '0'), '.') }}
                @endif
            </p>
        </article>
        <article class="rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-violet-800">Question bank</p>
            <p class="mt-2 text-3xl font-bold text-violet-900">{{ $bankQuestions->total() }}</p>
            <p class="mt-1 text-xs text-violet-700">Questions matching current filter</p>
        </article>
    </div>

    {{-- Questions already on this exam --}}
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Questions on this examination</h3>
            <p class="mt-1 text-xs text-slate-500">These questions will be visible to students during the exam. Marks are used for teacher guidance when grading.</p>
        </div>

        @if($attachedItems->isEmpty())
            <div class="px-5 py-12 text-center text-sm text-slate-500">
                No questions added yet. Search the question bank below to start building the exam.
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($attachedItems as $examQuestion)
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
                    <div wire:key="exam-q-{{ $examQuestion->id }}" class="flex items-start gap-4 px-5 py-4 hover:bg-slate-50/60">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $typeBadge['class'] }}">{{ $typeBadge['label'] }}</span>
                                @if($q->subject)
                                    <span class="text-xs text-slate-500">{{ $q->subject->name }}{{ $q->topic ? ' · '.$q->topic->name : '' }}</span>
                                @endif
                                <span class="ml-auto text-xs font-semibold text-slate-700">{{ rtrim(rtrim((string) $examQuestion->marks, '0'), '.') }} mark{{ (float) $examQuestion->marks !== 1.0 ? 's' : '' }}</span>
                            </div>
                            <p class="mt-1.5 text-sm text-slate-900 [&>p]:inline">
                                {!! Str::limit(strip_tags($q->prompt), 200) !!}
                            </p>
                            @if($q->type === 'multiple_choice')
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($q->options as $option)
                                        <span class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-xs font-medium {{ $option->is_correct ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300' : 'bg-slate-100 text-slate-600' }}">
                                            @if($option->is_correct)
                                                <svg class="h-3 w-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 8 4 4 6-8" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                            @endif
                                            {{ $option->label }}
                                        </span>
                                    @endforeach
                                </div>
                            @elseif($q->type === 'true_false')
                                <p class="mt-1.5 text-xs text-slate-500">Correct: <span class="font-semibold">{{ ucfirst($q->grading_key['answer'] ?? '—') }}</span></p>
                            @elseif($q->type === 'short_answer')
                                <p class="mt-1.5 text-xs text-slate-500">Expected answer: <span class="font-semibold">{{ $q->grading_key['answer'] ?? '—' }}</span></p>
                            @endif
                        </div>
                        <x-ui.icon-button wire:click="confirmRemove({{ $examQuestion->id }})" icon="trash" label="Remove question" variant="danger" target="confirmRemove({{ $examQuestion->id }})" />
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Question bank browser --}}
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Question bank</h3>
            <p class="mt-1 text-xs text-slate-500">Search and filter to find questions. Click <strong>Add to exam</strong> to attach a question.</p>
        </div>

        <div class="grid gap-3 p-4 2xl:grid-cols-[minmax(0,1fr)_repeat(3,minmax(0,180px))]">
            <div class="relative">
                <label for="bank-search" class="sr-only">Search questions</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                <input id="bank-search" type="search" wire:model.live.debounce.300ms="bankSearch" placeholder="Search by prompt, subject, or topic" autocomplete="off" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-24 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700">
                <span wire:loading wire:target="bankSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching…</span>
            </div>
            <select wire:model.live="bankType" aria-label="Filter by question type" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All types</option>
                <option value="multiple_choice">Objectives (MCQ)</option>
                <option value="true_false">Boolean (True/False)</option>
                <option value="short_answer">Fill-in-the-blank</option>
                <option value="essay">Essay</option>
            </select>
            <select wire:model.live="bankSubjectId" aria-label="Filter by subject" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All subjects</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="bankTopicId" aria-label="Filter by topic" class="rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All topics</option>
                @foreach($topics as $topic)
                    <option value="{{ $topic->id }}">{{ $topic->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($bankQuestions as $question)
                @php
                    $alreadyAdded = in_array($question->id, $attachedQuestionIds, true);
                    $typeBadge = match($question->type) {
                        'multiple_choice' => ['label' => 'Objectives', 'class' => 'bg-blue-100 text-blue-800'],
                        'true_false' => ['label' => 'Boolean', 'class' => 'bg-violet-100 text-violet-800'],
                        'short_answer' => ['label' => 'Fill-in', 'class' => 'bg-amber-100 text-amber-800'],
                        'essay' => ['label' => 'Essay', 'class' => 'bg-emerald-100 text-emerald-800'],
                        default => ['label' => ucfirst($question->type), 'class' => 'bg-slate-100 text-slate-700'],
                    };
                @endphp
                <div wire:key="bank-q-{{ $question->id }}" class="flex items-start gap-4 px-5 py-4 @if($alreadyAdded) bg-slate-50/60 @else hover:bg-slate-50/60 @endif">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $typeBadge['class'] }}">{{ $typeBadge['label'] }}</span>
                            @if($question->subject)
                                <span class="text-xs text-slate-500">{{ $question->subject->name }}{{ $question->topic ? ' · '.$question->topic->name : '' }}</span>
                            @endif
                            <span class="ml-auto text-xs text-slate-500">{{ rtrim(rtrim((string) $question->max_score, '0'), '.') }} mark{{ (float) $question->max_score !== 1.0 ? 's' : '' }}</span>
                        </div>
                        <p class="mt-1.5 text-sm text-slate-900">
                            {!! Str::limit(strip_tags($question->prompt), 220) !!}
                        </p>
                        @if($question->type === 'multiple_choice')
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($question->options as $option)
                                    <span class="inline-flex rounded-lg px-2 py-0.5 text-xs font-medium {{ $option->is_correct ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $option->label }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="shrink-0">
                        @if($alreadyAdded)
                            <span class="inline-flex items-center gap-1 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 8 4 4 6-8" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                Added
                            </span>
                        @else
                            <x-button wire:click="openAdd({{ $question->id }})" variant="secondary" size="sm" icon="plus" target="openAdd({{ $question->id }})" :loading="true">Add to exam</x-button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-sm text-slate-500">
                    @if(filled($bankSearch) || filled($bankType) || filled($bankSubjectId))
                        No questions match the current filters.
                    @else
                        The question bank is empty. <a href="{{ route('lms.questions.index') }}" class="font-semibold text-blue-700 hover:underline">Add questions to the bank</a> first.
                    @endif
                </div>
            @endforelse
        </div>

        <div class="border-t border-slate-100 px-5 py-4">
            {{ $bankQuestions->links() }}
        </div>
    </section>

    {{-- Add question modal --}}
    <x-modal :show="$showAddModal" title="Add question to exam" close-action="closeModals" max-width="md">
        @if($addingQuestion)
            <div class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    {!! Str::limit(strip_tags($addingQuestion->prompt), 300) !!}
                </div>

                <div>
                    <label for="add-marks" class="block text-sm font-medium text-slate-700">Marks for this question</label>
                    <input wire:model="addMarks" id="add-marks" type="number" min="0.01" max="999" step="0.01" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-700 focus:ring-blue-700">
                    <p class="mt-1 text-xs text-slate-500">Default is the question's bank marks. Override here if the exam allocates a different weight.</p>
                    @error('addMarks') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="ghost" target="closeModals" :loading="true">Cancel</x-button>
                <x-button wire:click="addQuestion" variant="primary" icon="plus" target="addQuestion" :loading="true">Add to exam</x-button>
            </div>
        </x-slot:footer>
    </x-modal>

    {{-- Remove question confirm modal --}}
    <x-modal :show="$showRemoveModal" title="Remove question?" close-action="closeModals" max-width="md">
        <p class="text-sm leading-6 text-slate-600">This removes the question from the examination. It remains in the question bank and can be re-added at any time.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button wire:click="removeQuestion" variant="danger" icon="trash" target="removeQuestion" :loading="true">Remove question</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
