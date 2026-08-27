@php
    $branding = app(\App\Support\SchoolBranding::class)->forLms();
    $pageTitle = app(\App\Support\LmsPage::class)->title();
    $unreadNotificationCount = auth()->user()->unreadNotifications()->count();
    $currentRouteName = request()->route()?->getName() ?? '';
    $lmsSection = match (true) {
        str_starts_with($currentRouteName, 'lms.dashboard') => 'Main',
        str_starts_with($currentRouteName, 'lms.profile') => 'Main',
        str_starts_with($currentRouteName, 'lms.academic-years'),
        str_starts_with($currentRouteName, 'lms.terms'),
        str_starts_with($currentRouteName, 'lms.classes'),
        str_starts_with($currentRouteName, 'lms.streams'),
        str_starts_with($currentRouteName, 'lms.subjects'),
        str_starts_with($currentRouteName, 'lms.class-subjects') => 'Academic Setup',
        str_starts_with($currentRouteName, 'lms.students'),
        str_starts_with($currentRouteName, 'lms.teachers'),
        str_starts_with($currentRouteName, 'lms.parents') => 'People',
        str_starts_with($currentRouteName, 'lms.lessons'),
        str_starts_with($currentRouteName, 'lms.topics') => 'Learning',
        str_starts_with($currentRouteName, 'lms.assignments'),
        str_starts_with($currentRouteName, 'lms.quizzes'),
        str_starts_with($currentRouteName, 'lms.questions'),
        str_starts_with($currentRouteName, 'lms.examinations'),
        str_starts_with($currentRouteName, 'lms.assessments'),
        str_starts_with($currentRouteName, 'lms.assessment-components'),
        str_starts_with($currentRouteName, 'lms.grading-scales'),
        str_starts_with($currentRouteName, 'lms.results') => 'Assessments',
        str_starts_with($currentRouteName, 'lms.attendance'),
        str_starts_with($currentRouteName, 'lms.timetables'),
        str_starts_with($currentRouteName, 'lms.schedule-periods'),
        str_starts_with($currentRouteName, 'lms.reports') => 'Records',
        str_starts_with($currentRouteName, 'lms.announcements'),
        str_starts_with($currentRouteName, 'lms.notifications') => 'Communication',
        str_starts_with($currentRouteName, 'lms.users'),
        str_starts_with($currentRouteName, 'lms.roles'),
        str_starts_with($currentRouteName, 'lms.permissions'),
        str_starts_with($currentRouteName, 'lms.audit-logs'),
        str_starts_with($currentRouteName, 'lms.school-setup'),
        str_starts_with($currentRouteName, 'lms.settings') => 'Administration',
        str_starts_with($currentRouteName, 'lms.website.') => 'Public Website',
        default => 'LMS',
    };
    $headerRole = str(auth()->user()->roles->first()?->name ?? 'user')->replace('_', ' ')->title();
    $userInitials = str(auth()->user()->name)
        ->trim()
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="{{ $branding['colors']['primary'] }}">
        <meta name="color-scheme" content="light dark">
        <title>{{ $pageTitle }} | {{ $branding['name'] }}</title>
        <script>
            (() => {
                const preference = localStorage.getItem('lms-theme');
                const dark = preference === 'dark' || (!preference && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>
        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-dvh overflow-hidden bg-slate-100 text-slate-900 antialiased transition-colors dark:bg-slate-950 dark:text-slate-100">
        <a href="#lms-main" class="fixed left-3 top-3 z-[10000] -translate-y-20 rounded-lg bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-lg transition focus:translate-y-0">
            Skip to main content
        </a>

        <div class="h-dvh min-h-0">
            <x-sidebar :unread-notification-count="$unreadNotificationCount" :branding="$branding" />

            <div id="lms-content-shell" class="flex h-dvh min-h-0 flex-col overflow-hidden transition-[padding] duration-200 md:pl-72">
                <header class="relative z-20 shrink-0 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/95">
                    <div class="flex min-h-16 items-center justify-between gap-2 px-3 py-2 sm:gap-4 sm:px-6 lg:px-8">
                        <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                            <button
                                id="content-sidebar-toggle"
                                type="button"
                                class="inline-flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 md:hidden"
                                aria-label="Open navigation"
                                aria-controls="lms-sidebar"
                                aria-expanded="false"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"></path>
                                </svg>
                            </button>
                            <div class="min-w-0">
                                <p class="truncate text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400 sm:text-xs">
                                    <span class="hidden sm:inline">Learning management system</span>
                                    <span class="sm:hidden">LMS</span>
                                </p>
                                <p class="mt-1 hidden items-center gap-2 truncate text-xs text-slate-500 dark:text-slate-400 sm:inline-flex">
                                    <a href="{{ route('lms.dashboard') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Dashboard</a>
                                    <span aria-hidden="true">/</span>
                                    <span>{{ $lmsSection }}</span>
                                    @if ($pageTitle !== $lmsSection)
                                        <span aria-hidden="true">/</span>
                                        <span class="truncate text-slate-600 dark:text-slate-300">{{ $pageTitle }}</span>
                                    @endif
                                </p>
                                <h1 class="truncate text-lg font-bold leading-tight text-slate-900 dark:text-white sm:text-xl">{{ $pageTitle }}</h1>
                            </div>
                        </div>

                        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
                            <button
                                id="theme-toggle"
                                type="button"
                                class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-amber-300 dark:hover:border-slate-600 dark:hover:bg-slate-700"
                                aria-label="Switch to dark mode"
                                aria-pressed="false"
                                title="Switch color theme"
                            >
                                <svg data-theme-icon="moon" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M20.5 15.2A8.5 8.5 0 0 1 8.8 3.5 8.5 8.5 0 1 0 20.5 15.2Z" stroke-linejoin="round"></path>
                                </svg>
                                <svg data-theme-icon="sun" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="12" cy="12" r="4"></circle>
                                    <path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" stroke-linecap="round"></path>
                                </svg>
                            </button>

                            <a href="{{ route('lms.notifications.index') }}" class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700" aria-label="Notifications{{ $unreadNotificationCount ? ': '.$unreadNotificationCount.' unread' : '' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"></path></svg>
                                @if ($unreadNotificationCount)
                                    <span class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white ring-2 ring-white dark:ring-slate-900">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                                @endif
                            </a>

                            <span class="hidden rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 lg:inline-flex">{{ $headerRole }}</span>

                            <div class="relative">
                                <button id="user-menu-toggle" type="button" class="flex min-h-11 cursor-pointer items-center gap-2 rounded-full p-1 text-left transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:hover:bg-slate-800" aria-expanded="false" aria-haspopup="menu" aria-controls="user-menu">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-700 text-sm font-bold text-white">{{ $userInitials ?: 'U' }}</span>
                                    <span class="hidden max-w-32 truncate text-sm font-medium text-slate-700 dark:text-slate-200 xl:block">{{ auth()->user()->name }}</span>
                                    <svg class="hidden h-4 w-4 text-slate-500 sm:block" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.09 1.04l-4.25 4.5a.75.75 0 0 1-1.1 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg>
                                </button>
                                <div id="user-menu" class="absolute right-0 z-30 mt-2 hidden w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl dark:border-slate-700 dark:bg-slate-900" role="menu" aria-label="User menu">
                                    <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ auth()->user()->name }}</p>
                                        <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</p>
                                    </div>
                                    <a href="{{ route('lms.profile.edit') }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:bg-slate-800" role="menuitem">Profile settings</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full cursor-pointer px-4 py-2.5 text-left text-sm text-rose-700 hover:bg-rose-50 focus:bg-rose-50 focus:outline-none dark:text-rose-400 dark:hover:bg-rose-950/40 dark:focus:bg-rose-950/40" role="menuitem">Logout</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <main id="lms-main" tabindex="-1" class="min-h-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-contain p-3 focus:outline-none sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>

                <footer class="shrink-0 border-t border-slate-200 bg-white px-3 py-2.5 text-center text-[11px] text-slate-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 sm:px-6 sm:text-left sm:text-xs lg:px-8">
                    <span>&copy; {{ now()->year }} {{ $branding['name'] }}.</span>
                    <span class="hidden sm:inline"> All rights reserved.</span>
                </footer>
            </div>
        </div>

        <button id="sidebar-backdrop" type="button" class="fixed inset-0 z-30 hidden cursor-default bg-slate-950/60 backdrop-blur-[1px] md:hidden" aria-label="Close navigation" tabindex="-1"></button>

        @livewireScripts
    </body>
</html>
