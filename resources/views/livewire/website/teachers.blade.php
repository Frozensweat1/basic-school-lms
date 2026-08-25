<div class="bg-white">
    <x-website.hero
        eyebrow="Meet our faculty"
        :title="$page?->hero_title ?: 'Experienced teachers, inspired learners'"
        :description="$page?->hero_subtitle ?: 'Our educators bring expertise, creativity, high expectations, and genuine care to every classroom.'"
        :image="$page?->hero_image_path ? Storage::disk('public')->url($page->hero_image_path) : null"
        :image-alt="$page?->hero_title ?: 'Teachers at ' . $branding['name']"
        :action="route('website.contact')"
        action-label="Contact the school"
    />

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-website.section-heading
                eyebrow="People who make learning possible"
                title="A team committed to every learner’s growth"
                description="Get to know the featured educators who guide, challenge, encourage, and celebrate our learners."
                align="center"
            />

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($teachers as $teacher)
                    <x-website.teacher-card :teacher="$teacher" />
                @empty
                    <div class="sm:col-span-2 lg:col-span-3">
                        <x-website.empty-state title="Our faculty directory is being updated" description="Please contact the school office if you would like to learn more about our teaching team." :action="route('website.contact')" action-label="Contact the school" />
                    </div>
                @endforelse
            </div>

            @if (method_exists($teachers, 'hasPages') && $teachers->hasPages())
                <div class="mt-10">{{ $teachers->links() }}</div>
            @endif
        </div>
    </section>

    <section class="border-y border-slate-200 bg-slate-50 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['title' => 'Subject expertise', 'description' => 'Strong knowledge and careful planning make complex ideas clear and engaging.'],
                    ['title' => 'Responsive support', 'description' => 'Teachers notice individual needs and work with families to help learners progress.'],
                    ['title' => 'Learning that lasts', 'description' => 'Discussion, practice, feedback, and reflection help learners use what they know.'],
                ] as $item)
                    <article class="rounded-3xl border border-slate-200 bg-white p-7">
                        <h2 class="text-xl font-bold text-slate-950">{{ $item['title'] }}</h2>
                        <p class="mt-3 leading-7 text-slate-600">{{ $item['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <x-website.cta
        eyebrow="A shared commitment"
        title="Great progress starts with a strong school-family partnership"
        description="Ask a question, arrange a visit, and discover how our team will support your child."
        :action="route('website.contact')"
        action-label="Start a conversation"
        :secondary-action="route('website.admissions')"
        secondary-action-label="Explore admissions"
    />
</div>
