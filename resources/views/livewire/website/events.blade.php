<div class="bg-white">
    <x-website.hero
        eyebrow="Be part of the moment"
        :title="$page?->hero_title ?: 'Upcoming events'"
        :description="$page?->hero_subtitle ?: 'Discover the learning, celebration, and community moments happening at our school.'"
        :image="$page?->hero_image_path ? Storage::disk('public')->url($page->hero_image_path) : null"
        :image-alt="$page?->hero_title ?: 'Events at ' . $branding['name']"
        :action="route('website.contact')"
        action-label="Ask about an event"
    />

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-website.section-heading eyebrow="Mark your calendar" title="What is coming up" description="Families and friends are welcome to join the moments that bring our community together." />

            <div class="mt-10 grid gap-6 lg:grid-cols-2">
                @forelse ($upcoming as $event)
                    <x-website.event-card :event="$event" />
                @empty
                    <div class="lg:col-span-2">
                        <x-website.empty-state title="No upcoming events have been published" description="Please check back soon or contact the school office for the latest calendar." :action="route('website.contact')" action-label="Contact the school" />
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    @if ($past->isNotEmpty())
        <section class="border-t border-slate-200 bg-slate-50 py-16 sm:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-website.section-heading eyebrow="Recently held" title="Moments from our recent calendar" />
                <div class="mt-8 divide-y divide-slate-200 overflow-hidden rounded-3xl border border-slate-200 bg-white">
                    @foreach ($past as $event)
                        <article class="grid gap-3 p-6 sm:grid-cols-[9rem_1fr_auto] sm:items-center">
                            <time datetime="{{ $event->starts_at->toAtomString() }}" class="text-sm font-bold text-[var(--brand-primary)]">{{ $event->starts_at->format('d M Y') }}</time>
                            <div>
                                <h2 class="font-bold text-slate-950">{{ $event->title }}</h2>
                                @if ($event->location)<p class="mt-1 text-sm text-slate-500">{{ $event->location }}</p>@endif
                            </div>
                            <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Completed</span>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-website.cta
        eyebrow="Need more information?"
        title="Our school office will be happy to help"
        description="Ask about attendance, timings, accessibility, or anything else you need before an event."
        :action="route('website.contact')"
        action-label="Contact the office"
        :secondary-action="route('website.gallery')"
        secondary-action-label="See school life"
    />
</div>
