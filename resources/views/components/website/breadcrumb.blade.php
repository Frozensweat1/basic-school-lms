@props(['items' => []])

@if (count($items) > 0)
    <nav aria-label="Breadcrumb" class="flex">
        <ol class="flex flex-wrap items-center gap-2 text-sm">
            @foreach ($items as $index => $item)
                <li class="flex items-center gap-2">
                    @if (isset($item['url']))
                        <a href="{{ $item['url'] }}" class="text-blue-600 hover:text-blue-700 hover:underline">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="text-slate-600" aria-current="page">{{ $item['label'] }}</span>
                    @endif

                    @if ($index < count($items) - 1)
                        <svg class="h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
