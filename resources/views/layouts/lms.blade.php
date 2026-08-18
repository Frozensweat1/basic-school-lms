<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>LMS Dashboard</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-dvh overflow-hidden bg-slate-100 text-slate-900 antialiased">
        <div class="h-dvh">
            <x-sidebar />

            <div id="lms-content-shell" class="flex h-dvh flex-col overflow-hidden transition-all duration-200 md:pl-72">
                <header class="z-10 shrink-0 border-b border-slate-200 bg-white/90 backdrop-blur-sm">
                    <div class="flex items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <div class="flex items-center gap-3">
                            <button id="content-sidebar-toggle" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-300 bg-white text-slate-700 shadow-sm transition hover:bg-slate-100 md:hidden" aria-label="Toggle sidebar">
                                ☰
                            </button>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Learning management system</p>
                                <h1 class="text-xl font-bold text-slate-900">Dashboard</h1>
                            </div>
                        </div>
                        <div class="ml-auto flex items-center gap-3">
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">School admin</span>
                            <div class="relative">
                                <button id="user-menu-toggle" type="button" class="flex items-center gap-2 rounded-full p-1 text-left transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-expanded="false" aria-haspopup="menu">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-700 text-sm font-bold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                    <span class="hidden max-w-32 truncate text-sm font-medium text-slate-700 sm:block">{{ auth()->user()->name }}</span>
                                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.09 1.04l-4.25 4.5a.75.75 0 0 1-1.1 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg>
                                </button>
                                <div id="user-menu" class="absolute right-0 mt-2 hidden w-52 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg" role="menu">
                                    <div class="border-b border-slate-100 px-4 py-3">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                                    </div>
                                    <a href="{{ route('lms.profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50" role="menuitem">Profile settings</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-rose-700 hover:bg-rose-50" role="menuitem">Logout</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
                <footer class="shrink-0 border-t border-slate-200 bg-white px-4 py-3 text-xs text-slate-500 sm:px-6 lg:px-8">
                    © {{ now()->year }} BrightStar Academy. All rights reserved.
                </footer>
            </div>
        </div>

        <div id="sidebar-backdrop" class="fixed inset-0 z-20 hidden bg-slate-950/40 md:hidden"></div>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            (function () {
                const sidebar = document.getElementById('lms-sidebar');
                const shell = document.getElementById('lms-content-shell');
                const backdrop = document.getElementById('sidebar-backdrop');
                const toggleButtons = [
                    document.getElementById('sidebar-toggle'),
                    document.getElementById('content-sidebar-toggle')
                ].filter(Boolean);

                function updateContentOffset(collapsed) {
                    if (!shell) return;
                    shell.classList.remove('md:pl-72', 'md:pl-24');
                    shell.classList.add(collapsed ? 'md:pl-24' : 'md:pl-72');
                }

                function setSidebarState(collapsed) {
                    if (!sidebar) return;

                    sidebar.classList.toggle('sidebar-collapsed', collapsed);
                    sidebar.setAttribute('data-collapsed', collapsed ? 'true' : 'false');
                    updateContentOffset(collapsed);

                    const toggleButton = document.getElementById('sidebar-toggle');
                    if (toggleButton) {
                        toggleButton.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
                        toggleButton.innerHTML = collapsed ? '»' : '«';
                    }
                }

                function setMobileOpen(open) {
                    if (!sidebar || !backdrop) return;
                    sidebar.classList.toggle('translate-x-0', open);
                    sidebar.classList.toggle('-translate-x-full', !open);
                    backdrop.classList.toggle('hidden', !open);
                }

                const stored = localStorage.getItem('lms-sidebar-collapsed');
                if (stored === '1') {
                    setSidebarState(true);
                }

                toggleButtons.forEach((button) => {
                    button.addEventListener('click', function () {
                        if (window.innerWidth < 768) {
                            const isOpen = !sidebar.classList.contains('translate-x-0');
                            setMobileOpen(!isOpen);
                            return;
                        }

                        const collapsed = !sidebar.classList.contains('sidebar-collapsed');
                        setSidebarState(collapsed);
                        localStorage.setItem('lms-sidebar-collapsed', collapsed ? '1' : '0');
                    });
                });

                if (backdrop) {
                    backdrop.addEventListener('click', function () {
                        setMobileOpen(false);
                    });
                }

                const userMenuToggle = document.getElementById('user-menu-toggle');
                const userMenu = document.getElementById('user-menu');
                if (userMenuToggle && userMenu) {
                    userMenuToggle.addEventListener('click', function () {
                        const isOpen = userMenu.classList.toggle('hidden') === false;
                        userMenuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    });

                    document.addEventListener('click', function (event) {
                        if (!userMenu.contains(event.target) && !userMenuToggle.contains(event.target)) {
                            userMenu.classList.add('hidden');
                            userMenuToggle.setAttribute('aria-expanded', 'false');
                        }
                    });
                }

                window.addEventListener('resize', function () {
                    if (window.innerWidth >= 768) {
                        sidebar.classList.remove('-translate-x-full', 'translate-x-0');
                        backdrop.classList.add('hidden');
                    }
                });
            })();
        </script>
        <style>
            .sidebar-panel {
                width: 18rem;
                overflow: hidden;
            }

            .sidebar-panel.sidebar-collapsed {
                width: 5rem;
            }

            .sidebar-panel.sidebar-collapsed .brand-copy,
            .sidebar-panel.sidebar-collapsed .nav-section-label,
            .sidebar-panel.sidebar-collapsed .nav-link-text {
                display: none;
            }

            .sidebar-panel.sidebar-collapsed .nav-link {
                justify-content: center;
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            .sidebar-panel.sidebar-collapsed .nav-link > span:first-child {
                margin-right: 0;
            }

            .sidebar-nav {
                scrollbar-width: thin;
                scrollbar-color: rgba(148, 163, 184, 0.6) transparent;
            }

            .sidebar-nav::-webkit-scrollbar {
                width: 6px;
            }

            .sidebar-nav::-webkit-scrollbar-thumb {
                background: rgba(148, 163, 184, 0.5);
                border-radius: 9999px;
            }

            .sidebar-nav::-webkit-scrollbar-track {
                background: transparent;
            }

            @media (max-width: 767px) {
                #lms-sidebar {
                    transform: translateX(-100%);
                    width: 18rem;
                }

                #lms-sidebar.translate-x-0 {
                    transform: translateX(0);
                }

                #lms-content-shell {
                    padding-left: 0 !important;
                }
            }
        </style>
    </body>
</html>
