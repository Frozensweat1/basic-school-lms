@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'image' => null,
    'imageAlt' => '',
    'variant' => 'default',
    'action' => null,
    'actionLabel' => null,
    'secondaryAction' => null,
    'secondaryActionLabel' => null,
    'breadcrumbs' => [],
])

@php
    $branding = app(\App\Support\SchoolBranding::class)->data();
    $variant = in_array($variant, ['default', 'home'], true) ? $variant : 'default';
    $imageUrl = $image;

    if ($image && ! str($image)->startsWith(['http://', 'https://', '/'])) {
        $imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($image);
    }

    $hasActions = ($action && $actionLabel) || ($secondaryAction && $secondaryActionLabel);
@endphp

<section {{ $attributes->class([
    'relative isolate overflow-hidden border-b border-slate-200/70',
    'bg-slate-950 text-white' => $variant === 'home',
    'bg-slate-50 text-slate-950' => $variant === 'default',
]) }}>
    <div aria-hidden="true" class="absolute inset-0 -z-20"
        @if ($variant === 'home') style="background: linear-gradient(125deg, var(--brand-secondary) 0%, var(--brand-primary) 62%, color-mix(in srgb, var(--brand-primary), white 18%) 100%)" @endif></div>
    <div aria-hidden="true" class="absolute -right-24 -top-36 -z-10 h-96 w-96 rounded-full opacity-25 blur-3xl" style="background: var(--brand-accent)"></div>
    <div aria-hidden="true" class="absolute -bottom-40 -left-28 -z-10 h-80 w-80 rounded-full opacity-20 blur-3xl" style="background: var(--brand-primary)"></div>

    <div @class([
        'mx-auto grid max-w-7xl items-center gap-10 px-4 sm:px-6 lg:px-8',
        'py-16 sm:py-20 lg:grid-cols-12 lg:gap-14 lg:py-28' => $variant === 'home',
        'py-14 sm:py-16 lg:py-20' => $variant === 'default',
        'lg:grid-cols-12' => $variant === 'default' && $imageUrl,
    ])>
        <div @class([
            'relative',
            'lg:col-span-7' => $variant === 'home' || $imageUrl,
            'max-w-3xl' => $variant === 'default' && ! $imageUrl,
        ])>
            @if (filled($breadcrumbs))
                <nav aria-label="Breadcrumb" class="mb-6">
                    <ol class="flex flex-wrap items-center gap-2 text-sm">
                        @foreach ($breadcrumbs as $breadcrumb)
                            <li class="flex items-center gap-2">
                                @if (! $loop->last && filled(data_get($breadcrumb, 'url')))
                                    <a href="{{ data_get($breadcrumb, 'url') }}" @class([
                                        'font-medium transition hover:underline',
                                        'text-white/75 hover:text-white' => $variant === 'home',
                                        'text-slate-500 hover:text-slate-900' => $variant === 'default',
                                    ])>{{ data_get($breadcrumb, 'label') }}</a>
                                    <svg aria-hidden="true" class="h-4 w-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
                                @else
                                    <span aria-current="page" @class(['text-white' => $variant === 'home', 'text-slate-700' => $variant === 'default'])>{{ data_get($breadcrumb, 'label') }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif

            @if ($eyebrow)
                <p @class([
                    'inline-flex items-center rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-[0.18em] ring-1',
                    'bg-white/10 text-white ring-white/20' => $variant === 'home',
                    'bg-white text-[var(--brand-primary)] ring-slate-200 shadow-sm' => $variant === 'default',
                ])>{{ $eyebrow }}</p>
            @endif

            <h1 @class([
                'text-balance font-black tracking-[-0.035em]',
                'mt-6 text-4xl leading-[1.05] sm:text-5xl lg:text-6xl' => $variant === 'home',
                'mt-5 text-4xl leading-tight sm:text-5xl' => $variant === 'default',
            ])>{{ $title }}</h1>

            @if ($description)
                <p @class([
                    'mt-6 max-w-2xl text-pretty leading-8',
                    'text-lg text-white/80 sm:text-xl' => $variant === 'home',
                    'text-lg text-slate-600' => $variant === 'default',
                ])>{{ $description }}</p>
            @endif

            @if ($hasActions)
                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    @if ($action && $actionLabel)
                        <a href="{{ $action }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full px-6 text-sm font-bold shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                            @if ($variant === 'home') style="background-color: var(--brand-accent); color: var(--brand-secondary); outline-color: white" @else style="background-color: var(--brand-primary); color: white; outline-color: var(--brand-primary)" @endif>
                            {{ $actionLabel }}
                            <svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
                        </a>
                    @endif
                    @if ($secondaryAction && $secondaryActionLabel)
                        <a href="{{ $secondaryAction }}" @class([
                            'inline-flex min-h-12 items-center justify-center rounded-full border px-6 text-sm font-bold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
                            'border-white/25 bg-white/5 text-white hover:border-white/40 hover:bg-white/10' => $variant === 'home',
                            'border-slate-300 bg-white text-slate-800 hover:border-slate-400 hover:bg-slate-50' => $variant === 'default',
                        ]) style="outline-color: var(--brand-primary)">{{ $secondaryActionLabel }}</a>
                    @endif
                </div>
            @endif
        </div>

        @if ($imageUrl)
            <div class="relative lg:col-span-5">
                <div aria-hidden="true" class="absolute -inset-3 rotate-2 rounded-[2rem] opacity-30" style="background: var(--brand-accent)"></div>
                <div class="relative overflow-hidden rounded-[1.75rem] bg-slate-200 shadow-2xl ring-1 ring-white/20">
                    <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" width="800" height="640" loading="eager" fetchpriority="high"
                        decoding="async" class="aspect-[5/4] w-full object-cover">
                    <div aria-hidden="true" class="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-slate-950/30 to-transparent"></div>
                </div>
            </div>
        @elseif ($variant === 'home')
            <div class="relative hidden lg:col-span-5 lg:block" aria-hidden="true">
                <div class="aspect-[5/4] rounded-[2rem] border border-white/15 bg-white/10 p-5 shadow-2xl backdrop-blur-sm">
                    <div class="flex h-full flex-col justify-between rounded-2xl border border-white/10 bg-white/5 p-7">
                        <span class="flex h-16 w-16 items-center justify-center rounded-2xl text-xl font-black text-white shadow-lg" style="background: var(--brand-primary)">{{ $branding['initials'] }}</span>
                        <div>
                            <div class="mb-5 h-px bg-gradient-to-r from-[var(--brand-accent)] to-transparent"></div>
                            <p class="text-2xl font-black text-white">{{ $branding['name'] }}</p>
                            <p class="mt-2 text-sm leading-6 text-white/65">{{ $branding['motto'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
