@props([
    'placeholder' => 'Write content…',
    'minHeight' => '16rem',
])

@php
    $wireModel = $attributes->wire('model');
@endphp

<div
    x-data="richTextEditor(@entangle($wireModel), { placeholder: @js($placeholder) })"
    wire:ignore
    {{ $attributes->except('wire:model')->class('rounded-lg border border-slate-300 bg-white focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-100') }}
>
    <div x-ref="editor" style="min-height: {{ $minHeight }}"></div>
</div>
