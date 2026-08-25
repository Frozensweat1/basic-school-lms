@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'align' => 'left',
])

@php($centered = $align === 'center')

<div {{ $attributes->class([$centered ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl']) }}>
    @if ($eyebrow)
        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--brand-primary)]">{{ $eyebrow }}</p>
    @endif
    <h2 class="mt-3 text-balance text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ $title }}</h2>
    @if ($description)
        <p class="mt-4 text-pretty text-base leading-7 text-slate-600 sm:text-lg">{{ $description }}</p>
    @endif
</div>
