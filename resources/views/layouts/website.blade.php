@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'canonical' => null,
    'robots' => 'index, follow',
    'structuredData' => [],
    'pageTitle' => null,
    'pageDescription' => null,
    'canonicalUrl' => null,
    'canonicalPath' => null,
    'socialImage' => null,
])

@php
    $branding = app(\App\Support\SchoolBranding::class)->data();
    $routeName = request()->route()?->getName();
    $pageMetadata = [
        'home' => ['title' => $branding['name'], 'description' => $branding['hero_subtitle']],
        'website.about' => ['title' => 'About us', 'description' => "Learn about {$branding['name']}, our mission, values, and learning community."],
        'website.academics' => ['title' => 'Academics', 'description' => "Explore the academic programmes and learning experience at {$branding['name']}."],
        'website.admissions' => ['title' => 'Admissions', 'description' => "Learn how to join {$branding['name']} and begin your child's learning journey."],
        'website.teachers' => ['title' => 'Our teachers', 'description' => "Meet the experienced and caring educators at {$branding['name']}."],
        'website.news' => ['title' => 'School news', 'description' => "Read the latest stories, achievements, and updates from {$branding['name']}."],
        'website.events' => ['title' => 'Events', 'description' => "Discover upcoming events and important dates at {$branding['name']}."],
        'website.gallery' => ['title' => 'Gallery', 'description' => "See learning, creativity, and community life at {$branding['name']}."],
        'website.contact' => ['title' => 'Contact us', 'description' => "Contact {$branding['name']} for admissions and general enquiries."],
    ];
    $routeMetadata = $pageMetadata[$routeName] ?? [];
    $seoTitle = $pageTitle ?: $title ?: ($routeMetadata['title'] ?? $branding['name']);
    $documentTitle = $routeName === 'home' || $seoTitle === $branding['name']
        ? $branding['name']
        : "{$seoTitle} | {$branding['name']}";
    $seoDescription = str($pageDescription ?: $description ?: ($routeMetadata['description'] ?? $branding['motto']))->squish()->limit(160, '')->toString();
    $canonicalUrl = $canonicalUrl ?: $canonical ?: ($canonicalPath ? url($canonicalPath) : url()->current());
    $socialImage = $socialImage ?: $image ?: $branding['logo_url'];
    $phoneHref = preg_replace('/[^+\d]/', '', (string) $branding['phone']);
    $navigation = [
        ['label' => 'Home', 'route' => 'home', 'footer' => false],
        ['label' => 'About', 'route' => 'website.about'],
        ['label' => 'Academics', 'route' => 'website.academics'],
        ['label' => 'Admissions', 'route' => 'website.admissions'],
        ['label' => 'Teachers', 'route' => 'website.teachers'],
        ['label' => 'News', 'route' => 'website.news'],
        ['label' => 'Events', 'route' => 'website.events'],
        ['label' => 'Gallery', 'route' => 'website.gallery'],
        ['label' => 'Contact', 'route' => 'website.contact', 'footer' => false],
    ];
    $utilityLinks = [
        ['label' => 'News', 'route' => 'website.news'],
        ['label' => 'Events', 'route' => 'website.events'],
        ['label' => 'Contact', 'route' => 'website.contact'],
    ];
    $sameAs = collect($branding['socials'])->filter()->values()->all();
    $schoolSchema = array_filter([
        '@type' => 'School',
        'name' => $branding['name'],
        'description' => $branding['motto'],
        'url' => route('home'),
        'logo' => $branding['logo_url'],
        'email' => $branding['email'],
        'telephone' => $branding['phone'],
        'address' => $branding['address'],
        'sameAs' => $sameAs ?: null,
    ], fn ($value) => filled($value));
    $additionalSchemas = filled($structuredData)
        ? (array_is_list($structuredData) ? $structuredData : [$structuredData])
        : [];
    $schemaGraph = collect([$schoolSchema, ...$additionalSchemas])
        ->filter(fn ($schema) => is_array($schema) && filled($schema))
        ->map(function (array $schema): array {
            unset($schema['@context']);

            return $schema;
        })
        ->values()
        ->all();
    $structuredDataPayload = ['@context' => 'https://schema.org', '@graph' => $schemaGraph];
@endphp

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="{{ $branding['colors']['primary'] }}">
        <meta name="color-scheme" content="light">
        <meta name="robots" content="{{ $robots }}">
        <meta name="description" content="{{ $seoDescription }}">
        <meta property="og:site_name" content="{{ $branding['name'] }}">
        <meta property="og:type" content="{{ $type }}">
        <meta property="og:title" content="{{ $documentTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta name="twitter:card" content="{{ $socialImage ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $documentTitle }}">
        <meta name="twitter:description" content="{{ $seoDescription }}">
        @if ($socialImage)
            <meta property="og:image" content="{{ $socialImage }}">
            <meta property="og:image:alt" content="{{ $branding['name'] }}">
            <meta name="twitter:image" content="{{ $socialImage }}">
        @endif
        @if ($branding['logo_url'])
            <link rel="icon" href="{{ $branding['logo_url'] }}">
            <link rel="apple-touch-icon" href="{{ $branding['logo_url'] }}">
        @endif
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <title>{{ $documentTitle }}</title>
        <style>
            :root {
                --brand-primary: {{ $branding['colors']['primary'] }};
                --brand-secondary: {{ $branding['colors']['secondary'] }};
                --brand-accent: {{ $branding['colors']['accent'] }};
            }
        </style>
        <script type="application/ld+json">{!! json_encode($structuredDataPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/website.js'])
    </head>
    <body class="website-shell flex min-h-dvh flex-col bg-white text-slate-900 antialiased">
        <a href="#website-main" class="website-skip-link">Skip to main content</a>

        <header data-website-header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 shadow-sm shadow-slate-950/5 backdrop-blur-xl">
            <div class="hidden border-b border-slate-200/70 bg-slate-50/70 lg:block">
                <div class="mx-auto flex h-10 max-w-7xl items-center justify-between gap-4 px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-4 text-xs font-medium text-slate-600">
                        @if ($branding['phone'])
                            <a href="tel:{{ $phoneHref }}" class="truncate transition hover:text-slate-900">{{ $branding['phone'] }}</a>
                        @endif
                        @if ($branding['email'])
                            <a href="mailto:{{ $branding['email'] }}" class="truncate transition hover:text-slate-900">{{ $branding['email'] }}</a>
                        @endif
                    </div>

                    <nav aria-label="Utility navigation" class="flex items-center gap-4 text-xs font-semibold text-slate-600">
                        @foreach ($utilityLinks as $item)
                            <a href="{{ route($item['route']) }}" class="transition hover:text-slate-900">{{ $item['label'] }}</a>
                        @endforeach
                    </nav>
                </div>
            </div>

            <div class="relative z-20 mx-auto flex h-18 max-w-7xl items-center justify-between gap-3 px-4 sm:h-20 sm:px-6 lg:px-8">
                <x-website.brand-mark :branding="$branding" class="min-w-0 flex-1 xl:max-w-64" />

                <nav aria-label="Primary navigation" data-website-desktop-nav class="shrink-0 items-center gap-0.5 md:flex">
                    @foreach ($navigation as $item)
                        @php
                            $itemHref = $item['href'] ?? route($item['route']);
                            $activeRoutes = $item['active_routes'] ?? [$item['route'] ?? null];
                            $isActive = collect($activeRoutes)
                                ->filter()
                                ->contains(fn ($activeRoute) => request()->routeIs($activeRoute, $activeRoute.'.*'));
                        @endphp
                        <x-website.nav-link :href="$itemHref" :active="$isActive" data-desktop-nav-item>
                            {{ $item['label'] }}
                        </x-website.nav-link>
                    @endforeach
                </nav>

                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ auth()->check() ? route('lms.dashboard') : route('login') }}"
                        class="hidden min-h-11 items-center justify-center rounded-full px-5 text-sm font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 xl:inline-flex"
                        style="background-color: var(--brand-primary); outline-color: var(--brand-primary)">
                        {{ auth()->check() ? 'Open portal' : 'Portal login' }}
                    </a>
                    <button data-website-menu-toggle type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 md:hidden"
                        style="outline-color: var(--brand-primary)" aria-expanded="false"
                        aria-controls="website-mobile-menu" aria-label="Open navigation">
                        <svg data-menu-icon="open" aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                        <svg data-menu-icon="close" aria-hidden="true" class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div data-website-menu-backdrop class="fixed inset-0 top-18 z-0 hidden bg-slate-950/30 backdrop-blur-sm sm:top-20 md:hidden" aria-hidden="true"></div>
            <nav id="website-mobile-menu" data-website-menu aria-label="Mobile navigation"
                class="absolute inset-x-0 top-full z-10 max-h-[calc(100dvh-4.5rem)] overflow-y-auto border-t border-slate-200 bg-white px-4 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-4 shadow-2xl sm:px-6 md:hidden"
                aria-hidden="true">
                <div class="mx-auto grid max-w-7xl gap-1">
                    @foreach ($navigation as $item)
                        @php
                            $itemHref = $item['href'] ?? route($item['route']);
                            $activeRoutes = $item['active_routes'] ?? [$item['route'] ?? null];
                            $isActive = collect($activeRoutes)
                                ->filter()
                                ->contains(fn ($activeRoute) => request()->routeIs($activeRoute, $activeRoute.'.*'));
                        @endphp
                        <x-website.nav-link :href="$itemHref" :active="$isActive" mobile>
                            {{ $item['label'] }}
                        </x-website.nav-link>
                    @endforeach
                    <div class="my-2 border-t border-slate-200"></div>
                    <a href="{{ auth()->check() ? route('lms.dashboard') : route('login') }}"
                        class="inline-flex min-h-12 items-center justify-center rounded-xl px-5 text-sm font-bold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                        style="background-color: var(--brand-primary); outline-color: var(--brand-primary)">
                        {{ auth()->check() ? 'Open learning portal' : 'Sign in to the portal' }}
                    </a>
                    <a href="{{ route('website.sitemap') }}" class="mt-2 inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        View sitemap
                    </a>
                    <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 px-2 text-sm text-slate-600">
                        @if ($branding['phone'])
                            <a href="tel:{{ $phoneHref }}" class="hover:text-slate-950">{{ $branding['phone'] }}</a>
                        @endif
                        @if ($branding['email'])
                            <a href="mailto:{{ $branding['email'] }}" class="break-all hover:text-slate-950">{{ $branding['email'] }}</a>
                        @endif
                    </div>
                </div>
            </nav>
        </header>

        <main id="website-main" tabindex="-1" class="min-w-0 flex-1 outline-none">
            {{ $slot }}
        </main>

        <x-website.site-footer :branding="$branding" :navigation="$navigation" />
        <x-website.gallery-lightbox />

        @livewireScripts
    </body>
</html>
