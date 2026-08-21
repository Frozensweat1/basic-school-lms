<div class="bg-white">
    <x-website.hero eyebrow="Life at {{ app(\App\Support\SchoolBranding::class)->data()['name'] }}" title="A community that learns together" description="A glimpse of the experiences that make school memorable." />
    <div class="mx-auto max-w-7xl space-y-14 px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        @forelse($albums as $album)
            <section wire:key="album-{{ $album->id }}"><h2 class="text-2xl font-bold text-slate-900">{{ $album->title }}</h2><p class="mt-2 text-slate-600">{{ $album->description }}</p><div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">@foreach($album->images as $image)<figure wire:key="image-{{ $image->id }}" class="overflow-hidden rounded-2xl bg-slate-100 shadow-sm">@if($image->path)<img src="{{ Storage::disk('public')->url($image->path) }}" alt="{{ $image->caption ?: $album->title }}" class="aspect-[4/3] w-full object-cover">@else<div class="flex aspect-[4/3] items-center justify-center bg-gradient-to-br from-blue-100 via-sky-100 to-amber-100 text-sm font-semibold text-slate-700">{{ $image->caption ?: $album->title }}</div>@endif @if($image->caption)<figcaption class="p-3 text-sm text-slate-600">{{ $image->caption }}</figcaption>@endif</figure>@endforeach</div></section>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-600">Gallery albums will appear here soon.</div>
        @endforelse
    </div>
</div>
