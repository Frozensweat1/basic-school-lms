@props(['eyebrow' => null, 'title', 'description' => null, 'action' => null, 'actionLabel' => null])
@php($branding = app(\App\Support\SchoolBranding::class)->data())
<section class="relative overflow-hidden bg-slate-50" style="--brand-primary: {{ $branding['colors']['primary'] }}; --brand-accent: {{ $branding['colors']['accent'] }}">
    <div class="absolute -right-20 -top-24 h-72 w-72 rounded-full opacity-30 blur-3xl" style="background: var(--brand-accent)"></div>
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="relative max-w-3xl">
            @if($eyebrow)<p class="text-sm font-semibold uppercase tracking-[0.2em]" style="color: var(--brand-primary)">{{ $eyebrow }}</p>@endif
            <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-900 sm:text-5xl">{{ $title }}</h1>
            @if($description)<p class="mt-5 max-w-2xl text-lg leading-8 text-slate-600">{{ $description }}</p>@endif
            @if($action && $actionLabel)<a href="{{ $action }}" class="mt-8 inline-flex rounded-full px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90" style="background: var(--brand-primary)">{{ $actionLabel }}</a>@endif
        </div>
    </div>
</section>
