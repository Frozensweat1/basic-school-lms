@php
    $isTextList = in_array($collection, ['steps', 'requirements'], true);
    $isStats = $collection === 'stats';
@endphp

<section class="space-y-3 border-t border-slate-200 pt-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
            <p class="mt-1 text-sm text-slate-500">Items appear publicly in the order shown here.</p>
        </div>
        <x-button type="button" wire:click="addStructuredItem('{{ $collection }}')" variant="ghost" size="sm" icon="plus" target="addStructuredItem('{{ $collection }}')" :loading="true">{{ $addLabel }}</x-button>
    </div>

    <div class="space-y-3">
        @forelse ($rows as $index => $row)
            <div wire:key="{{ $collection }}-row-{{ $index }}" class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                <span class="mt-2 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold text-slate-500 ring-1 ring-slate-200">{{ $index + 1 }}</span>
                @if ($isTextList)
                    <div class="min-w-0 flex-1">
                        <input wire:model.blur="{{ $collection }}.{{ $index }}" type="text" class="w-full rounded-lg border-slate-300 bg-white" placeholder="Enter {{ str($collection)->singular() }}">
                        @error($collection.'.'.$index) <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>
                @elseif ($isStats)
                    <div class="grid min-w-0 flex-1 gap-3 sm:grid-cols-[9rem_1fr]">
                        <div><input wire:model.blur="stats.{{ $index }}.value" type="text" class="w-full rounded-lg border-slate-300 bg-white" placeholder="Value">@error('stats.'.$index.'.value')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><input wire:model.blur="stats.{{ $index }}.label" type="text" class="w-full rounded-lg border-slate-300 bg-white" placeholder="Label">@error('stats.'.$index.'.label')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    </div>
                @else
                    <div class="grid min-w-0 flex-1 gap-3">
                        <div><input wire:model.blur="{{ $collection }}.{{ $index }}.title" type="text" class="w-full rounded-lg border-slate-300 bg-white" placeholder="Title">@error($collection.'.'.$index.'.title')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                        <div><textarea wire:model.blur="{{ $collection }}.{{ $index }}.description" rows="2" class="w-full rounded-lg border-slate-300 bg-white" placeholder="Description"></textarea>@error($collection.'.'.$index.'.description')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    </div>
                @endif
                <x-ui.icon-button wire:click="removeStructuredItem('{{ $collection }}', {{ $index }})" icon="trash" variant="danger" label="Remove item {{ $index + 1 }}" target="removeStructuredItem('{{ $collection }}', {{ $index }})" />
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">No items yet. Use “{{ $addLabel }}” to create one.</div>
        @endforelse
    </div>
</section>
