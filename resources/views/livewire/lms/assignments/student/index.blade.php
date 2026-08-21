<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Learning work</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">My assignments</h2>
        <p class="mt-1 text-sm text-slate-600">Submit written work and supporting files securely.</p>
    </div>

    <div class="grid gap-4">
        @forelse($assignments as $assignment)
            @php($canSubmit = (!$assignment->opens_at || $assignment->opens_at->isPast()) && ($assignment->allow_late_submission || !$assignment->due_at->isPast()))
            <article wire:key="assignment-{{ $assignment->id }}" class="rounded-2xl border border-slate-200 bg-white p-5">
                <h3 class="font-semibold text-slate-900">{{ $assignment->title }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $assignment->classSubject->subject->name }} · Due {{ $assignment->due_at->format('d M Y, H:i') }}</p>
                <p class="mt-3 whitespace-pre-wrap text-sm text-slate-700">{{ strip_tags($assignment->instructions) }}</p>
                <p class="mt-2 text-sm {{ $assignment->submission ? 'text-emerald-700' : ($canSubmit ? 'text-amber-700' : 'text-rose-700') }}">{{ $assignment->submission ? ucfirst($assignment->submission->status) : ($canSubmit ? 'Not submitted' : 'Not currently accepting submissions') }}</p>

                @if($assignment->submission?->attachments?->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($assignment->submission->attachments as $attachment)
                            <button wire:click="downloadAttachment({{ $attachment->id }})" wire:loading.attr="disabled" wire:target="downloadAttachment({{ $attachment->id }})" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">{{ $attachment->name }}</button>
                        @endforeach
                    </div>
                @endif

                @if(!$assignment->submission && $canSubmit)
                    <textarea wire:model="submissionTexts.{{ $assignment->id }}" rows="3" class="mt-3 block w-full rounded-lg border-slate-300" placeholder="Write your submission"></textarea>
                    <div class="mt-3"><label class="block text-sm font-medium text-slate-700">Attach a file <span class="font-normal text-slate-500">(PDF, Word, image, or ZIP; max 10 MB)</span></label><input type="file" wire:model="submissionFiles.{{ $assignment->id }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">@error('submissionFiles.'.$assignment->id)<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <x-button class="mt-4" wire:click="submit({{ $assignment->id }})" icon="send" target="submit({{ $assignment->id }})">Submit assignment</x-button>
                @elseif(!$assignment->submission && $assignment->opens_at?->isFuture())
                    <p class="mt-3 text-sm text-slate-500">Opens {{ $assignment->opens_at->format('d M Y, H:i') }}.</p>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">No published assignments are available.</div>
        @endforelse
    </div>
</div>
