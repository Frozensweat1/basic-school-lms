@php
    $content = $page?->content ?? [];
    $steps = $content['steps'] ?? ['Send an enquiry', 'Visit the school', 'Complete the application', 'Prepare for a confident start'];
    $requirements = $content['requirements'] ?? ['Completed application form', 'Recent learner photograph', 'Birth certificate or valid identification', 'Previous school records, where applicable'];
@endphp

<div class="bg-white">
    <x-website.hero
        eyebrow="Join our community"
        :title="$page?->hero_title ?: 'A clear and welcoming admissions journey'"
        :description="$page?->hero_subtitle ?: 'Our team will help your family understand the school, find the right class, and prepare for a confident start.'"
        :image="$page?->hero_image_path ? Storage::disk('public')->url($page->hero_image_path) : null"
        :image-alt="$page?->hero_title ?: 'Admissions at ' . $branding['name']"
        :action="route('website.contact')"
        action-label="Start an enquiry"
        :secondary-action="$branding['phone'] ? 'tel:' . preg_replace('/[^+0-9]/', '', $branding['phone']) : null"
        secondary-action-label="Call admissions"
    />

    <section class="py-16 sm:py-20 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-[1.15fr_0.85fr] lg:items-start">
                <div>
                    <x-website.section-heading eyebrow="The process" title="Four simple steps to get started" description="We keep families informed at each stage and make space for the questions that matter to you." />
                    <ol class="mt-8 grid gap-5 sm:grid-cols-2">
                        @foreach ($steps as $index => $step)
                            <li class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                                <span class="text-sm font-black uppercase tracking-[0.16em] text-[var(--brand-primary)]">Step {{ $index + 1 }}</span>
                                <h2 class="mt-3 text-lg font-bold text-slate-950">{{ is_array($step) ? ($step['title'] ?? 'Next step') : $step }}</h2>
                                @if (is_array($step) && !empty($step['description']))
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $step['description'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                    @if (!empty($content['body']))
                        <div class="website-prose mt-8">{!! $content['body'] !!}</div>
                    @endif
                </div>

                <aside class="rounded-3xl border border-slate-200 bg-slate-50 p-7 sm:p-8 lg:sticky lg:top-28">
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-[var(--brand-primary)]">Prepare your application</p>
                    <h2 class="mt-3 text-2xl font-bold text-slate-950">What you may need</h2>
                    <ul class="mt-6 space-y-4">
                        @foreach ($requirements as $requirement)
                            <li class="flex gap-3 text-sm leading-6 text-slate-700">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.31a1 1 0 0 1-1.42.003l-3.75-3.75A1 1 0 0 1 5.704 8.85l3.04 3.04 6.543-6.595a1 1 0 0 1 1.417-.005Z" clip-rule="evenodd" /></svg>
                                <span>{{ is_array($requirement) ? ($requirement['title'] ?? '') : $requirement }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('website.contact') }}" class="website-button website-button-primary mt-8 w-full">Send an admissions enquiry</a>
                    <p class="mt-4 text-xs leading-5 text-slate-500">Requirements can vary by entry stage. Our admissions team will confirm exactly what applies to your child.</p>
                </aside>
            </div>
        </div>
    </section>

    <section class="border-t border-slate-200 bg-slate-50 py-14 sm:py-16">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 md:grid-cols-3 lg:px-8">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Call us</p>
                <a href="tel:{{ preg_replace('/[^+0-9]/', '', $branding['phone']) }}" class="mt-2 block font-semibold text-slate-950 hover:text-[var(--brand-primary)]">{{ $branding['phone'] }}</a>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Email admissions</p>
                <a href="mailto:{{ $branding['email'] }}" class="mt-2 block break-all font-semibold text-slate-950 hover:text-[var(--brand-primary)]">{{ $branding['email'] }}</a>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Visit the school</p>
                <p class="mt-2 font-semibold text-slate-950">{{ $branding['address'] }}</p>
            </div>
        </div>
    </section>
</div>
