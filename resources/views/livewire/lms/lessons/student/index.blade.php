<div class="space-y-6">
    <header class="overflow-hidden rounded-3xl bg-gradient-to-br from-blue-950 via-blue-900 to-indigo-800 px-6 py-7 text-white shadow-lg sm:px-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-200">My learning library</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight">Lessons for your class</h2>
                <p class="mt-2 text-sm leading-6 text-blue-100">Choose a subject and topic, continue learning at your own pace, and keep your progress up to date automatically.</p>
            </div>

            <div class="min-w-64 rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                <div class="flex items-center justify-between gap-4 text-sm">
                    <span class="font-medium text-blue-100">Overall progress</span>
                    <span class="font-bold text-white">{{ $completionPercentage }}%</span>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-blue-950/50" role="progressbar" aria-label="Lesson completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $completionPercentage }}">
                    <div class="h-full rounded-full bg-emerald-400 transition-all duration-300" style="width: {{ $completionPercentage }}%"></div>
                </div>
                <p class="mt-2 text-xs text-blue-100">{{ $completedLessons }} of {{ $totalLessons }} {{ \Illuminate\Support\Str::plural('lesson', $totalLessons) }} completed</p>
            </div>
        </div>
    </header>

    @php
        $filtersActive = filled($search) || filled($filterSubjectId) || filled($filterTopicId);
    @endphp

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" aria-labelledby="lesson-filters-heading">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 id="lesson-filters-heading" class="font-semibold text-slate-900">Find a lesson</h3>
                <p class="mt-0.5 text-xs text-slate-500">Topics update when you choose a subject.</p>
            </div>
            <p class="text-sm text-slate-500" aria-live="polite">
                <span wire:loading.remove wire:target="search,filterSubjectId,filterTopicId">{{ $filteredTotal }} {{ \Illuminate\Support\Str::plural('lesson', $filteredTotal) }}</span>
                <span wire:loading wire:target="search,filterSubjectId,filterTopicId">Updating lessons...</span>
            </p>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(18rem,1fr)_15rem_15rem_auto]">
            <div class="relative">
                <label for="student-lesson-search" class="sr-only">Search lessons</label>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input
                    id="student-lesson-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search lesson, subject, or topic"
                    autocomplete="off"
                    class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-4 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700"
                >
            </div>

            <div>
                <label for="student-lesson-subject" class="sr-only">Filter by subject</label>
                <select id="student-lesson-subject" wire:model.live="filterSubjectId" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                    <option value="">All subjects</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="student-lesson-topic" class="sr-only">Filter by topic</label>
                <select id="student-lesson-topic" wire:model.live="filterTopicId" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-700 focus:ring-blue-700">
                    <option value="">All topics</option>
                    @foreach ($topics as $topic)
                        <option value="{{ $topic->id }}">
                            {{ filled($filterSubjectId) ? $topic->title : $topic->classSubject->subject->name.' - '.$topic->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($filtersActive)
                <x-button wire:click="clearFilters" variant="ghost" target="clearFilters" :loading="true">Clear filters</x-button>
            @endif
        </div>
    </section>

    <section aria-labelledby="lesson-results-heading">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Curriculum</p>
                <h3 id="lesson-results-heading" class="mt-1 text-xl font-bold text-slate-900">Available lessons</h3>
            </div>
            @if ($lessons->isNotEmpty())
                <p class="text-xs font-medium text-slate-500">Showing {{ $lessons->count() }} of {{ $filteredTotal }}</p>
            @endif
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            @forelse ($lessons as $lesson)
                @php
                    $completed = $lesson->progress->isNotEmpty();
                    $subject = $lesson->topic->classSubject->subject;
                    $schoolClass = $lesson->topic->classSubject->schoolClass;
                @endphp
                <article wire:key="student-lesson-{{ $lesson->id }}" class="group flex h-full flex-col overflow-hidden rounded-2xl border {{ $completed ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200 bg-white' }} shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-blue-800">{{ $subject->name }}</span>
                                    <span class="text-slate-400" aria-hidden="true">/</span>
                                    <span class="text-slate-600">{{ $lesson->topic->title }}</span>
                                </div>
                                <h4 class="mt-3 text-lg font-bold text-slate-900 transition group-hover:text-blue-800">{{ $lesson->title }}</h4>
                            </div>

                            @if ($completed)
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m4 10 4 4 8-8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Completed
                                </span>
                            @endif
                        </div>

                        <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">{{ $lesson->summary ?: 'Open this lesson to view the learning content and resources.' }}</p>

                        <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-slate-100 pt-4 text-xs text-slate-500">
                            <span>{{ $schoolClass->name }}{{ $schoolClass->stream ? ' - '.$schoolClass->stream->name : '' }}</span>
                            @if ($lesson->teacher)
                                <span>{{ $lesson->teacher->first_name }} {{ $lesson->teacher->last_name }}</span>
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('lms.lessons.student.show', $lesson) }}" wire:navigate class="inline-flex cursor-pointer items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3 text-sm font-semibold text-blue-900 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-700">
                        <span>{{ $completed ? 'Review lesson' : 'Open lesson' }}</span>
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </article>
            @empty
                <div class="lg:col-span-2">
                    <x-empty-state
                        title="No lessons found"
                        :description="$filtersActive ? 'Try another subject, topic, or search phrase.' : 'No published lessons are available for your active class yet.'"
                    />
                </div>
            @endforelse
        </div>

        @if ($hasMore)
            <div class="mt-7 flex flex-col items-center gap-2" aria-live="polite">
                <x-button
                    wire:key="lesson-more-{{ md5($search) }}-{{ $filterSubjectId ?: 'all' }}-{{ $filterTopicId ?: 'all' }}-{{ $visibleLessons }}"
                    wire:intersect.once.margin.300px="loadMore"
                    wire:click="loadMore"
                    variant="secondary"
                    target="loadMore"
                    :loading="true"
                >
                    Load more lessons
                </x-button>
                <p wire:loading.remove wire:target="loadMore" class="text-xs text-slate-500">More lessons load automatically as you scroll.</p>
                <p wire:loading wire:target="loadMore" class="text-xs font-medium text-blue-700">Loading more lessons...</p>
            </div>
        @elseif ($lessons->isNotEmpty())
            <p class="mt-7 text-center text-xs font-medium text-slate-500">You have reached the end of these lessons.</p>
        @endif
    </section>
</div>
