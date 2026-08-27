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
            <div class="grid w-full max-w-5xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl shadow-slate-900/10 lg:grid-cols-[0.92fr_1.08fr]">
                <aside class="relative hidden overflow-hidden bg-blue-950 p-10 text-white lg:flex lg:flex-col lg:justify-between">
                    <div aria-hidden="true" class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-sky-400/20 blur-3xl"></div>
                    <div aria-hidden="true" class="absolute -bottom-28 -left-20 h-80 w-80 rounded-full bg-amber-300/10 blur-3xl"></div>
                    <div class="relative">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-sm font-bold tracking-wide text-white">
                            @if ($branding['logo_url'])
                                <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['name'] }} logo" class="h-10 w-10 rounded-2xl bg-white/10 object-contain ring-1 ring-white/20">
                            @else
                                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/10 text-lg ring-1 ring-white/15">{{ $branding['initials'] }}</span>
                            @endif
                            {{ $branding['name'] }}
                        </a>
                        <p class="mt-20 max-w-sm text-xs font-bold uppercase tracking-[0.24em] text-sky-200">Learning portal</p>
                        <h1 class="mt-5 max-w-md text-4xl font-black leading-tight tracking-tight">A calmer way to stay close to learning.</h1>
                        <p class="mt-5 max-w-sm text-base leading-7 text-blue-100">{{ $branding['motto'] }}</p>
                    </div>
                    <div class="relative flex items-center gap-3 text-sm text-blue-100"><span class="h-2 w-2 rounded-full bg-emerald-300"></span>School community portal</div>
                </aside>

                <section class="p-6 sm:p-10 lg:p-14">
                    <div class="mb-8 flex items-center justify-between lg:hidden">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-950">
                            @if ($branding['logo_url'])
                                <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['name'] }} logo" class="h-9 w-9 rounded-xl object-contain ring-1 ring-slate-200">
                            @else
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-950 text-white">{{ $branding['initials'] }}</span>
                            @endif
                            {{ $branding['name'] }}
                        </a>
                        <a href="{{ route('home') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-900">Website</a>
                    </div>
                    {{ $slot }}
                </section>
            </div>
        </main>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-password-toggle]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const input = document.getElementById(button.dataset.passwordToggle);
                        if (!input) return;
                        const visible = input.type === 'text';
                        input.type = visible ? 'password' : 'text';
                        button.textContent = visible ? 'Show' : 'Hide';
                        button.setAttribute('aria-pressed', visible ? 'false' : 'true');
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
