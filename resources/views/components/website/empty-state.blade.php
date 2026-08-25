@props([
    'title',
    'description' => null,
    'action' => null,
    'actionLabel' => null,
])

<div {{ $attributes->class(['rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center sm:px-10']) }}>
    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-[var(--brand-primary)] shadow-sm ring-1 ring-slate-200">
        @isset($icon)
            {{ $icon }}
        @else
            <svg aria-hidden="true" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.8A2.8 2.8 0 0 1 6.8 3h10.4A2.8 2.8 0 0 1 20 5.8v12.4a2.8 2.8 0 0 1-2.8 2.8H6.8A2.8 2.8 0 0 1 4 18.2V5.8Z" /><path stroke-linecap="round" d="M8 9h8M8 13h5" /></svg>
        @endisset
    </span>
    <h2 class="mt-5 text-lg font-bold text-slate-950">{{ $title }}</h2>
    @if ($description)
        <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-600">{{ $description }}</p>
    @endif
    @if (($action && $actionLabel) || trim((string) $slot) !== '')
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            @if ($action && $actionLabel)
                <a href="{{ $action }}" class="inline-flex min-h-11 items-center justify-center rounded-full px-5 text-sm font-bold text-white shadow-sm transition hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2" style="background: var(--brand-primary); outline-color: var(--brand-primary)">{{ $actionLabel }}</a>
            @endif
            {{ $slot }}
        </div>
    @endif
</div>
