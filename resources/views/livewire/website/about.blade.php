@php
    $content = $page?->content ?? [];
    $values = $content['values'] ?? [
        ['title' => 'Excellence with purpose', 'description' => 'We set ambitious standards while helping every learner understand why their work matters.'],
        ['title' => 'Belonging and care', 'description' => 'Every child deserves to be known, respected, supported, and safe enough to be curious.'],
        ['title' => 'Character in action', 'description' => 'Integrity, empathy, responsibility, and service are practised in everyday school life.'],
    ];
@endphp

<div class="bg-white">
    <x-website.hero
        eyebrow="About our school"
        :title="$page?->hero_title ?: 'A school built around the whole child'"
        :description="$page?->hero_subtitle ?: 'Discover our story, our teaching philosophy, and the community that helps learners thrive.'"
        :image="$page?->hero_image_path ? Storage::disk('public')->url($page->hero_image_path) : null"
        :image-alt="$page?->hero_title ?: $branding['name']"
        :secondary-action="route('website.contact')"
        secondary-action-label="Plan a visit"
    />

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:items-start lg:gap-16 lg:px-8">
            <div>
                <x-website.section-heading eyebrow="Our story" title="A place to be known, challenged, and encouraged" />
                @if (!empty($content['body']))
                    <div class="website-prose mt-6">{!! $content['body'] !!}</div>
                @else
                    <p class="mt-6 text-lg leading-8 text-slate-600">At {{ $branding['name'] }}, education is a partnership between learners, families, and caring professionals. We build strong academic foundations while creating room for questions, creativity, friendship, and meaningful responsibility.</p>
                    <p class="mt-4 leading-7 text-slate-600">Our classrooms are shaped by high expectations and thoughtful support, so each learner can grow in confidence and contribute positively to the world around them.</p>
                @endif
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-1">
                <article class="rounded-3xl border border-slate-200 bg-slate-50 p-7">
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-[var(--brand-primary)]">Our mission</p>
                    <p class="mt-4 text-lg leading-8 text-slate-700">{{ $content['mission'] ?? 'To provide a safe, ambitious learning environment where every child is known, supported, and encouraged to make a positive difference.' }}</p>
                </article>
                <article class="rounded-3xl p-7 text-white shadow-xl" style="background: var(--brand-secondary)">
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-white/70">Our vision</p>
                    <p class="mt-4 text-lg leading-8 text-white/90">{{ $content['vision'] ?? 'Confident, compassionate, and capable learners who are ready to keep growing and lead with purpose.' }}</p>
                </article>
            </div>
        </div>
    </section>

    <section class="border-y border-slate-200 bg-slate-50 py-16 sm:py-20 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-website.section-heading eyebrow="What guides us" title="Values families can see in everyday school life" description="Our values shape the way we teach, learn, collaborate, and care for one another." align="center" />
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ($values as $index => $value)
                    <article class="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl text-sm font-black text-white" style="background: var(--brand-primary)">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <h2 class="mt-6 text-xl font-bold text-slate-950">{{ $value['title'] ?? 'Our value' }}</h2>
                        <p class="mt-3 leading-7 text-slate-600">{{ $value['description'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <x-website.cta
        eyebrow="Meet the people behind the learning"
        title="Dedicated teachers make a lasting difference"
        description="Get to know the educators who create thoughtful, engaging, and supportive learning experiences."
        :action="route('website.teachers')"
        action-label="Meet our teachers"
        :secondary-action="route('website.contact')"
        secondary-action-label="Contact the school"
    />
</div>
