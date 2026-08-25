@props([
    'branding' => null,
    'compact' => false,
    'inverse' => false,
    'href' => null,
])

@php
    $branding ??= app(\App\Support\SchoolBranding::class)->data();
    $href ??= route('home');
@endphp

<a href="{{ $href }}" {{ $attributes->class(['group inline-flex items-center gap-3 rounded-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4']) }}
    style="outline-color: var(--brand-accent)" aria-label="{{ $branding['name'] }} home">
    @if ($branding['logo_url'])
        <img src="{{ $branding['logo_url'] }}" alt="" width="48" height="48"
            class="h-11 w-11 shrink-0 rounded-xl object-cover shadow-sm ring-1 ring-slate-950/10 sm:h-12 sm:w-12">
    @else
        <span aria-hidden="true"
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-sm font-black tracking-wide text-white shadow-sm ring-1 ring-white/20 sm:h-12 sm:w-12"
            style="background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary))">
            {{ $branding['initials'] }}
        </span>
    @endif

    @unless ($compact)
        <span class="min-w-0">
            <span @class([
                'block truncate text-base font-black leading-tight tracking-tight sm:text-lg',
                'text-white' => $inverse,
                'text-slate-950 group-hover:text-[var(--brand-primary)]' => ! $inverse,
            ])>{{ $branding['name'] }}</span>
            @if ($branding['motto'])
                <span @class([
                    'mt-1 hidden truncate text-[0.68rem] font-semibold uppercase tracking-[0.16em] sm:block',
                    'text-slate-400' => $inverse,
                    'text-slate-500' => ! $inverse,
                ])>{{ $branding['motto'] }}</span>
            @endif
        </span>
    @endunless
</a>
