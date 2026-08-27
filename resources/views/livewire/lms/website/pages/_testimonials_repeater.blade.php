<section class="space-y-3 border-t border-slate-200 pt-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h3 class="font-semibold text-slate-900">Homepage testimonials</h3>
            <p class="mt-1 text-sm text-slate-500">Show short, authentic feedback from parents, learners, and alumni.</p>
            <p class="mt-1 text-xs text-slate-500">Best results: 20-45 words per quote and square avatars (1:1) for clean alignment.</p>
        </div>
        <x-button type="button" wire:click="addStructuredItem('testimonials')" variant="ghost" size="sm" icon="plus" target="addStructuredItem('testimonials')" :loading="true">Add testimonial</x-button>
    </div>

    <div class="space-y-3">
        @forelse ($testimonials as $index => $testimonial)
            @php
                $quoteLength = strlen((string) ($testimonial['text'] ?? ''));
            @endphp
            <div wire:key="testimonials-row-{{ $index }}" class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                <div class="grid gap-3 sm:grid-cols-[1fr_1fr_7rem]">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Author</label>
                        <input wire:model.blur="testimonials.{{ $index }}.author" type="text" class="mt-1 w-full rounded-lg border-slate-300 bg-white" placeholder="Ama Mensah">
                        @error('testimonials.'.$index.'.author')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Role</label>
                        <input wire:model.blur="testimonials.{{ $index }}.role" type="text" class="mt-1 w-full rounded-lg border-slate-300 bg-white" placeholder="Parent">
                        @error('testimonials.'.$index.'.role')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Rating</label>
                        <select wire:model.blur="testimonials.{{ $index }}.rating" class="mt-1 w-full rounded-lg border-slate-300 bg-white">
                            @for ($rating = 5; $rating >= 1; $rating--)
                                <option value="{{ $rating }}">{{ $rating }} star{{ $rating > 1 ? 's' : '' }}</option>
                            @endfor
                        </select>
                        @error('testimonials.'.$index.'.rating')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-3">
                    <div class="flex items-center justify-between gap-3">
                        <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Testimonial text</label>
                        <span class="text-xs font-medium {{ $quoteLength > 320 ? 'text-amber-700' : 'text-slate-500' }}">{{ $quoteLength }}/450</span>
                    </div>
                    <textarea wire:model.live.debounce.300ms="testimonials.{{ $index }}.text" rows="3" maxlength="450" class="mt-1 w-full rounded-lg border-slate-300 bg-white" placeholder="Share a concise and specific quote."></textarea>
                    @error('testimonials.'.$index.'.text')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>

                <div class="mt-3 grid gap-3 sm:grid-cols-[7rem_1fr] sm:items-center">
                    <div class="h-16 w-16 overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                        @if (isset($testimonialAvatars[$index]) && $testimonialAvatars[$index])
                            <img src="{{ $testimonialAvatars[$index]->temporaryUrl() }}" alt="New avatar preview" class="h-full w-full object-cover">
                        @elseif (! empty($testimonial['avatar']))
                            <img src="{{ \Illuminate\Support\Str::startsWith($testimonial['avatar'], ['http://', 'https://']) ? $testimonial['avatar'] : Storage::disk('public')->url($testimonial['avatar']) }}" alt="Current avatar" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-slate-400">No photo</div>
                        @endif
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Avatar (optional)</label>
                        <input wire:model="testimonialAvatars.{{ $index }}" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-semibold file:text-slate-700">
                        <p wire:loading wire:target="testimonialAvatars.{{ $index }}" class="mt-1 text-xs font-medium text-blue-700">Uploading avatar preview...</p>
                        @error('testimonialAvatars.'.$index)<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror

                        @if (! empty($testimonial['avatar']))
                            <label class="mt-2 inline-flex items-center gap-2 text-sm text-slate-600">
                                <input wire:model="removeTestimonialAvatars.{{ $index }}" type="checkbox" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                Remove current avatar
                            </label>
                        @endif
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <x-ui.icon-button wire:click="removeStructuredItem('testimonials', {{ $index }})" icon="trash" variant="danger" label="Remove testimonial {{ $index + 1 }}" target="removeStructuredItem('testimonials', {{ $index }})" />
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">No testimonials yet. Use Add testimonial to create one.</div>
        @endforelse
    </div>
</section>
