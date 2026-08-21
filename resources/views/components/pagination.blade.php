@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between" aria-label="Pagination">
        <p class="text-sm text-slate-600">
            Showing <span class="font-semibold text-slate-800">{{ $paginator->firstItem() }}</span>
            to <span class="font-semibold text-slate-800">{{ $paginator->lastItem() }}</span>
            of <span class="font-semibold text-slate-800">{{ $paginator->total() }}</span> results
        </p>

        <div class="[&>nav]:justify-start sm:[&>nav]:justify-end">
            {{ $paginator->onEachSide(1)->links() }}
        </div>
    </nav>
@endif
