@props(['program'])

@php
    $title = data_get($program, 'title', 'Learning programme');
    $description = data_get($program, 'description');
    $eyebrow = data_get($program, 'eyebrow', 'Learning programme');
    $features = collect(data_get($program, 'features', []))->filter();
    $action = data_get($program, 'action', route('website.contact'));
    $actionLabel = data_get($program, 'action_label', 'Ask about this programme');
@endphp

<article {{ $attributes->class(['group flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl sm:p-8']) }}>
    <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-white shadow-sm" style="background: var(--brand-primary)">
        <svg aria-hidden="true" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.3v13m0-13C10.8 5.5 9.3 5 7.5 5S4.2 5.5 3 6.3v13c1.2-.8 2.8-1.3 4.5-1.3s3.3.5 4.5 1.3m0-13C13.2 5.5 14.8 5 16.5 5s3.3.5 4.5 1.3v13c-1.2-.8-2.8-1.3-4.5-1.3s-3.3.5-4.5 1.3" /></svg>
    </div>
    <p class="mt-6 text-xs font-black uppercase tracking-[0.18em] text-[var(--brand-primary)]">{{ $eyebrow }}</p>
    <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">{{ $title }}</h2>
    @if ($description)
        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $description }}</p>
    @endif
    @if ($features->isNotEmpty())
        <ul class="mt-5 space-y-2.5 text-sm text-slate-700">
            @foreach ($features as $feature)
                <li class="flex items-start gap-2.5">
                    <svg aria-hidden="true" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                    <span>{{ $feature }}</span>
                </li>
            @endforeach
        </ul>
    @endif
    <a href="{{ $action }}" class="mt-auto inline-flex w-fit items-center gap-2 pt-6 text-sm font-bold text-[var(--brand-primary)] transition group-hover:gap-3 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2" style="outline-color: var(--brand-primary)">
        {{ $actionLabel }}
        <svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
    </a>
</article>
