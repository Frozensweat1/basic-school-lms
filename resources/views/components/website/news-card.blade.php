@props(['post', 'compact' => false])

@php
    $imageUrl = $post->featured_image_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($post->featured_image_path)
        : null;
    $href = \Illuminate\Support\Facades\Route::has('website.news.show')
        ? route('website.news.show', ['slug' => $post->slug])
        : route('website.news');
    $summary = $post->excerpt ?: str($post->body)->stripTags()->squish()->limit(150);
@endphp

<article {{ $attributes->class([
    'group flex h-full overflow-hidden border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl',
    'flex-row rounded-2xl' => $compact,
    'flex-col rounded-3xl' => ! $compact,
]) }}>
    <a href="{{ $href }}" @class([
        'relative block shrink-0 overflow-hidden focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
        'w-28 sm:w-40' => $compact,
        'w-full' => ! $compact,
    ]) style="outline-color: var(--brand-primary)" aria-label="Read {{ $post->title }}">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $post->title }}" width="720" height="405" loading="lazy" decoding="async"
                @class([
                    'w-full object-cover transition duration-500 group-hover:scale-[1.03]',
                    'h-full min-h-32' => $compact,
                    'aspect-[16/9]' => ! $compact,
                ])>
        @else
            <span @class(['flex items-end', 'h-full min-h-32 p-3' => $compact, 'aspect-[16/9] p-6' => ! $compact]) style="background: linear-gradient(135deg, var(--brand-secondary), var(--brand-primary))">
                <span @class(['font-black uppercase text-white/80', 'text-[0.6rem] tracking-[0.12em]' => $compact, 'text-xs tracking-[0.2em]' => ! $compact])>School story</span>
            </span>
        @endif
    </a>
    <div @class(['flex min-w-0 flex-1 flex-col', 'p-4 sm:p-5' => $compact, 'p-6' => ! $compact])>
        @if ($post->published_at)
            <time datetime="{{ $post->published_at->toAtomString() }}" class="text-xs font-bold uppercase tracking-[0.16em] text-[var(--brand-primary)]">{{ $post->published_at->format('d F Y') }}</time>
        @endif
        <h2 @class(['font-black tracking-tight text-slate-950', 'mt-2 line-clamp-2 text-base sm:text-lg' => $compact, 'mt-3 text-xl' => ! $compact])>
            <a href="{{ $href }}" class="transition hover:text-[var(--brand-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2" style="outline-color: var(--brand-primary)">{{ $post->title }}</a>
        </h2>
        @if ($summary)
            <p @class(['flex-1 text-sm text-slate-600', 'mt-2 hidden leading-5 sm:line-clamp-2' => $compact, 'mt-3 line-clamp-3 leading-6' => ! $compact])>{{ $summary }}</p>
        @endif
        <a href="{{ $href }}" @class(['inline-flex w-fit items-center gap-2 text-sm font-bold text-[var(--brand-primary)] transition group-hover:gap-3 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2', 'mt-auto hidden pt-3 sm:inline-flex' => $compact, 'mt-5' => ! $compact]) style="outline-color: var(--brand-primary)">
            Read story
            <svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
        </a>
    </div>
</article>
