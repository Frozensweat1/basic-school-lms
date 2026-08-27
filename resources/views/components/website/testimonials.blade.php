@props(['testimonials' => [], 'title' => 'What our community says'])

<section {{ $attributes->class('relative overflow-hidden bg-white py-16 sm:py-20 lg:py-24') }}>
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-slate-100 to-transparent"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700">Testimonials</p>
            <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">{{ $title }}</h2>
            <p class="mt-4 text-sm leading-6 text-slate-600 sm:text-base">Real experiences from families and learners in our school community.</p>
        </div>

        @if (count($testimonials) > 0)
            <div class="-mx-4 mt-12 sm:mx-0" data-testimonials-root>
                <div data-testimonials-track class="flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-2 sm:px-0 md:grid md:grid-cols-2 md:gap-5 md:overflow-visible md:px-0 md:pb-0 xl:grid-cols-3">
                @foreach ($testimonials as $index => $testimonial)
                    @php
                        $rating = max(1, min(5, (int) ($testimonial['rating'] ?? 5)));
                        $isFeatured = $index === 0;
                        $role = trim((string) ($testimonial['role'] ?? 'Parent'));
                        $roleClass = match (strtolower($role)) {
                            'alumnus', 'alumni' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                            'student' => 'bg-violet-50 text-violet-700 ring-violet-200',
                            'teacher' => 'bg-amber-50 text-amber-700 ring-amber-200',
                            default => 'bg-sky-50 text-sky-700 ring-sky-200',
                        };
                    @endphp

                    <article data-testimonial-card style="--reveal-delay: {{ $index * 90 }}ms" class="group relative flex min-w-[84%] snap-start flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-xl hover:shadow-slate-900/5 sm:min-w-[62%] md:min-w-0 motion-safe:animate-[testimonial-reveal_620ms_ease-out_both] [animation-delay:var(--reveal-delay)] {{ $isFeatured ? 'md:col-span-2 xl:col-span-1 xl:row-span-2 xl:p-8' : '' }}">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-1" aria-label="{{ $rating }} out of 5 stars">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="h-4 w-4 {{ $i <= $rating ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.14em] text-slate-600">Verified</span>
                        </div>

                        <blockquote class="flex-1">
                            <p class="text-sm leading-7 text-slate-700 sm:text-base">&ldquo;{{ $testimonial['text'] }}&rdquo;</p>
                        </blockquote>

                        <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-4">
                            @if (! empty($testimonial['avatar']))
                                <img src="{{ $testimonial['avatar'] }}" alt="{{ $testimonial['author'] }}" class="h-11 w-11 rounded-full object-cover ring-2 ring-slate-100" loading="lazy" />
                            @else
                                <div class="flex h-11 w-11 items-center justify-center rounded-full text-sm font-black text-white" style="background: var(--brand-primary);">
                                    <span>{{ strtoupper(substr($testimonial['author'], 0, 1)) }}</span>
                                </div>
                            @endif

                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-900">{{ $testimonial['author'] }}</p>
                                <div class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ $roleClass }}">{{ $role }}</div>
                            </div>
                        </div>
                    </article>
                @endforeach
                </div>

                @if (count($testimonials) > 1)
                    <div class="mt-5 flex items-center justify-center gap-2 md:hidden">
                        @foreach ($testimonials as $index => $testimonial)
                            <button
                                type="button"
                                data-testimonial-dot="{{ $index }}"
                                class="h-2.5 w-2.5 rounded-full bg-slate-300 transition aria-[current=true]:w-6 aria-[current=true]:bg-slate-900"
                                aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                                aria-label="Show testimonial {{ $index + 1 }}"
                            ></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <p class="mt-8 text-center text-slate-600">No testimonials available yet.</p>
        @endif
    </div>

    <style>
        @keyframes testimonial-reveal {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        [data-testimonials-track] {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        [data-testimonials-track]::-webkit-scrollbar {
            display: none;
        }

        [data-testimonials-track].is-dragging {
            cursor: grabbing;
            user-select: none;
        }
    </style>

    <script>
        (() => {
            if (window.__testimonialsEnhancedInit) return;
            window.__testimonialsEnhancedInit = true;

            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

            const bindTrack = (track) => {
                if (!track || track.dataset.carouselBound === '1') return;
                track.dataset.carouselBound = '1';

                const root = track.closest('[data-testimonials-root]');
                const dots = root ? Array.from(root.querySelectorAll('[data-testimonial-dot]')) : [];
                const cards = () => Array.from(track.querySelectorAll('[data-testimonial-card]'));
                const canCarousel = () => window.matchMedia('(max-width: 1023px)').matches && cards().length > 1;

                let isDown = false;
                let startX = 0;
                let startScrollLeft = 0;
                let autoTimer = null;
                let currentIndex = 0;
                let pauseCount = 0;

                const setActiveDot = (index) => {
                    dots.forEach((dot, dotIndex) => {
                        dot.setAttribute('aria-current', dotIndex === index ? 'true' : 'false');
                    });
                };

                const indexFromScroll = () => {
                    const cardItems = cards();
                    if (cardItems.length === 0) return 0;

                    const left = track.scrollLeft;
                    let nearestIndex = 0;
                    let nearestDistance = Number.POSITIVE_INFINITY;

                    cardItems.forEach((card, index) => {
                        const distance = Math.abs(card.offsetLeft - left);
                        if (distance < nearestDistance) {
                            nearestDistance = distance;
                            nearestIndex = index;
                        }
                    });

                    return nearestIndex;
                };

                const goTo = (index, smooth = true) => {
                    const cardItems = cards();
                    if (cardItems.length === 0) return;

                    const safeIndex = Math.max(0, Math.min(index, cardItems.length - 1));
                    currentIndex = safeIndex;
                    setActiveDot(safeIndex);
                    track.scrollTo({
                        left: cardItems[safeIndex].offsetLeft,
                        behavior: smooth && !reduceMotion.matches ? 'smooth' : 'auto',
                    });
                };

                const stopAuto = () => {
                    if (autoTimer) {
                        clearInterval(autoTimer);
                        autoTimer = null;
                    }
                };

                const startAuto = () => {
                    stopAuto();
                    if (pauseCount > 0 || !canCarousel() || reduceMotion.matches) return;

                    autoTimer = setInterval(() => {
                        const cardItems = cards();
                        if (cardItems.length < 2) return;
                        goTo((currentIndex + 1) % cardItems.length);
                    }, 5500);
                };

                const pauseAuto = () => {
                    pauseCount += 1;
                    stopAuto();
                };

                const resumeAuto = () => {
                    pauseCount = Math.max(0, pauseCount - 1);
                    if (pauseCount === 0) startAuto();
                };

                const onPointerDown = (event) => {
                    if (!canCarousel()) return;
                    isDown = true;
                    startX = event.clientX;
                    startScrollLeft = track.scrollLeft;
                    track.classList.add('is-dragging');
                    pauseAuto();

                    if (track.setPointerCapture) {
                        track.setPointerCapture(event.pointerId);
                    }
                };

                const onPointerMove = (event) => {
                    if (!isDown || !canCarousel()) return;
                    const walk = event.clientX - startX;
                    track.scrollLeft = startScrollLeft - walk;
                };

                const onPointerUp = () => {
                    if (!isDown) return;
                    isDown = false;
                    track.classList.remove('is-dragging');
                    currentIndex = indexFromScroll();
                    setActiveDot(currentIndex);
                    resumeAuto();
                };

                track.addEventListener('pointerdown', onPointerDown);
                track.addEventListener('pointermove', onPointerMove);
                track.addEventListener('pointerup', onPointerUp);
                track.addEventListener('pointercancel', onPointerUp);
                track.addEventListener('pointerleave', onPointerUp);
                track.addEventListener('mouseenter', pauseAuto);
                track.addEventListener('mouseleave', resumeAuto);
                track.addEventListener('focusin', pauseAuto);
                track.addEventListener('focusout', resumeAuto);
                track.addEventListener('scroll', () => {
                    if (!canCarousel()) return;
                    currentIndex = indexFromScroll();
                    setActiveDot(currentIndex);
                }, { passive: true });

                dots.forEach((dot, index) => {
                    dot.addEventListener('click', () => {
                        pauseAuto();
                        goTo(index);
                        setTimeout(() => resumeAuto(), 1200);
                    });
                });

                goTo(0, false);
                startAuto();
            };

            const init = () => {
                document.querySelectorAll('[data-testimonials-track]').forEach(bindTrack);
            };

            document.addEventListener('livewire:navigated', init);
            document.addEventListener('DOMContentLoaded', init);
            init();
        })();
    </script>
</section>
