@props(['title' => 'Account access'])
@php
    $branding = app(\App\Support\SchoolBranding::class)->data();
    $primaryColor = $branding['colors']['primary'] ?? '#0f172a';
@endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">
        <meta name="theme-color" content="{{ $primaryColor }}">
        <title>{{ $title }} | {{ $branding['name'] }}</title>
        @if ($branding['logo_url'])
            <link rel="icon" href="{{ $branding['logo_url'] }}">
            <link rel="apple-touch-icon" href="{{ $branding['logo_url'] }}">
        @endif
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            [data-submit-loading] {
                display: none;
            }

            button.is-loading [data-submit-label] {
                display: none;
            }

            button.is-loading [data-submit-loading] {
                display: inline-flex !important;
            }
        </style>
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
        <main class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid w-full max-w-6xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_35px_100px_-40px_rgba(15,23,42,0.45)] ring-1 ring-slate-200/70 lg:grid-cols-[0.9fr_1.1fr]">
                <aside class="relative hidden overflow-hidden bg-slate-950 p-8 text-white lg:flex lg:flex-col lg:justify-between">
                    <div aria-hidden="true" class="absolute -right-16 -top-16 h-52 w-52 rounded-full bg-blue-500/20 blur-3xl"></div>
                    <div aria-hidden="true" class="absolute -bottom-20 -left-12 h-64 w-64 rounded-full bg-cyan-400/15 blur-3xl"></div>
                    <div class="relative">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-sm font-bold tracking-[0.2em] text-slate-100 uppercase">
                            @if ($branding['logo_url'])
                                <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['name'] }} logo" class="h-10 w-10 rounded-2xl bg-white/10 object-contain ring-1 ring-white/15">
                            @else
                                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/10 text-base font-black text-white ring-1 ring-white/15">{{ $branding['initials'] }}</span>
                            @endif
                            {{ $branding['name'] }}
                        </a>

                        <div class="mt-16 space-y-6">
                            <div>
                                <p class="text-[0.7rem] font-bold uppercase tracking-[0.28em] text-sky-200">Learning portal</p>
                                <h1 class="mt-4 max-w-sm text-4xl font-black leading-tight tracking-tight text-white">Stay connected to every part of school life.</h1>
                            </div>
                            <p class="max-w-sm text-base leading-7 text-slate-300">{{ $branding['motto'] }}</p>
                        </div>
                    </div>

                    <div class="relative grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm">
                            <p class="text-[0.65rem] font-bold uppercase tracking-[0.18em] text-slate-300">Portal</p>
                            <p class="mt-2 text-xl font-black text-white">24/7</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm">
                            <p class="text-[0.65rem] font-bold uppercase tracking-[0.18em] text-slate-300">Support</p>
                            <p class="mt-2 text-xl font-black text-white">Fast</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm">
                            <p class="text-[0.65rem] font-bold uppercase tracking-[0.18em] text-slate-300">Access</p>
                            <p class="mt-2 text-xl font-black text-white">Secure</p>
                        </div>
                    </div>
                </aside>

                <section class="flex items-center justify-center p-5 sm:p-8 lg:p-10">
                    <div class="w-full max-w-md">
                        <div class="mb-8 flex items-center justify-between lg:hidden">
                            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-sm font-bold text-slate-900">
                                @if ($branding['logo_url'])
                                    <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['name'] }} logo" class="h-9 w-9 rounded-xl object-contain ring-1 ring-slate-200">
                                @else
                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-950 text-sm font-black text-white">{{ $branding['initials'] }}</span>
                                @endif
                                {{ $branding['name'] }}
                            </a>
                            <a href="{{ route('home') }}" class="text-sm font-semibold text-slate-500 transition hover:text-slate-900">Website</a>
                        </div>
                        {{ $slot }}
                    </div>
                </section>
            </div>
        </main>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-password-toggle]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const input = document.getElementById(button.dataset.passwordToggle);
                        if (!input) return;

                        const isVisible = input.type === 'text';
                        input.type = isVisible ? 'password' : 'text';
                        button.textContent = isVisible ? 'Show' : 'Hide';
                        button.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
                    });
                });

                document.querySelectorAll('form[data-loading-form]').forEach((form) => {
                    form.addEventListener('submit', () => {
                        const button = form.querySelector('[data-submit-button]');
                        if (!button) return;

                        const label = button.querySelector('[data-submit-label]');
                        const loading = button.querySelector('[data-submit-loading]');

                        button.disabled = true;
                        button.classList.add('is-loading');

                        if (label) {
                            label.hidden = true;
                            label.style.display = 'none';
                        }

                        if (loading) {
                            loading.hidden = false;
                            loading.style.display = 'inline-flex';
                        }
                    });
                });
            });
        </script>
    </body>
</html>
