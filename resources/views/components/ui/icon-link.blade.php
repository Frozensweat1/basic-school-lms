@props([
    'href',
    'icon' => 'mail',
    'label',
    'variant' => 'ghost',
])

@php
    $variants = [
        'ghost' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-200',
        'primary' => 'bg-blue-900 text-white hover:bg-blue-800 focus:ring-blue-200 shadow-sm',
    ];
    $icons = [
        'mail' => '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 5h14v10H3V5Z" stroke-linejoin="round"/><path d="m3 6 7 5 7-5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ];
@endphp

<a href="{{ $href }}"
    aria-label="{{ $label }}"
    title="{{ $label }}"
    {{ $attributes->merge(['class' => 'inline-flex cursor-pointer items-center justify-center rounded-xl px-2.5 py-1.5 text-xs font-semibold transition-colors duration-150 focus:outline-none focus:ring-2 '.($variants[$variant] ?? $variants['ghost'])]) }}>
    {!! $icons[$icon] ?? $icon !!}
    <span class="sr-only">{{ $label }}</span>
</a>
