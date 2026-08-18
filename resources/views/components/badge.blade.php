@props([
    'variant' => 'default',
    'size' => 'sm',
])

@php
    $variants = [
        'default' => 'bg-slate-100 text-slate-700',
        'primary' => 'bg-blue-100 text-blue-700',
        'success' => 'bg-emerald-100 text-emerald-700',
        'warning' => 'bg-amber-100 text-amber-700',
        'danger' => 'bg-rose-100 text-rose-700',
        'info' => 'bg-sky-100 text-sky-700',
    ];

    $sizes = [
        'sm' => 'px-2.5 py-1 text-[11px]',
        'md' => 'px-3 py-1.5 text-xs',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full font-semibold ' . ($variants[$variant] ?? $variants['default']) . ' ' . ($sizes[$size] ?? $sizes['sm'])]) }}>
    {{ $slot }}
</span>
