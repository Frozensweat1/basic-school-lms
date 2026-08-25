<div class="space-y-6">
    @php
        $filtersActive = filled($search) || filled($filterType);
        $bankFiltersActive = filled($bankSearch) || filled($bankType);
        $quizIndexRoute = auth()->user()->hasRole('teacher') ? 'lms.quizzes.teacher.index' : 'lms.quizzes.admin.index';
    @endphp

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Quiz builder</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $quiz->title }}</h2>
            <p class="mt-1 text-sm text-slate-600">Add questions from the matching curriculum bank. Correct answers never appear in this builder.</p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('lms.questions.index') }}" class="inline-flex cursor-pointer items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">Question Bank</a>
            <a href="{{ route($quizIndexRoute) }}" class="inline-flex cursor-pointer items-center rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-200">Back to quizzes</a>
            <x-button wire:click="create" variant="primary" icon="plus" target="create" :loading="true">Add question</x-button>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Class subject</p>
            <p class="mt-2 font-semibold text-slate-900">{{ $quiz->classSubject->schoolClass->name }} &middot; {{ $quiz->classSubject->subject->name }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Question scope</p>
            <p class="mt-2 font-semibold text-slate-900">{{ $quiz->lesson?->title ?? $quiz->topic?->title ?? 'Whole subject' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $quiz->lesson ? 'Only questions tied to this lesson can be added.' : ($quiz->topic ? 'Only questions tied to this topic can be added.' : 'Questions from this subject are available.') }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Question total</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $quizQuestions->total() }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ \Illuminate\Support\Str::plural('question', $quizQuestions->total()) }} currently in this quiz</p>
        </div>
    </div>

    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div class="grid w-full gap-3 sm:grid-cols-2 lg:max-w-3xl lg:grid-cols-[minmax(18rem,1fr)_12rem]">
            <div class="relative">
                <label for="linked-question-search" class="sr-only">Search questions in this quiz</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input id="linked-question-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search linked questions, topics, or lessons" autocomplete="off" class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-20 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700">
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching&hellip;</span>
            </div>
            <select wire:model.live="filterType" aria-label="Filter linked questions by type" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All types</option>
                <option value="multiple_choice">Multiple choice</option>
                <option value="true_false">True / False</option>
                <option value="short_answer">Short answer</option>
                <option value="essay">Essay</option>
            </select>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            @if ($filtersActive)
                <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>
            @endif
            <p class="whitespace-nowrap text-sm text-slate-500" aria-live="polite">
                <span wire:loading.remove wire:target="search,filterType">{{ $quizQuestions->total() }} {{ \Illuminate\Support\Str::plural('question', $quizQuestions->total()) }}</span>
                <span wire:loading wire:target="search,filterType">Updating&hellip;</span>
            </p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Order</th>
                        <th class="px-5 py-3">Question</th>
                        <th class="px-5 py-3">Curriculum link</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Score</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($quizQuestions as $quizQuestion)
                        <tr wire:key="quiz-question-{{ $quizQuestion->id }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4 font-semibold text-slate-700">{{ $quizQuestion->sequence }}</td>
                            <td class="px-5 py-4"><p class="max-w-xl font-medium text-slate-900">{{ \Illuminate\Support\Str::limit(strip_tags($quizQuestion->question->prompt), 160) }}</p></td>
                            <td class="px-5 py-4 text-slate-700">
                                <p>{{ $quizQuestion->question->topic?->title ?? 'Subject-level question' }}</p>
                                @if ($quizQuestion->question->lesson)
                                    <p class="mt-1 text-xs text-slate-500">{{ $quizQuestion->question->lesson->title }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ str_replace('_', ' ', ucfirst($quizQuestion->question->type)) }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $quizQuestion->question->max_score }}</td>
                            <td class="px-5 py-4 text-right">
                                <x-ui.icon-button wire:click="confirmDelete({{ $quizQuestion->id }})" icon="trash" label="Remove this question" variant="danger" target="confirmDelete({{ $quizQuestion->id }})" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="font-medium text-slate-700">{{ $filtersActive ? 'No questions match the current search or filter.' : 'No questions have been added yet.' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $filtersActive ? 'Clear a filter or try another search term.' : 'Add a curriculum-matched Question Bank item to start building this quiz.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$quizQuestions" />

    <x-modal :show="$showFormModal" title="Add question to quiz" close-action="closeModals" max-width="xl">
        <form wire:submit="save" class="space-y-5">
            <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                <p class="font-semibold">Eligible Question Bank items</p>
                <p class="mt-1">{{ $quiz->lesson ? 'Only questions linked to this lesson are shown.' : ($quiz->topic ? 'Only questions linked to this topic are shown.' : 'Only questions linked to this subject are shown.') }}</p>
            </div>

            <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="grid w-full gap-3 sm:grid-cols-[minmax(14rem,1fr)_11rem]">
                    <div class="relative">
                        <label for="bank-question-search" class="sr-only">Search eligible Question Bank items</label>
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m20 20-3.5-3.5"></path>
                        </svg>
                        <input id="bank-question-search" type="search" wire:model.live.debounce.300ms="bankSearch" placeholder="Search eligible questions" autocomplete="off" class="w-full rounded-lg border-slate-300 py-2 pl-9 pr-16 text-sm focus:border-blue-700 focus:ring-blue-700">
                        <span wire:loading wire:target="bankSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-500">Searching&hellip;</span>
                    </div>
                    <select wire:model.live="bankType" aria-label="Filter Question Bank items by type" class="rounded-lg border-slate-300 text-sm focus:border-blue-700 focus:ring-blue-700">
                        <option value="">All types</option>
                        <option value="multiple_choice">Multiple choice</option>
                        <option value="true_false">True / False</option>
                        <option value="short_answer">Short answer</option>
                        <option value="essay">Essay</option>
                    </select>
                </div>
                @if ($bankFiltersActive)
                    <x-button wire:click="clearBankFilters" variant="ghost" size="sm" target="clearBankFilters" :loading="true">Clear</x-button>
                @endif
            </div>

            <div class="max-h-96 overflow-y-auto rounded-xl border border-slate-200 bg-white">
                <div wire:loading.flex wire:target="bankSearch,bankType" class="min-h-32 items-center justify-center gap-2 p-6 text-sm font-medium text-slate-500">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.34-5.66" stroke-linecap="round"/></svg>
                    Updating eligible questions...
                </div>
                <div wire:loading.remove wire:target="bankSearch,bankType" class="divide-y divide-slate-100">
                    @forelse ($availableQuestions as $question)
                        <label wire:key="available-question-{{ $question->id }}" class="flex cursor-pointer items-start gap-3 p-4 transition hover:bg-slate-50">
                            <input wire:model.live="questionId" type="radio" value="{{ $question->id }}" class="mt-1 border-slate-300 text-blue-700 focus:ring-blue-700">
                            <span class="min-w-0 flex-1">
                                <span class="block font-medium text-slate-900">{{ \Illuminate\Support\Str::limit(strip_tags($question->prompt), 180) }}</span>
                                <span class="mt-1 block text-xs text-slate-500">{{ str_replace('_', ' ', ucfirst($question->type)) }} &middot; {{ $question->max_score }} {{ \Illuminate\Support\Str::plural('point', $question->max_score) }} @if ($question->topic) &middot; {{ $question->topic->title }} @endif @if ($question->lesson) &middot; {{ $question->lesson->title }} @endif</span>
                            </span>
                        </label>
                    @empty
                        <div class="p-8 text-center">
                            <p class="font-medium text-slate-700">No eligible questions are available.</p>
                            <p class="mt-1 text-sm text-slate-500">Create a matching question in the Question Bank, or adjust the search and type filter.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <x-pagination :paginator="$availableQuestions" />
            @error('questionId')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror

            <div>
                <label for="sequence" class="block text-sm font-medium text-slate-700">Display order</label>
                <input wire:model.blur="sequence" id="sequence" type="number" min="0" class="mt-1 block w-full rounded-lg border-slate-300">
                <p class="mt-1 text-xs text-slate-500">The next available order is selected automatically. You can change it if needed.</p>
                @error('sequence')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-3">
                <x-button type="button" wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button type="submit" icon="save" target="save" :loading="true">Add selected question</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Remove question?" close-action="closeModals" max-width="md">
        <p class="text-sm text-slate-600">This removes the question from this quiz only. The Question Bank entry is retained for other quizzes.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Remove question</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
