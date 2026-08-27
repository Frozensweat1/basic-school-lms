@props(['event', 'compact' => false])

@php
    $imageUrl = $event->featured_image_path
        ? $event->featured_image_path
        : null;
    $href = \Illuminate\Support\Facades\Route::has('website.events.show')
        ? route('website.events.show', ['slug' => $event->slug])
        : route('website.events');
@endphp

<article {{ $attributes->class([
    'group flex h-full overflow-hidden border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl',
    'flex-row rounded-2xl' => $compact,
    'flex-col rounded-3xl sm:flex-row' => ! $compact,
]) }}>
    @if ($imageUrl)
        <a href="{{ $href }}" @class(['block shrink-0 overflow-hidden', 'w-28 sm:w-36' => $compact, 'sm:w-2/5' => ! $compact]) aria-label="View {{ $event->title }}">
            <x-website.optimized-image
                src="{{ $imageUrl }}"
                alt="{{ $event->title }}"
                width="640"
                height="480"
                @class(['h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]', 'min-h-36' => $compact, 'aspect-[16/10] min-h-48 sm:aspect-auto' => ! $compact])
            />
        </a>
    @endif
    <div @class(['flex min-w-0 flex-1 flex-col', 'p-4 sm:p-5' => $compact, 'p-6' => ! $compact])>
        <div class="flex items-start gap-4">
            <time datetime="{{ $event->starts_at->toAtomString() }}" @class(['flex shrink-0 flex-col overflow-hidden rounded-xl text-center ring-1 ring-slate-200', 'w-12' => $compact, 'w-14' => ! $compact])>
                <span class="bg-[var(--brand-primary)] px-2 py-1 text-[0.65rem] font-black uppercase tracking-wider text-white">{{ $event->starts_at->format('M') }}</span>
                <span class="bg-slate-50 px-2 py-2 text-xl font-black text-slate-950">{{ $event->starts_at->format('d') }}</span>
            </time>
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--brand-primary)]">{{ $event->starts_at->format('l, H:i') }}</p>
                <h2 @class(['mt-2 font-black tracking-tight text-slate-950', 'line-clamp-2 text-base sm:text-lg' => $compact, 'text-xl' => ! $compact])>
                    <a href="{{ $href }}" class="transition hover:text-[var(--brand-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2" style="outline-color: var(--brand-primary)">{{ $event->title }}</a>
                </h2>
            </div>
        </div>
        @if ($event->description)
            <p @class(['text-sm text-slate-600', 'mt-2 hidden line-clamp-2 leading-5 sm:block' => $compact, 'mt-4 line-clamp-3 leading-6' => ! $compact])>{{ $event->description }}</p>
        @endif
        @if ($event->location)
            <p @class(['mt-auto flex items-center gap-2 text-sm font-medium text-slate-500', 'pt-3' => $compact, 'pt-5' => ! $compact])>
                <svg aria-hidden="true" class="h-4 w-4 shrink-0 text-[var(--brand-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.1 7-12A7 7 0 1 0 5 9c0 6.9 7 12 7 12Z" /><circle cx="12" cy="9" r="2.5" /></svg>
                <span class="truncate">{{ $event->location }}</span>
            </p>
        @endif
    </div>
</article>
