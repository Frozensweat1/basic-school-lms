@props([
    'href',
    'active' => false,
    'mobile' => false,
])

<a href="{{ $href }}" @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
        'flex min-h-12 items-center justify-between rounded-xl px-4 text-base' => $mobile,
        'inline-flex min-h-11 items-center rounded-lg px-2.5 text-sm' => ! $mobile,
        'bg-[color:var(--brand-primary)] text-white shadow-sm' => $active && $mobile,
        'text-[var(--brand-primary)] after:absolute after:inset-x-2 after:bottom-0 after:h-0.5 after:rounded-full after:bg-[var(--brand-accent)] relative' => $active && ! $mobile,
        'text-slate-700 hover:bg-slate-100 hover:text-slate-950' => ! $active,
    ]) }} style="outline-color: var(--brand-primary)">
    <span>{{ $slot }}</span>
    @if ($mobile)
        <svg aria-hidden="true" class="h-4 w-4 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
        </svg>
    @endif
</a>
