@props(['branding' => [], 'sitemap' => []])

<footer class="border-t border-slate-200 bg-slate-900 text-slate-200">
    <!-- Newsletter signup -->
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-8 md:grid-cols-2 md:gap-12">
            <div>
                <h3 class="text-xl font-black text-white">{{ $branding['name'] ?? 'Our School' }}</h3>
                <p class="mt-2 text-sm leading-relaxed">{{ $branding['tagline'] ?? 'Building leaders and thinkers, today.' }}</p>
            </div>

            <form wire:submit="subscribeNewsletter" class="flex flex-col gap-2 sm:flex-row" novalidate>
                <input
                    type="email"
                    wire:model.blur="newsletterEmail"
                    placeholder="Your email for updates"
                    class="min-w-0 flex-1 rounded-lg border-0 bg-white px-4 py-2.5 text-slate-950 placeholder-slate-500 text-sm focus:ring-2 focus:ring-[var(--brand-accent)]"
                    required
                />
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="subscribeNewsletter"
                    class="rounded-lg bg-[var(--brand-primary)] px-5 py-2.5 font-bold text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="subscribeNewsletter">Subscribe</span>
                    <span wire:loading.flex wire:target="subscribeNewsletter">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" opacity="0.25"/><path d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" opacity="0.75"/></svg>
                    </span>
                </button>
            </form>
        </div>
    </div>

    <!-- Divider -->
    <div class="border-t border-slate-700"></div>

    <!-- Footer links and info -->
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-8 md:grid-cols-4">
            <!-- Quick links -->
            <div>
                <h4 class="font-bold text-white">Explore</h4>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="hover:text-white transition">Home</a></li>
                    <li><a href="{{ route('website.academics') }}" class="hover:text-white transition">Academics</a></li>
                    <li><a href="{{ route('website.admissions') }}" class="hover:text-white transition">Admissions</a></li>
                    <li><a href="{{ route('website.news') }}" class="hover:text-white transition">News</a></li>
                </ul>
            </div>

            <!-- About links -->
            <div>
                <h4 class="font-bold text-white">About</h4>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a href="{{ route('website.about') }}" class="hover:text-white transition">About Us</a></li>
                    <li><a href="{{ route('website.events') }}" class="hover:text-white transition">Events</a></li>
                    <li><a href="{{ route('website.gallery') }}" class="hover:text-white transition">Gallery</a></li>
                    <li><a href="{{ route('website.contact') }}" class="hover:text-white transition">Contact</a></li>
                </ul>
            </div>

            <!-- Legal links -->
            <div>
                <h4 class="font-bold text-white">Legal</h4>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a href="#" class="hover:text-white transition">Privacy Policy</a></li>
                    <li><a href="#" class="hover:text-white transition">Terms of Service</a></li>
                    <li><a href="#" class="hover:text-white transition">Cookie Policy</a></li>
                </ul>
            </div>

            <!-- Contact info -->
            <div>
                <h4 class="font-bold text-white">Get in Touch</h4>
                <div class="mt-4 space-y-2 text-sm">
                    @if ($branding['phone'])
                        <p><a href="tel:{{ $branding['phone'] }}" class="hover:text-white transition">{{ $branding['phone'] }}</a></p>
                    @endif
                    @if ($branding['email'])
                        <p><a href="mailto:{{ $branding['email'] }}" class="hover:text-white transition">{{ $branding['email'] }}</a></p>
                    @endif
                    @if ($branding['address'])
                        <p>{{ $branding['address'] }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom bar -->
    <div class="border-t border-slate-700">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-6 sm:px-6 lg:px-8 lg:flex-row">
            <p class="text-xs text-slate-400">&copy; {{ now()->year }} {{ $branding['name'] ?? 'School Name' }}. All rights reserved.</p>
            <div class="flex gap-4">
                @if ($branding['social_links'])
                    @forelse (data_get($branding, 'social_links', []) as $platform => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-slate-700 transition" aria-label="Visit us on {{ $platform }}">
                            @switch($platform)
                                @case('facebook')
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                @break
                                @case('twitter')
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2s9 5 20 5a9.5 9.5 0 00-9-5.5c4.75 2.25 7-7 7-7s1.5 5.34-5.5 9"/></svg>
                                @break
                                @case('linkedin')
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"/></svg>
                                @break
                                @case('instagram')
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z" fill="white"/><circle cx="17.5" cy="6.5" r="1.5" fill="white"/></svg>
                                @break
                                @default
                                    <span>{{ $platform }}</span>
                            @endswitch
                        </a>
                    @empty
                    @endforelse
                @endif
            </div>
        </div>
    </div>
</footer>
