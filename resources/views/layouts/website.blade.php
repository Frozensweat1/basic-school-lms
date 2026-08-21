<!DOCTYPE html>
@php($branding = app(\App\Support\SchoolBranding::class)->data())
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $branding['name'] }}</title>
        <meta name="description" content="{{ $branding['motto'] }}">
        <style>:root{--brand-primary:{{ $branding['colors']['primary'] }};--brand-secondary:{{ $branding['colors']['secondary'] }};--brand-accent:{{ $branding['colors']['accent'] }};} </style>
        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 text-slate-900 antialiased">
        <header class="relative border-b border-slate-200 bg-white/90 backdrop-blur-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    @if($branding['logo_url'])<img src="{{ $branding['logo_url'] }}" alt="{{ $branding['name'] }} logo" class="h-10 w-10 rounded-full object-cover ring-1 ring-slate-200">@else<div class="flex h-10 w-10 items-center justify-center rounded-full font-semibold text-white" style="background:var(--brand-primary)">{{ $branding['initials'] }}</div>@endif
                    <div>
                        <p class="text-lg font-bold tracking-wide text-slate-900">{{ $branding['name'] }}</p>
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">{{ $branding['motto'] }}</p>
                    </div>
                </div>
                <nav class="hidden items-center gap-6 text-sm font-medium text-slate-700 md:flex">
                    <a href="{{ route('home') }}" class="hover:text-blue-900">Home</a>
                    <a href="{{ route('website.about') }}" class="hover:text-blue-900">About</a>
                    <a href="{{ route('website.academics') }}" class="hover:text-blue-900">Academics</a>
                    <a href="{{ route('website.admissions') }}" class="hover:text-blue-900">Admissions</a>
                    <a href="{{ route('website.teachers') }}" class="hover:text-blue-900">Teachers</a>
                    <a href="{{ route('website.news') }}" class="hover:text-blue-900">News</a>
                    <a href="{{ route('website.events') }}" class="hover:text-blue-900">Events</a>
                    <a href="{{ route('website.gallery') }}" class="hover:text-blue-900">Gallery</a>
                    <a href="{{ route('website.contact') }}" class="hover:text-blue-900">Contact</a>
                </nav>
                <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('lms.dashboard') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90" style="background:var(--brand-primary)">
                        Open portal
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90" style="background:var(--brand-primary)">
                        Portal Login
                    </a>
                @endauth
                <button id="website-menu-toggle" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-700 md:hidden" aria-expanded="false" aria-controls="website-mobile-menu" aria-label="Open menu"><span aria-hidden="true">☰</span></button>
                <div id="website-mobile-menu" class="absolute left-4 right-4 top-20 z-20 hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-xl md:hidden"><div class="grid gap-2 text-sm font-medium text-slate-700"><a href="{{ route('home') }}" class="rounded-lg px-3 py-2 hover:bg-slate-50">Home</a><a href="{{ route('website.about') }}" class="rounded-lg px-3 py-2 hover:bg-slate-50">About</a><a href="{{ route('website.academics') }}" class="rounded-lg px-3 py-2 hover:bg-slate-50">Academics</a><a href="{{ route('website.admissions') }}" class="rounded-lg px-3 py-2 hover:bg-slate-50">Admissions</a><a href="{{ route('website.teachers') }}" class="rounded-lg px-3 py-2 hover:bg-slate-50">Teachers</a><a href="{{ route('website.news') }}" class="rounded-lg px-3 py-2 hover:bg-slate-50">News</a><a href="{{ route('website.events') }}" class="rounded-lg px-3 py-2 hover:bg-slate-50">Events</a><a href="{{ route('website.gallery') }}" class="rounded-lg px-3 py-2 hover:bg-slate-50">Gallery</a><a href="{{ route('website.contact') }}" class="rounded-lg px-3 py-2 hover:bg-slate-50">Contact</a></div></div>
                </div>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200 bg-slate-900 text-slate-200">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-4 lg:px-8">
                <div>
                    <div class="flex items-center gap-3">@if($branding['logo_url'])<img src="{{ $branding['logo_url'] }}" alt="" class="h-10 w-10 rounded-full object-cover">@else<div class="flex h-10 w-10 items-center justify-center rounded-full font-semibold text-white" style="background:var(--brand-primary)">{{ $branding['initials'] }}</div>@endif<h3 class="text-lg font-semibold text-white">{{ $branding['name'] }}</h3></div>
                    <p class="mt-4 text-sm text-slate-300">{{ $branding['footer_text'] }}</p>
                </div>
                <div>
                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Explore</h4>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li><a href="{{ route('website.about') }}" class="hover:text-white">About us</a></li>
                        <li><a href="{{ route('website.academics') }}" class="hover:text-white">Academics</a></li>
                        <li><a href="{{ route('website.admissions') }}" class="hover:text-white">Admissions</a></li>
                        <li><a href="{{ route('website.news') }}" class="hover:text-white">News & events</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Quick links</h4>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li><a href="{{ auth()->check() ? route('lms.dashboard') : route('login') }}" class="hover:text-white">Parent portal</a></li>
                        <li><a href="{{ auth()->check() ? route('lms.dashboard') : route('login') }}" class="hover:text-white">Student portal</a></li>
                        <li><a href="{{ auth()->check() ? route('lms.dashboard') : route('login') }}" class="hover:text-white">Teacher resources</a></li>
                        <li><a href="{{ route('website.contact') }}" class="hover:text-white">Support</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Contact</h4>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li>{{ $branding['address'] }}</li>
                        <li><a href="mailto:{{ $branding['email'] }}" class="hover:text-white">{{ $branding['email'] }}</a></li>
                        <li>{{ $branding['phone'] }}</li>
                        @foreach($branding['socials'] as $network => $url) @if($url)<li><a href="{{ $network === 'whatsapp' ? 'https://wa.me/'.preg_replace('/\D+/', '', $url) : $url }}" target="_blank" rel="noopener" class="capitalize hover:text-white">{{ $network }}</a></li>@endif @endforeach
                    </ul>
                </div>
            </div>
        </footer>
        <script>
            (() => {
                const toggle = document.getElementById('website-menu-toggle');
                const menu = document.getElementById('website-mobile-menu');
                if (!toggle || !menu) return;
                toggle.addEventListener('click', () => {
                    const open = menu.classList.toggle('hidden') === false;
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
            })();
        </script>
        @livewireScripts
    </body>
</html>
