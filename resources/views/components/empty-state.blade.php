@props([
    'title' => 'No items found',
    'description' => null,
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center']) }}>
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="M6 8.5V6.8A2.8 2.8 0 018.8 4h6.4A2.8 2.8 0 0118 6.8v1.7m-12 0h12a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6a2 2 0 012-2z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    <h3 class="mt-5 text-lg font-semibold text-slate-900">{{ $title }}</h3>
    @if($description)
        <p class="mt-2 text-sm text-slate-500">{{ $description }}</p>
    @endif

    @if($action)
        <div class="mt-5 flex justify-center">
            {!! $action !!}
        </div>
    @endif
</div>
