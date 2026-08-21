<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Assessment progress</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">Ward quizzes</h2>
    </div>

    <select wire:model.live="studentId" class="block w-full max-w-sm rounded-lg border-slate-300">
        <option value="">Choose a ward</option>
        @foreach($students as $ward)
            <option value="{{ $ward->id }}">{{ $ward->first_name }} {{ $ward->last_name }}</option>
        @endforeach
    </select>

    <div class="grid gap-4">
        @forelse($quizzes as $quiz)
            <article wire:key="quiz-{{ $quiz->id }}" class="rounded-2xl border border-slate-200 bg-white p-5">
                <h3 class="font-semibold text-slate-900">{{ $quiz->title }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $quiz->classSubject->subject->name }}</p>
                <p class="mt-2 text-sm text-slate-700">
                    {{ $quiz->attempt ? ucfirst(str_replace('_', ' ', $quiz->attempt->status)) : 'Not started' }}
                    @if($quiz->attempt && $quiz->attempt->score !== null)
                        · Score {{ $quiz->attempt->score }}/{{ $quiz->max_score }}
                    @endif
                </p>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">No published quizzes for this ward.</div>
        @endforelse
    </div>
</div>
