<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Assignment submissions</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $assignment->title }}</h2>
            <p class="mt-1 text-sm text-slate-600">
                {{ $assignment->classSubject->schoolClass->name }} &middot; {{ $assignment->classSubject->subject->name }} &middot; Due {{ $assignment->due_at->format('d M Y, H:i') }}
            </p>
        </div>

        @if ($canGrade)
            <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700">You can grade these submissions</span>
        @else
            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">View-only review</span>
        @endif
    </div>

    <div class="grid gap-4">
        @forelse ($submissions as $submission)
            <article wire:key="submission-{{ $submission->id }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $submission->student->first_name }} {{ $submission->student->last_name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">Submitted {{ $submission->submitted_at?->format('d M Y, H:i') ?? '—' }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $submission->status === 'graded' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">{{ ucfirst($submission->status) }}</span>
                </div>

                @if (filled($submission->submission_text))
                    <p class="mt-4 whitespace-pre-wrap text-sm text-slate-700">{{ $submission->submission_text }}</p>
                @else
                    <p class="mt-4 text-sm italic text-slate-500">No written response was provided.</p>
                @endif

                @if ($submission->attachments->isNotEmpty())
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Submitted files</span>
                        @foreach ($submission->attachments as $attachment)
                            <x-button type="button" wire:click="downloadAttachment({{ $attachment->id }})" variant="secondary" size="xs" target="downloadAttachment({{ $attachment->id }})" :loading="true">{{ $attachment->name }}</x-button>
                        @endforeach
                    </div>
                @endif

                @if ($canGrade)
                    <div class="mt-5 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Score / {{ $assignment->max_score }}</label>
                            <input wire:model.blur="scores.{{ $submission->id }}" type="number" min="0" max="{{ $assignment->max_score }}" step="0.01" class="mt-1 block w-full rounded-lg border-slate-300">
                            @error('scores.'.$submission->id)<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Feedback</label>
                            <textarea wire:model.blur="feedback.{{ $submission->id }}" rows="2" class="mt-1 block w-full rounded-lg border-slate-300"></textarea>
                            @error('feedback.'.$submission->id)<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <x-button class="mt-4" wire:click="saveGrade({{ $submission->id }})" icon="save" target="saveGrade({{ $submission->id }})" :loading="true">Save grade</x-button>
                @else
                    <div class="mt-5 grid gap-3 border-t border-slate-100 pt-5 text-sm sm:grid-cols-2">
                        <div>
                            <p class="text-slate-500">Score</p>
                            <p class="mt-1 font-medium text-slate-800">{{ $submission->score !== null ? $submission->score.' / '.$assignment->max_score : 'Pending grading' }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Teacher feedback</p>
                            <p class="mt-1 font-medium text-slate-800">{{ $submission->feedback ?: 'No feedback yet' }}</p>
                        </div>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center">
                <p class="font-medium text-slate-700">No submissions yet.</p>
                <p class="mt-1 text-sm text-slate-500">Submitted learner work will appear here for review.</p>
            </div>
        @endforelse
    </div>

    <x-pagination :paginator="$submissions" />
</div>
