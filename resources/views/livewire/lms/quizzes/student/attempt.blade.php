<div class="space-y-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.22em] text-slate-500">Assessment work</p>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $attempt->quiz->title }}</h2>
        @if($attempt->quiz->instructions)
            <p class="mt-2 text-sm text-slate-600">{{ strip_tags($attempt->quiz->instructions) }}</p>
        @endif
    </div>

    <form wire:submit="save" class="space-y-4">
        @foreach($questions as $quizQuestion)
            @php($question = $quizQuestion->question)
            <fieldset class="rounded-2xl border border-slate-200 bg-white p-5">
                <legend class="font-medium text-slate-900">{{ $loop->iteration }}. {{ strip_tags($question->prompt) }} <span class="text-xs font-normal text-slate-500">({{ $question->max_score }} point{{ $question->max_score == 1 ? '' : 's' }})</span></legend>

                @if(in_array($question->type, ['multiple_choice', 'true_false'], true))
                    <div class="mt-4 space-y-2">
                        @foreach($question->options as $option)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-3 py-2 hover:bg-slate-50">
                                <input type="radio" wire:model="answers.{{ $question->id }}" value="{{ $option->label }}" class="text-blue-600">
                                <span class="text-sm text-slate-700">{{ $option->label }}</span>
                            </label>
                        @endforeach
                    </div>
                @elseif($question->type === 'essay')
                    <textarea wire:model="answers.{{ $question->id }}" rows="5" class="mt-3 block w-full rounded-lg border-slate-300" placeholder="Write your response..."></textarea>
                @else
                    <input wire:model="answers.{{ $question->id }}" type="text" class="mt-3 block w-full rounded-lg border-slate-300" placeholder="Enter your answer...">
                @endif
                @error('answers.'.$question->id) <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
            </fieldset>
        @endforeach

        <x-button type="submit" icon="check" target="save">Submit quiz</x-button>
    </form>
</div>
