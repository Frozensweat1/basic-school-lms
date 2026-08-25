<div class="bg-white">
    <x-website.hero
        :eyebrow="$branding['name'] . ' stories'"
        :title="$page?->hero_title ?: 'News from our school community'"
        :description="$page?->hero_subtitle ?: 'Highlights, ideas, achievements, and useful updates from across school life.'"
        :image="$page?->hero_image_path ? Storage::disk('public')->url($page->hero_image_path) : null"
        :image-alt="$page?->hero_title ?: 'News from ' . $branding['name']"
    />

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <x-website.section-heading eyebrow="Latest updates" title="Stories worth sharing" description="Follow the learning, leadership, creativity, and community moments happening at our school." />
                <a href="{{ route('website.events') }}" class="website-button website-button-secondary shrink-0">Upcoming events</a>
            </div>

            <div class="mt-10 grid gap-7 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($posts as $post)
                    <x-website.news-card :post="$post" />
                @empty
                    <div class="md:col-span-2 lg:col-span-3">
                        <x-website.empty-state title="No published stories yet" description="School news and learner achievements will appear here as soon as they are published." />
                    </div>
                @endforelse
            </div>

            @if ($posts->hasPages())
                <div class="mt-12">{{ $posts->links() }}</div>
            @endif
        </div>
    </section>

    <x-website.cta
        eyebrow="Stay connected"
        title="There is always more to discover"
        description="Explore upcoming events or contact our school team for the information your family needs."
        :action="route('website.events')"
        action-label="View upcoming events"
        :secondary-action="route('website.contact')"
        secondary-action-label="Contact us"
    />
</div>
