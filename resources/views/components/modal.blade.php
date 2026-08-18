@props([
    'show' => false,
    'title' => null,
    'closeAction' => null,
    'maxWidth' => 'lg',
])

@php
    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-2xl',
        '2xl' => 'max-w-3xl',
    ];
@endphp

@if ($show)
    <div x-data="{}" class="fixed inset-0 z-[100] flex min-h-dvh items-center justify-center overflow-y-auto p-4 sm:p-6"
        style="background-color: rgba(2, 6, 23, 0.74); backdrop-filter: blur(3px);"
        role="dialog"
        aria-modal="true"
        @if($title) aria-labelledby="modal-title" @endif
        {{ $attributes }}>
        <div class="relative my-auto w-full {{ $widths[$maxWidth] ?? $widths['lg'] }} overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/25">
            @if ($title || $closeAction)
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    @if ($title)
                        <h3 id="modal-title" class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
                    @else
                        <span></span>
                    @endif
                    @if ($closeAction)
                        <x-ui.icon-button wire:click="{{ $closeAction }}" icon="close" label="Close dialog" target="{{ $closeAction }}" />
                    @endif
                </div>
            @endif

            <div class="px-6 py-5">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
@endif
