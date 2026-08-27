<div class="bg-white">
    <x-website.hero
        variant="home"
        eyebrow="Admissions are open"
        :title="$page?->hero_title ?: $branding['hero_title']"
        :description="$page?->hero_subtitle ?: $branding['hero_subtitle']"
        :image="$page?->hero_image_path ? Storage::disk('public')->url($page->hero_image_path) : null"
        :image-alt="$page?->hero_title ?: $branding['name']"
        :action="route('website.admissions')"
        action-label="Explore admissions"
        :secondary-action="route('website.academics')"
        secondary-action-label="Discover our learning"
    />

    <section aria-label="School at a glance" class="relative z-10 -mt-8 pb-16 sm:-mt-10 sm:pb-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-900/5 sm:grid-cols-3">
                @foreach ($stats as $label => $value)
                    <div class="px-6 py-7 text-center sm:border-r sm:border-slate-200 sm:last:border-r-0">
                        <p class="text-3xl font-black tracking-tight text-slate-950 sm:text-4xl" data-count="{{ $value }}" data-decimals="0">0</p>
                        <p class="mt-1 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <script>
        const animateCounters = () => {
            document.querySelectorAll('[data-count]').forEach((element) => {
                const target = parseInt(element.dataset.count);
                const decimals = parseInt(element.dataset.decimals) || 0;
                let current = 0;
                const increment = Math.ceil(target / 60);

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            const timer = setInterval(() => {
                                current += increment;
                                if (current >= target) {
                                    current = target;
                                    clearInterval(timer);
                                }

                                element.textContent = current.toLocaleString('en-US', {
                                    minimumFractionDigits: decimals,
                                    maximumFractionDigits: decimals,
                                });
                            }, 16);

                            observer.unobserve(entry.target);
                        }
                    });
                });

                observer.observe(element);
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', animateCounters);
        } else {
            animateCounters();
        }
    </script>

    <section class="bg-slate-50 py-16 sm:py-20 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-website.section-heading
                eyebrow="Learning at {{ $branding['name'] }}"
                title="A strong foundation for every stage"
                description="Purposeful programmes combine knowledge, creativity, wellbeing, and the confidence to keep learning."
                align="center"
            />

            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @forelse ($programs as $program)
                    <x-website.program-card :program="$program" />
                @empty
                    <div class="md:col-span-3">
                        <x-website.empty-state title="Programme information is coming soon" description="Contact our school office and we will help you find the right learning stage." />
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:items-end">
                <x-website.section-heading
                    eyebrow="School life"
                    title="Learning continues beyond the classroom"
                    description="Stay connected to the ideas, achievements, and shared moments shaping our school community."
                />
                <div class="flex flex-wrap gap-3 lg:justify-end">
                    <a href="{{ route('website.news') }}" class="website-button website-button-secondary">All school news</a>
                    <a href="{{ route('website.events') }}" class="website-button website-button-secondary">View the calendar</a>
                </div>
            </div>

            <div class="mt-10 grid gap-10 lg:grid-cols-2">
                <div>
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-xl font-bold text-slate-950">Latest stories</h2>
                        <span class="h-px flex-1 bg-slate-200" aria-hidden="true"></span>
                    </div>
                    <div class="mt-5 grid gap-5">
                        @forelse ($articles as $article)
                            <x-website.news-card :post="$article" compact />
                        @empty
                            <x-website.empty-state title="No stories yet" description="Fresh news from our classrooms and community will appear here." />
                        @endforelse
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-xl font-bold text-slate-950">Coming up</h2>
                        <span class="h-px flex-1 bg-slate-200" aria-hidden="true"></span>
                    </div>
                    <div class="mt-5 grid gap-5">
                        @forelse ($events as $event)
                            <x-website.event-card :event="$event" compact />
                        @empty
                            <x-website.empty-state title="No upcoming events" description="New dates and community activities will be shared here." />
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    <x-website.testimonials
        id="testimonials"
        :testimonials="$testimonials"
        title="Families trust the BrightStar journey"
        class="bg-slate-50"
    />

    <x-website.cta
        eyebrow="Start the conversation"
        title="Could this be the right school for your family?"
        description="Learn about admissions, arrange a visit, or ask our team any question about your child’s next step."
        :action="route('website.contact')"
        action-label="Talk to our team"
        :secondary-action="route('website.admissions')"
        secondary-action-label="How to apply"
    />

    <x-website.newsletter-signup />
</div>
