@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'action' => null,
    'actionLabel' => null,
    'secondaryAction' => null,
    'secondaryActionLabel' => null,
])

<section {{ $attributes->class(['px-4 py-14 sm:px-6 sm:py-16 lg:px-8']) }}>
    <div class="relative isolate mx-auto max-w-7xl overflow-hidden rounded-3xl bg-slate-950 px-6 py-10 text-white shadow-xl sm:px-10 sm:py-12 lg:px-14">
        <div aria-hidden="true" class="absolute -right-20 -top-24 -z-10 h-64 w-64 rounded-full opacity-30 blur-3xl" style="background: var(--brand-primary)"></div>
        <div aria-hidden="true" class="absolute -bottom-32 left-1/3 -z-10 h-56 w-56 rounded-full opacity-15 blur-3xl" style="background: var(--brand-accent)"></div>
        <div class="relative flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-3xl">
            @if ($eyebrow)
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--brand-accent)]">{{ $eyebrow }}</p>
            @endif
            <h2 class="mt-3 text-balance text-3xl font-black tracking-tight sm:text-4xl">{{ $title }}</h2>
            @if ($description)
                <p class="mt-4 max-w-2xl text-pretty leading-7 text-slate-300">{{ $description }}</p>
            @endif
        </div>
        @if (($action && $actionLabel) || ($secondaryAction && $secondaryActionLabel))
            <div class="flex shrink-0 flex-col gap-3 sm:flex-row lg:flex-col xl:flex-row">
                @if ($action && $actionLabel)
                    <a href="{{ $action }}" class="inline-flex min-h-12 items-center justify-center rounded-full px-6 text-sm font-bold shadow-lg transition hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                        style="background: var(--brand-accent); color: var(--brand-secondary); outline-color: white">{{ $actionLabel }}</a>
                @endif
                @if ($secondaryAction && $secondaryActionLabel)
                    <a href="{{ $secondaryAction }}" class="inline-flex min-h-12 items-center justify-center rounded-full border border-white/25 px-6 text-sm font-bold text-white transition hover:border-white/40 hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                        style="outline-color: var(--brand-accent)">{{ $secondaryActionLabel }}</a>
                @endif
            </div>
        @endif
        </div>
    </div>
</section>
