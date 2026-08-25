@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
    'target' => null,
    'text' => null,
    'icon' => null,
])

@php
    $variants = [
        'primary' => 'bg-blue-900 text-white hover:bg-blue-800 focus:ring-blue-200 shadow-sm',
        'secondary' => 'bg-slate-100 text-slate-800 hover:bg-slate-200 focus:ring-slate-200',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-500 focus:ring-emerald-200',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-500 focus:ring-rose-200',
        'ghost' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-200',
    ];

    $sizes = [
        'xs' => 'px-2.5 py-1.5 text-xs',
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-5 py-3 text-base',
    ];

    $loadingTarget = $loading ? (filled($target) ? $target : null) : null;
    $baseClasses = 'inline-flex flex-nowrap cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-xl font-semibold transition-colors duration-150 focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60';
    $icons = [
        'plus' => '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 4v12M4 10h12" stroke-linecap="round"/></svg>',
        'edit' => '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m13.5 3.5 3 3M4 16l3.4-.8L16.5 6a2.1 2.1 0 0 0-3-3l-9.1 9.2L4 16Z" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'trash' => '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h12m-8 3v5m4-5v5M7 5l.7-2h4.6l.7 2m-8 0 .7 11h8.6L15 5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'save' => '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 3h10l2 2v12H4V3Zm3 0v5h6V3m-6 14v-5h6v5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'close' => '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 5 10 10M15 5 5 15" stroke-linecap="round"/></svg>',
        'sparkles' => '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m10 2 1.2 4.3L15.5 7.5l-4.3 1.2L10 13l-1.2-4.3L4.5 7.5l4.3-1.2L10 2Zm5 10 .7 2.3L18 15l-2.3.7L15 18l-.7-2.3L12 15l2.3-.7L15 12Z" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'play' => '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m7 5 7 5-7 5V5Z" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ];
    $iconMarkup = $icons[$icon] ?? $icon;
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md'])
    ]) }}
    @if($loadingTarget) wire:loading.attr="disabled" wire:target="{{ $loadingTarget }}" @endif
    @if($loadingTarget) wire:loading.class="opacity-75" @endif
>
    @if($loadingTarget)
        <span wire:loading.flex wire:target="{{ $loadingTarget }}" class="w-full flex-nowrap items-center justify-center gap-2 whitespace-nowrap">
            <svg class="h-4 w-4 shrink-0 animate-spin" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"></circle>
                <path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
            </svg>
            <span class="truncate">{{ $text ?? $slot }}</span>
        </span>
        <span wire:loading.remove wire:target="{{ $loadingTarget }}" class="inline-flex flex-nowrap items-center gap-2 whitespace-nowrap">
            @if($iconMarkup)
                {!! $iconMarkup !!}
            @endif
            <span>{{ $text ?? $slot }}</span>
        </span>
    @else
        @if($iconMarkup)
            {!! $iconMarkup !!}
        @endif
        <span>{{ $text ?? $slot }}</span>
    @endif
</button>
