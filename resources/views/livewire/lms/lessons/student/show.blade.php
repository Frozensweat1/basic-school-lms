<div class="mx-auto max-w-5xl space-y-6">
    <nav aria-label="Lesson breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('lms.lessons.student.index') }}" wire:navigate class="cursor-pointer font-medium text-blue-800 hover:text-blue-700 hover:underline">My lessons</a>
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>{{ $lesson->topic->classSubject->subject->name }}</span>
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span class="truncate" aria-current="page">{{ $lesson->title }}</span>
    </nav>

    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <header class="bg-gradient-to-br from-blue-950 via-blue-900 to-indigo-800 px-6 py-8 text-white sm:px-9 sm:py-10">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                        <span class="rounded-full bg-white/15 px-3 py-1.5 text-blue-50">{{ $lesson->topic->classSubject->subject->name }}</span>
                        <span class="text-blue-200">{{ $lesson->topic->title }}</span>
                    </div>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">{{ $lesson->title }}</h1>
                    @if ($lesson->summary)
                        <p class="mt-4 max-w-2xl text-sm leading-7 text-blue-100 sm:text-base">{{ $lesson->summary }}</p>
                    @endif
                </div>

                <span class="inline-flex shrink-0 items-center gap-2 self-start rounded-full bg-emerald-400/15 px-3 py-2 text-xs font-bold text-emerald-100 ring-1 ring-inset ring-emerald-300/30">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m4 10 4 4 8-8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Lesson completed
                </span>
            </div>

            <div class="mt-7 flex flex-wrap gap-x-5 gap-y-2 border-t border-white/15 pt-5 text-xs text-blue-100">
                <span>{{ $lesson->topic->classSubject->schoolClass->name }}{{ $lesson->topic->classSubject->schoolClass->stream ? ' - '.$lesson->topic->classSubject->schoolClass->stream->name : '' }}</span>
                @if ($lesson->teacher)
                    <span>Teacher: {{ $lesson->teacher->first_name }} {{ $lesson->teacher->last_name }}</span>
                @endif
                @if ($lesson->published_at)
                    <span>Published {{ $lesson->published_at->format('M j, Y') }}</span>
                @endif
            </div>
        </header>

        <div class="space-y-8 px-6 py-7 sm:px-9 sm:py-9">
            @if ($justCompleted)
                <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900" role="status">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m4 10 4 4 8-8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <div>
                        <p class="font-semibold">Your progress was updated.</p>
                        <p class="mt-0.5 text-emerald-800">Opening this lesson marked it as completed. Revisiting it will not duplicate or reset your progress.</p>
                    </div>
                </div>
            @endif

            @if ($lesson->objectives)
                <section class="rounded-2xl border border-blue-100 bg-blue-50/70 p-5" aria-labelledby="lesson-objectives-heading">
                    <h2 id="lesson-objectives-heading" class="font-bold text-blue-950">What you will learn</h2>
                    <ul class="mt-3 grid gap-2 text-sm leading-6 text-blue-900 sm:grid-cols-2">
                        @foreach ($lesson->objectives as $objective)
                            <li class="flex items-start gap-2">
                                <svg class="mt-1 h-4 w-4 shrink-0 text-blue-700" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m4 10 4 4 8-8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span>{{ $objective }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section aria-labelledby="lesson-content-heading">
                <h2 id="lesson-content-heading" class="sr-only">Lesson content</h2>
                @if ($lesson->content)
                    <div class="prose prose-slate max-w-none leading-7">
                        {!! app(App\Support\ContentSanitizer::class)->clean($lesson->content) !!}
                    </div>
                @else
                    <p class="rounded-2xl bg-slate-50 p-5 text-sm text-slate-600">The teacher has not added written content to this lesson yet. Check the resources below.</p>
                @endif
            </section>

            @if ($lesson->resources->isNotEmpty())
                <section class="border-t border-slate-200 pt-7" aria-labelledby="lesson-resources-heading">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Supporting material</p>
                        <h2 id="lesson-resources-heading" class="mt-1 text-xl font-bold text-slate-900">Learning resources</h2>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($lesson->resources as $resource)
                            <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $resource->title }}</p>
                                    <p class="mt-0.5 text-xs uppercase tracking-wide text-slate-500">{{ str_replace('_', ' ', $resource->type) }}</p>
                                </div>

                                @if ($resource->external_url)
                                    <a href="{{ $resource->external_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-blue-100 px-3 py-2 text-xs font-semibold text-blue-800 transition hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-700">
                                        Open
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M11 4h5v5M9 11l7-7M16 11v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </a>
                                @else
                                    <x-button wire:click="downloadResource({{ $resource->id }})" variant="secondary" size="xs" target="downloadResource({{ $resource->id }})" :loading="true">Download</x-button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </article>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="next-lesson-heading">
        @if ($nextLesson)
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Continue learning</p>
                    <h2 id="next-lesson-heading" class="mt-1 text-lg font-bold text-slate-900">Next: {{ $nextLesson->title }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $nextLesson->topic->title }}</p>
                </div>
                <a href="{{ route('lms.lessons.student.show', $nextLesson) }}" wire:navigate class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    Next lesson
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>
        @else
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Subject complete</p>
                    <h2 id="next-lesson-heading" class="mt-1 text-lg font-bold text-slate-900">You reached the end of this subject's published lessons.</h2>
                    <p class="mt-1 text-sm text-slate-500">Return to your library to choose another subject or review a completed lesson.</p>
                </div>
                <a href="{{ route('lms.lessons.student.index') }}" wire:navigate class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300">
                    Back to my lessons
                </a>
            </div>
        @endif
    </section>
</div>
