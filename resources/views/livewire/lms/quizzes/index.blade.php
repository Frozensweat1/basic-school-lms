<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Assessment</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Quizzes</h2>
            <p class="mt-1 text-sm text-slate-600">Configure quizzes, build their questions, and publish them to learners.</p>
        </div>

        @can('create', App\Models\Quiz::class)
            <x-button wire:click="create" variant="primary" icon="plus" target="create" :loading="true">New quiz</x-button>
        @endcan
    </div>

    @php
        $filtersActive = filled($search) || filled($filterClassSubjectId) || filled($filterStatus) || filled($filterSchedule);
        $questionRoute = auth()->user()->hasRole('teacher') ? 'lms.quizzes.teacher.questions.index' : 'lms.quizzes.admin.questions.index';
    @endphp
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm xl:flex-row xl:items-center xl:justify-between">
        <div class="grid w-full gap-3 sm:grid-cols-2 xl:max-w-6xl xl:grid-cols-[minmax(17rem,1fr)_minmax(13rem,1fr)_9rem_9rem]">
            <div class="relative sm:col-span-2 xl:col-span-1">
                <label for="quiz-search" class="sr-only">Search quizzes</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input
                    id="quiz-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search title, topic, class, or teacher"
                    autocomplete="off"
                    class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-20 text-sm shadow-sm transition focus:border-blue-700 focus:ring-blue-700"
                >
                <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500">Searching&hellip;</span>
            </div>

            <select wire:model.live="filterClassSubjectId" aria-label="Filter by class subject" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All class subjects</option>
                @foreach ($classSubjects as $classSubject)
                    <option value="{{ $classSubject->id }}">{{ $classSubject->schoolClass->name }} &middot; {{ $classSubject->subject->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterStatus" aria-label="Filter by quiz status" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="closed">Closed</option>
            </select>

            <select wire:model.live="filterSchedule" aria-label="Filter by quiz schedule" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                <option value="">All schedules</option>
                <option value="upcoming">Opens later</option>
                <option value="open">Open now</option>
                <option value="closed">Closed</option>
            </select>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            @if ($filtersActive)
                <x-button wire:click="clearFilters" variant="ghost" size="sm" target="clearFilters" :loading="true">Clear filters</x-button>
            @endif
            <p class="whitespace-nowrap text-sm text-slate-500" aria-live="polite">
                <span wire:loading.remove wire:target="search,filterClassSubjectId,filterStatus,filterSchedule">{{ $quizzes->total() }} {{ \Illuminate\Support\Str::plural('quiz', $quizzes->total()) }}</span>
                <span wire:loading wire:target="search,filterClassSubjectId,filterStatus,filterSchedule">Updating&hellip;</span>
            </p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Quiz</th>
                        <th class="px-5 py-3">Class subject</th>
                        <th class="px-5 py-3">Questions</th>
                        <th class="px-5 py-3">Schedule</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($quizzes as $quiz)
                        <tr wire:key="quiz-{{ $quiz->id }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-900">{{ $quiz->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' minutes' : 'No time limit' }} &middot; {{ $quiz->max_attempts }} {{ \Illuminate\Support\Str::plural('attempt', $quiz->max_attempts) }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                {{ $quiz->classSubject->schoolClass->name }} &middot; {{ $quiz->classSubject->subject->name }}
                                @if ($quiz->topic)
                                    <span class="block text-xs text-slate-500">{{ $quiz->topic->title }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <p>{{ $quiz->quiz_questions_count }} {{ \Illuminate\Support\Str::plural('question', $quiz->quiz_questions_count) }}</p>
                                <a href="{{ route($questionRoute, $quiz) }}" class="mt-1 inline-flex cursor-pointer text-xs font-semibold text-blue-700 hover:text-blue-900 hover:underline">Manage questions</a>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                @if ($quiz->opens_at?->isFuture())
                                    <p>Opens {{ $quiz->opens_at->format('d M Y, H:i') }}</p>
                                @elseif ($quiz->closes_at?->isPast())
                                    <p class="text-rose-700">Closed {{ $quiz->closes_at->format('d M Y, H:i') }}</p>
                                @elseif ($quiz->closes_at)
                                    <p>Closes {{ $quiz->closes_at->format('d M Y, H:i') }}</p>
                                @else
                                    <p class="text-emerald-700">Available now</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $quiz->status === 'published' ? 'bg-emerald-100 text-emerald-700' : ($quiz->status === 'closed' ? 'bg-slate-100 text-slate-600' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($quiz->status) }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route($questionRoute, $quiz) }}" class="inline-flex cursor-pointer items-center rounded-xl bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-200" title="Add or manage quiz questions">Questions</a>
                                    @if (auth()->user()->hasRole('teacher'))
                                        <a href="{{ route('lms.quizzes.teacher.grade', $quiz) }}" class="inline-flex cursor-pointer items-center rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-200" title="Grade quiz attempts">Grade</a>
                                    @endif
                                    @can('update', $quiz)
                                        <x-ui.icon-button wire:click="edit({{ $quiz->id }})" icon="edit" label="Edit {{ $quiz->title }}" target="edit({{ $quiz->id }})" />
                                    @endcan
                                    @can('delete', $quiz)
                                        <x-ui.icon-button wire:click="confirmDelete({{ $quiz->id }})" icon="trash" label="Archive {{ $quiz->title }}" variant="danger" target="confirmDelete({{ $quiz->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="font-medium text-slate-700">{{ $filtersActive ? 'No quizzes match the current search or filters.' : 'No quizzes yet.' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $filtersActive ? 'Clear a filter or try another search term.' : 'Create your first quiz, then add its questions from the list.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-pagination :paginator="$quizzes" />

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit quiz' : 'New quiz'" close-action="closeModals" max-width="xl">
        <form wire:submit="save" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="classSubjectId" class="block text-sm font-medium text-slate-700">Class subject</label>
                    <select wire:model.blur="classSubjectId" id="classSubjectId" class="mt-1 block w-full rounded-lg border-slate-300">
                        <option value="">Choose a class subject</option>
                        @foreach ($classSubjects as $classSubject)
                            <option value="{{ $classSubject->id }}">{{ $classSubject->schoolClass->name }} &middot; {{ $classSubject->subject->name }}</option>
                        @endforeach
                    </select>
                    @error('classSubjectId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="topicId" class="block text-sm font-medium text-slate-700">Topic <span class="text-slate-400">(optional)</span></label>
                    <select wire:model.blur="topicId" id="topicId" class="mt-1 block w-full rounded-lg border-slate-300">
                        <option value="">No topic</option>
                        @foreach ($topics as $topic)
                            <option value="{{ $topic->id }}">{{ $topic->title }}</option>
                        @endforeach
                    </select>
                    @error('topicId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="lessonId" class="block text-sm font-medium text-slate-700">Lesson <span class="text-slate-400">(optional)</span></label>
                    <select wire:model.blur="lessonId" id="lessonId" class="mt-1 block w-full rounded-lg border-slate-300">
                        <option value="">No lesson</option>
                        @foreach ($lessons as $lesson)
                            <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                        @endforeach
                    </select>
                    @error('lessonId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                @unless (auth()->user()->hasRole('teacher'))
                    <div>
                        <label for="teacherId" class="block text-sm font-medium text-slate-700">Teacher</label>
                        <select wire:model.blur="teacherId" id="teacherId" class="mt-1 block w-full rounded-lg border-slate-300">
                            <option value="">Use class subject teacher</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->first_name }} {{ $teacher->last_name }}</option>
                            @endforeach
                        </select>
                        @error('teacherId')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                @endunless
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="title" class="block text-sm font-medium text-slate-700">Quiz title</label>
                    <input wire:model.blur="title" id="title" class="mt-1 block w-full rounded-lg border-slate-300">
                    @error('title')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="maxAttempts" class="block text-sm font-medium text-slate-700">Maximum attempts</label>
                    <input wire:model.blur="maxAttempts" id="maxAttempts" type="number" min="1" class="mt-1 block w-full rounded-lg border-slate-300">
                    @error('maxAttempts')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="instructions" class="block text-sm font-medium text-slate-700">Instructions <span class="text-slate-400">(optional)</span></label>
                <x-ui.rich-text-editor wire:model="instructions" id="instructions" class="mt-1" placeholder="Explain quiz rules and instructions..." />
                @error('instructions')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="timeLimitMinutes" class="block text-sm font-medium text-slate-700">Time limit in minutes <span class="text-slate-400">(optional)</span></label>
                    <input wire:model.blur="timeLimitMinutes" id="timeLimitMinutes" type="number" min="1" class="mt-1 block w-full rounded-lg border-slate-300">
                    @error('timeLimitMinutes')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="passMark" class="block text-sm font-medium text-slate-700">Pass mark % <span class="text-slate-400">(optional)</span></label>
                    <input wire:model.blur="passMark" id="passMark" type="number" min="0" max="100" step="0.01" class="mt-1 block w-full rounded-lg border-slate-300">
                    @error('passMark')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="opensAt" class="block text-sm font-medium text-slate-700">Opens at <span class="text-slate-400">(optional)</span></label>
                    <input wire:model.blur="opensAt" id="opensAt" type="datetime-local" class="mt-1 block w-full rounded-lg border-slate-300">
                    @error('opensAt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="closesAt" class="block text-sm font-medium text-slate-700">Closes at <span class="text-slate-400">(optional)</span></label>
                    <input wire:model.blur="closesAt" id="closesAt" type="datetime-local" class="mt-1 block w-full rounded-lg border-slate-300">
                    @error('closesAt')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                    <select wire:model.blur="status" id="status" class="mt-1 block w-full rounded-lg border-slate-300">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="closed">Closed</option>
                    </select>
                    @error('status')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <label class="mt-7 flex items-center gap-3 rounded-lg bg-slate-50 px-3 py-3 text-sm text-slate-700">
                    <input wire:model="randomizeQuestions" type="checkbox" class="rounded border-slate-300 text-blue-700">
                    Randomize question order
                </label>
            </div>

            <div class="flex justify-end gap-3">
                <x-button type="button" wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button type="submit" variant="primary" icon="save" target="save" :loading="true">Save quiz</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$showDeleteModal" title="Archive quiz?" close-action="closeModals" max-width="md">
        <p class="text-sm text-slate-600">The quiz will be archived; existing attempts are retained.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-button wire:click="closeModals" variant="secondary" icon="close" target="closeModals" :loading="true">Cancel</x-button>
                <x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Archive quiz</x-button>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
