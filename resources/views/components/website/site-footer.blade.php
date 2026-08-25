@props([
    'branding',
    'navigation' => [],
])

@php
    $phoneHref = preg_replace('/[^+\d]/', '', (string) $branding['phone']);
    $footerLinks = collect($navigation)->reject(fn ($item) => in_array($item['route'], ['home', 'website.contact'], true));
@endphp

<footer class="relative overflow-hidden bg-slate-950 text-slate-300">
    <div aria-hidden="true" class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[var(--brand-accent)] to-transparent opacity-70"></div>
    <div aria-hidden="true" class="absolute -right-32 -top-32 h-80 w-80 rounded-full opacity-10 blur-3xl" style="background: var(--brand-primary)"></div>

    <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 sm:py-16 lg:grid-cols-12 lg:px-8">
        <div class="lg:col-span-5">
            <x-website.brand-mark :branding="$branding" inverse class="max-w-sm" />
            <p class="mt-5 max-w-md text-sm leading-7 text-slate-400">{{ $branding['footer_text'] }}</p>
            <x-website.social-links :socials="$branding['socials']" inverse class="mt-6" />
        </div>

        <div class="sm:grid sm:grid-cols-2 sm:gap-8 lg:col-span-7 lg:grid-cols-2">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-white">Explore</h2>
                <ul class="mt-5 grid grid-cols-2 gap-x-5 gap-y-3 text-sm sm:grid-cols-1">
                    @foreach ($footerLinks as $item)
                        <li><a href="{{ route($item['route']) }}" class="transition hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4" style="outline-color: var(--brand-accent)">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="mt-10 sm:mt-0">
                <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-white">Contact</h2>
                <address class="mt-5 space-y-4 text-sm not-italic leading-6 text-slate-400">
                    @if ($branding['address'])
                        <p class="flex items-start gap-3">
                            <svg aria-hidden="true" class="mt-0.5 h-5 w-5 shrink-0 text-[var(--brand-accent)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.1 7-12A7 7 0 1 0 5 9c0 6.9 7 12 7 12Z" /><circle cx="12" cy="9" r="2.5" /></svg>
                            <span>{{ $branding['address'] }}</span>
                        </p>
                    @endif
                    @if ($branding['phone'])
                        <p class="flex items-center gap-3">
                            <svg aria-hidden="true" class="h-5 w-5 shrink-0 text-[var(--brand-accent)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.8 4.6c0-.9.7-1.6 1.6-1.6h2.7c.7 0 1.3.5 1.5 1.1l.8 3a1.6 1.6 0 0 1-.6 1.7l-1.5 1.1a13 13 0 0 0 6.8 6.8l1.1-1.5a1.6 1.6 0 0 1 1.7-.6l3 .8c.7.2 1.1.8 1.1 1.5v2.7c0 .9-.7 1.6-1.6 1.6h-.8A15.8 15.8 0 0 1 2.8 5.4v-.8Z" /></svg>
                            <a href="tel:{{ $phoneHref }}" class="break-words transition hover:text-white">{{ $branding['phone'] }}</a>
                        </p>
                    @endif
                    @if ($branding['email'])
                        <p class="flex items-center gap-3">
                            <svg aria-hidden="true" class="h-5 w-5 shrink-0 text-[var(--brand-accent)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6.8A2.8 2.8 0 0 1 5.8 4h12.4A2.8 2.8 0 0 1 21 6.8v10.4a2.8 2.8 0 0 1-2.8 2.8H5.8A2.8 2.8 0 0 1 3 17.2V6.8Z" /><path stroke-linecap="round" stroke-linejoin="round" d="m4 6 8 6 8-6" /></svg>
                            <a href="mailto:{{ $branding['email'] }}" class="break-all transition hover:text-white">{{ $branding['email'] }}</a>
                        </p>
                    @endif
                </address>
                <a href="{{ route('website.contact') }}" class="mt-6 inline-flex min-h-11 items-center gap-2 rounded-full border border-white/15 px-5 text-sm font-bold text-white transition hover:border-white/30 hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4" style="outline-color: var(--brand-accent)">
                    Contact the school
                    <svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
                </a>
            </div>
        </div>
    </div>

    <div class="relative border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <p>&copy; {{ now()->year }} {{ $branding['name'] }}. All rights reserved.</p>
            <p>Learning, character, and community.</p>
        </div>
    </div>
</footer>
