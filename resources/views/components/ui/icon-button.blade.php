@props(['icon', 'label', 'variant' => 'ghost', 'target' => null])

<x-button
    type="button"
    :variant="$variant"
    size="xs"
    :icon="$icon"
    :loading="filled($target)"
    :target="$target"
    aria-label="{{ $label }}"
    title="{{ $label }}"
    {{ $attributes }}
>
    <span class="sr-only">{{ $label }}</span>
</x-button>
