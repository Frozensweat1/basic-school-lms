@props([
    'id' => null,
    'title',
    'subtitle' => null,
    'config',
    'emptyMessage' => 'There is not enough data to display this chart yet.',
])

@php
    $chartId = $id ?: 'dashboard-chart-'.Illuminate\Support\Str::slug($title).'-'.substr(md5(json_encode($config)), 0, 8);
    $values = collect(data_get($config, 'data.datasets', []))->flatMap(fn ($dataset) => data_get($dataset, 'data', []));
    $hasData = data_get($config, 'meta.hasData');
    $hasData ??= $values->contains(fn ($value) => $value !== null && is_numeric($value));
@endphp

<section {{ $attributes->class('rounded-2xl border border-slate-200 bg-white p-5 shadow-sm') }} data-dashboard-chart-container>
    <div>
        <h2 class="font-bold text-slate-900">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($hasData)
        <div class="relative mt-5 h-72">
            <canvas
                id="{{ $chartId }}"
                data-dashboard-chart
                role="img"
                aria-label="{{ $title }}{{ $subtitle ? '. '.$subtitle : '' }}"
            ></canvas>
        </div>
        <script type="application/json" data-dashboard-chart-config>{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @else
        <div class="mt-5 flex h-72 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 text-center text-sm text-slate-500">
            {{ $emptyMessage }}
        </div>
    @endif
</section>
