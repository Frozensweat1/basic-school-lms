<div class="bg-white">
    <x-website.hero
        eyebrow="Life at {{ $branding['name'] }}"
        :title="$page?->hero_title ?: 'A community that learns together'"
        :description="$page?->hero_subtitle ?: 'Explore the experiences, celebrations, projects, and everyday moments that make school memorable.'"
        :image="$page?->hero_image_path ? Storage::disk('public')->url($page->hero_image_path) : null"
        :image-alt="$page?->hero_title ?: 'Life at ' . $branding['name']"
    />

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto max-w-7xl space-y-16 px-4 sm:px-6 lg:px-8">
            @forelse ($albums as $album)
                <section aria-labelledby="album-{{ $album->id }}" wire:key="album-{{ $album->id }}">
                    <div class="max-w-2xl">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[var(--brand-primary)]">Photo collection</p>
                        <h2 id="album-{{ $album->id }}" class="mt-2 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">{{ $album->title }}</h2>
                        @if ($album->description)<p class="mt-3 leading-7 text-slate-600">{{ $album->description }}</p>@endif
                    </div>

                    <div class="mt-7 grid auto-rows-[12rem] gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @forelse ($album->images as $index => $image)
                            @php($imageUrl = $image->path ? Storage::disk('public')->url($image->path) : null)
                            <figure wire:key="gallery-image-{{ $image->id }}" class="group relative overflow-hidden rounded-3xl bg-slate-100 {{ $index === 0 ? 'sm:col-span-2 sm:row-span-2 sm:auto-rows-auto' : '' }}">
                                @if ($imageUrl)
                                    <button
                                        type="button"
                                        data-gallery-open
                                        data-gallery-src="{{ $imageUrl }}"
                                        data-gallery-alt="{{ $image->caption ?: $album->title }}"
                                        class="block h-full min-h-48 w-full cursor-zoom-in focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[var(--brand-accent)]"
                                        aria-label="Open {{ $image->caption ?: $album->title }} image"
                                    >
                                        <img
                                            src="{{ $imageUrl }}"
                                            alt="{{ $image->caption ?: $album->title }}"
                                            width="800"
                                            height="600"
                                            loading="lazy"
                                            decoding="async"
                                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                        >
                                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/80 to-transparent p-5 pt-12 text-left text-sm font-semibold text-white opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">{{ $image->caption ?: 'View image' }}</span>
                                    </button>
                                @else
                                    <div class="flex h-full min-h-48 items-center justify-center bg-gradient-to-br from-slate-100 via-white to-amber-50 p-5 text-center text-sm font-semibold text-slate-600">{{ $image->caption ?: $album->title }}</div>
                                @endif
                            </figure>
                        @empty
                            <div class="sm:col-span-2 lg:col-span-4">
                                <x-website.empty-state title="Photos are coming soon" description="This album is ready and its images will appear here once published." />
                            </div>
                        @endforelse
                    </div>
                </section>
            @empty
                <x-website.empty-state title="Our gallery is being prepared" description="Photos from learning and school life will appear here soon." />
            @endforelse

            @if (method_exists($albums, 'hasPages') && $albums->hasPages())
                <div>{{ $albums->links() }}</div>
            @endif
        </div>
    </section>

    <x-website.cta
        eyebrow="Come and see for yourself"
        title="A visit is the best way to experience our community"
        :action="route('website.contact')"
        action-label="Arrange a visit"
        :secondary-action="route('website.admissions')"
        secondary-action-label="Explore admissions"
    />
</div>
