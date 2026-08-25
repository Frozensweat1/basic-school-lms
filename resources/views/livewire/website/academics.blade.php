@php
    $content = $page?->content ?? [];
    $approach = $content['approach'] ?? [
        ['title' => 'Know each learner', 'description' => 'Regular feedback and responsive teaching help every learner make meaningful progress.'],
        ['title' => 'Connect ideas to life', 'description' => 'Projects, discussion, practical work, and technology make learning useful and memorable.'],
        ['title' => 'Build confident habits', 'description' => 'Learners practise curiosity, collaboration, reflection, and responsible independence.'],
    ];
@endphp

<div class="bg-white">
    <x-website.hero
        eyebrow="Learning with purpose"
        :title="$page?->hero_title ?: 'A balanced education for every learner'"
        :description="$page?->hero_subtitle ?: 'Explore a curriculum that brings together strong foundations, creativity, wellbeing, and real-world understanding.'"
        :image="$page?->hero_image_path ? Storage::disk('public')->url($page->hero_image_path) : null"
        :image-alt="$page?->hero_title ?: 'Learners at ' . $branding['name']"
        :action="route('website.contact')"
        action-label="Ask about our curriculum"
    />

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-website.section-heading
                eyebrow="Our programmes"
                title="Learning designed for every stage"
                description="Each programme is age-appropriate, connected to clear outcomes, and enriched by opportunities to explore."
                align="center"
            />

            <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse (($page?->programs ?? []) as $program)
                    <x-website.program-card :program="$program" />
                @empty
                    <div class="md:col-span-2 lg:col-span-3">
                        <x-website.empty-state title="Programme details are being prepared" description="Our school team can share current curriculum and class information with your family." :action="route('website.contact')" action-label="Request information" />
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="border-y border-slate-200 bg-slate-50 py-16 sm:py-20 lg:py-24">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:items-start lg:px-8">
            <div>
                <x-website.section-heading eyebrow="How we teach" title="Thoughtful teaching that turns knowledge into confidence" />
                @if (!empty($content['body']))
                    <div class="website-prose mt-6">{!! $content['body'] !!}</div>
                @else
                    <p class="mt-6 text-lg leading-8 text-slate-600">Great learning is clear, active, and connected. Teachers explain important ideas, invite thoughtful questions, check understanding, and give learners the time and guidance to improve.</p>
                @endif
            </div>

            <div class="grid gap-5">
                @foreach ($approach as $index => $item)
                    <article class="grid grid-cols-[auto_1fr] gap-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl font-black text-white" style="background: var(--brand-primary)">{{ $index + 1 }}</span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-950">{{ $item['title'] ?? 'Our approach' }}</h2>
                            <p class="mt-2 leading-7 text-slate-600">{{ $item['description'] ?? '' }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <x-website.cta
        eyebrow="See learning in context"
        title="Visit the school and experience our classrooms"
        description="A conversation and campus visit can help you understand the right programme for your child."
        :action="route('website.contact')"
        action-label="Arrange a visit"
        :secondary-action="route('website.admissions')"
        secondary-action-label="View admissions"
    />
</div>
