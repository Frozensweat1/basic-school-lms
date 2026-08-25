<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Quiz building</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Question Bank</h2>
            <p class="mt-1 text-sm text-slate-600">Create reusable questions and connect them to the right subject, topic, and lesson.</p>
        </div>

        <x-button wire:click="create" variant="primary" icon="plus" target="create" :loading="true">New question</x-button>
    </div>

    @php
        $filtersActive = filled($search) || filled($filterSubjectId) || filled($filterTopicId) || filled($filterLessonId) || filled($filterType);
    @endphp
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm xl:flex-row xl:items-center xl:justify-between">
        <div class="grid w-full gap-3 sm:grid-cols-2 xl:max-w-6xl xl:grid-cols-[minmax(16rem,1fr)_minmax(12rem,1fr)_minmax(12rem,1fr)_minmax(12rem,1fr)_10rem]">
            <div class="relative sm:col-span-2 xl:col-span-1">
                <label for="question-search" class="sr-only">Search questions</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input
                    id="question-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search question, subject, topic, or lesson"
                    autocomplete="off"
                    class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-20 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700"
                >
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching&hellip;</span>
            </div>

            <select wire:model.live="filterSubjectId" aria-label="Filter by subject" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All subjects</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterTopicId" aria-label="Filter by topic" @disabled(blank($filterSubjectId)) class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700 disabled:cursor-not-allowed disabled:bg-slate-100">
                <option value="">{{ filled($filterSubjectId) ? 'All topics' : 'Choose a subject first' }}</option>
                @foreach ($filterTopics as $topic)
                    <option value="{{ $topic->id }}">{{ $topic->title }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterLessonId" aria-label="Filter by lesson" @disabled(blank($filterTopicId)) class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700 disabled:cursor-not-allowed disabled:bg-slate-100">
                <option value="">{{ filled($filterTopicId) ? 'All lessons' : 'Choose a topic first' }}</option>
                @foreach ($filterLessons as $lesson)
                    <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterType" aria-label="Filter by question type" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
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
                <span wire:loading.remove wire:target="search,filterSubjectId,filterTopicId,filterLessonId,filterType">{{ $questions->total() }} {{ \Illuminate\Support\Str::plural('question', $questions->total()) }}</span>
                <span wire:loading wire:target="search,filterSubjectId,filterTopicId,filterLessonId,filterType">Updating&hellip;</span>
            </p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Question</th>
                        <th class="px-5 py-3">Curriculum link</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Score</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($questions as $question)
                        <tr wire:key="question-{{ $question->id }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <p class="max-w-xl font-medium text-slate-900">{{ \Illuminate\Support\Str::limit(strip_tags($question->prompt), 140) }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <p>{{ $question->subject?->name ?? 'Not classified' }}</p>
                                @if ($question->topic)
                                    <p class="mt-1 text-xs text-slate-500">{{ $question->topic->title }}</p>
                                @endif
                                @if ($question->lesson)
                                    <p class="mt-1 text-xs text-slate-500">{{ $question->lesson->title }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ str_replace('_', ' ', ucfirst($question->type)) }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $question->max_score }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @can('update', $question)
                                        <x-ui.icon-button wire:click="edit({{ $question->id }})" icon="edit" label="Edit question" target="edit({{ $question->id }})" />
                                    @endcan
                                    @can('delete', $question)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $question->id }})" icon="trash" label="Delete question" variant="danger" target="confirmDelete({{ $question->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <p class="font-medium text-slate-700">{{ $filtersActive ? 'No questions match the current search or filters.' : 'No questions yet.' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $filtersActive ? 'Clear a filter or try another search term.' : 'Create your first curriculum-linked question.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$questions" />

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit question' : 'New question'" close-action="closeModals" max-width="xl">
        <form wire:submit="save" class="space-y-5">
            <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                Build a curriculum-aligned Question Bank: every question belongs to a subject, topic, and lesson.
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="subjectId" class="block text-sm font-medium text-slate-700">Subject</label>
                    <select wire:model.live="subjectId" id="subjectId" class="mt-1 block w-full rounded-lg border-slate-300">
                        <option value="">Choose a subject</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                    @error('subjectId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="topicId" class="block text-sm font-medium text-slate-700">Topic</label>
                    <select wire:model.live="topicId" id="topicId" @disabled(blank($subjectId)) class="mt-1 block w-full rounded-lg border-slate-300 disabled:cursor-not-allowed disabled:bg-slate-100">
                        <option value="">{{ filled($subjectId) ? 'Choose a topic' : 'Choose a subject first' }}</option>
                        @foreach ($formTopics as $topic)
                            <option value="{{ $topic->id }}">{{ $topic->title }}</option>
                        @endforeach
                    </select>
                    @error('topicId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="lessonId" class="block text-sm font-medium text-slate-700">Lesson</label>
                    <select wire:model.live="lessonId" id="lessonId" @disabled(blank($topicId)) class="mt-1 block w-full rounded-lg border-slate-300 disabled:cursor-not-allowed disabled:bg-slate-100">
                        <option value="">{{ filled($topicId) ? 'Choose a lesson' : 'Choose a topic first' }}</option>
                        @foreach ($formLessons as $lesson)
                            <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                        @endforeach
                    </select>
                    @error('lessonId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="type" class="block text-sm font-medium text-slate-700">Question type</label>
                    <select wire:model.live="type" id="type" class="mt-1 block w-full rounded-lg border-slate-300">
                        <option value="multiple_choice">Multiple choice</option>
                        <option value="true_false">True / False</option>
                        <option value="short_answer">Short answer</option>
                        <option value="essay">Essay</option>
                    </select>
                    @error('type')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="maxScore" class="block text-sm font-medium text-slate-700">Maximum score</label>
                    <input wire:model.blur="maxScore" id="maxScore" type="number" min="0.01" step="0.01" class="mt-1 block w-full rounded-lg border-slate-300">
                    @error('maxScore')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="prompt" class="block text-sm font-medium text-slate-700">Prompt</label>
                <textarea wire:model.blur="prompt" id="prompt" rows="4" class="mt-1 block w-full rounded-lg border-slate-300" placeholder="Write the question learners should answer..."></textarea>
                @error('prompt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            @if (in_array($type, ['multiple_choice', 'true_false'], true))
                <div>
                    <label for="optionsText" class="block text-sm font-medium text-slate-700">Options @if ($type === 'multiple_choice')<span class="text-slate-400">(one per line)</span>@endif</label>
                    @if ($type === 'multiple_choice')
                        <textarea wire:model.blur="optionsText" id="optionsText" rows="4" class="mt-1 block w-full rounded-lg border-slate-300" placeholder="Option one&#10;Option two"></textarea>
                    @else
                        <p class="mt-1 text-sm text-slate-500">True and False options are added automatically.</p>
                    @endif
                    @error('optionsText')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            @endif

            @if ($type !== 'essay')
                <div>
                    <label for="correctAnswer" class="block text-sm font-medium text-slate-700">Correct answer</label>
                    <input wire:model.blur="correctAnswer" id="correctAnswer" class="mt-1 block w-full rounded-lg border-slate-300">
                    @error('correctAnswer')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            @endif

            <div class="flex justify-end gap-3">
                <x-button type="button" wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button type="submit" icon="save" target="save" :loading="true">Save question</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Delete question?" close-action="closeModals" max-width="md">
        <p class="text-sm text-slate-600">This question will be permanently deleted. Quizzes using it may also lose that question.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
