<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Assessment work</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">My quizzes</h2>
        <p class="mt-1 text-sm text-slate-600">Complete published quizzes within their allowed attempt and time windows.</p>
    </div>

    <div class="grid gap-4">
        @forelse ($quizzes as $quiz)
            @php
                $isUpcoming = $quiz->hasNotOpened();
                $isClosed = $quiz->hasClosed();
                $activeAttempt = $quiz->attempt?->status === 'in_progress';
                $canStart = $quiz->isOpenForAttempts()
                    && (! $quiz->attempt || ($quiz->attempt->status === 'completed' && $quiz->attemptCount < $quiz->max_attempts));
            @endphp

            <article wire:key="quiz-{{ $quiz->id }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-slate-900">{{ $quiz->title }}</h3>
                            @if ($isUpcoming)
                                <x-badge variant="warning">Upcoming</x-badge>
                            @elseif ($isClosed)
                                <x-badge variant="danger">Closed</x-badge>
                            @else
                                <x-badge variant="success">Open</x-badge>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $quiz->classSubject->subject->name }}
                            &middot; Attempts {{ $quiz->attemptCount }}/{{ $quiz->max_attempts }}
                            @if ($quiz->time_limit_minutes)
                                &middot; {{ $quiz->time_limit_minutes }} minutes
                            @endif
                        </p>
                        <p class="mt-2 text-sm text-slate-600">
                            {{ $quiz->attempt ? str($quiz->attempt->status)->replace('_', ' ')->title() : 'Not started' }}
                            @if ($quiz->attempt?->score !== null)
                                &middot; Score {{ $quiz->attempt->score }}/{{ $quiz->max_score }}
                            @endif
                        </p>
                    </div>

                    <div class="shrink-0 text-left text-xs text-slate-500 sm:text-right">
                        @if ($quiz->opens_at)
                            <p><span class="font-semibold text-slate-700">Opens:</span> {{ $quiz->opens_at->format('d M Y, g:i A') }}</p>
                        @endif
                        @if ($quiz->closes_at)
                            <p class="mt-1"><span class="font-semibold text-slate-700">Closes:</span> {{ $quiz->closes_at->format('d M Y, g:i A') }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-4 border-t border-slate-100 pt-4">
                    @if ($isUpcoming)
                        <p class="inline-flex items-center gap-2 rounded-xl bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800">
                            This quiz opens {{ $quiz->opens_at->diffForHumans() }}.
                        </p>
                    @elseif ($isClosed)
                        <p class="inline-flex items-center gap-2 rounded-xl bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700">
                            The attempt window for this quiz has closed.
                        </p>
                    @elseif ($activeAttempt)
                        <a href="{{ route('lms.quizzes.student.attempt', $quiz->attempt) }}" class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            Continue attempt
                        </a>
                    @elseif ($canStart)
                        <x-button wire:click="start({{ $quiz->id }})" icon="play" target="start({{ $quiz->id }})" :loading="true">Start attempt</x-button>
                    @elseif ($quiz->attempt?->status === 'submitted')
                        <p class="text-sm font-medium text-emerald-700">Submitted for grading.</p>
                    @else
                        <p class="text-sm font-medium text-slate-600">No attempts remaining.</p>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">No published quizzes are available.</div>
        @endforelse
    </div>

    <x-pagination :paginator="$quizzes" />
</div>
