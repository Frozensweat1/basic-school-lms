@props([
    'label' => 'Label',
    'value' => '0',
    'change' => null,
    'icon' => null,
    'tone' => 'primary',
])

@php
    $tones = [
        'primary' => 'bg-blue-50 text-blue-700',
        'success' => 'bg-emerald-50 text-emerald-700',
        'warning' => 'bg-amber-50 text-amber-700',
        'danger' => 'bg-rose-50 text-rose-700',
        'slate' => 'bg-slate-100 text-slate-700',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $value }}</p>
        </div>

        @if($icon)
            <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $tones[$tone] ?? $tones['primary'] }}">
                {!! $icon !!}
            </div>
        @endif
    </div>

    @if($change)
        <p class="mt-4 text-sm font-medium text-emerald-600">{{ $change }}</p>
    @endif
</div>
