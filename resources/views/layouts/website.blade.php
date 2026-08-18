<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>BrightStar Academy</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 text-slate-900 antialiased">
        <header class="border-b border-slate-200 bg-white/90 backdrop-blur-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-900 font-semibold text-white">BS</div>
                    <div>
                        <p class="text-lg font-bold tracking-wide text-slate-900">BrightStar Academy</p>
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Nurturing excellence</p>
                    </div>
                </div>
                <nav class="hidden items-center gap-8 text-sm font-medium text-slate-700 md:flex">
                    <a href="#" class="hover:text-blue-900">Home</a>
                    <a href="#" class="hover:text-blue-900">About</a>
                    <a href="#" class="hover:text-blue-900">Academics</a>
                    <a href="#" class="hover:text-blue-900">Admissions</a>
                    <a href="#" class="hover:text-blue-900">Teachers</a>
                    <a href="#" class="hover:text-blue-900">News</a>
                    <a href="#" class="hover:text-blue-900">Contact</a>
                </nav>
                <a href="/login" class="rounded-full bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                    Portal Login
                </a>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200 bg-slate-900 text-slate-200">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-4 lg:px-8">
                <div>
                    <h3 class="mb-4 text-lg font-semibold text-white">BrightStar Academy</h3>
                    <p class="text-sm text-slate-300">A caring learning community dedicated to academic excellence, creativity, and character.</p>
                </div>
                <div>
                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Explore</h4>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li>About us</li>
                        <li>Academics</li>
                        <li>Admissions</li>
                        <li>News & events</li>
                    </ul>
                </div>
                <div>
                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Quick links</h4>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li>Parent portal</li>
                        <li>Student portal</li>
                        <li>Teacher resources</li>
                        <li>Support</li>
                    </ul>
                </div>
                <div>
                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Contact</h4>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li>12 School Avenue</li>
                        <li>hello@brightstar.academy</li>
                        <li>+234 800 000 0000</li>
                    </ul>
                </div>
            </div>
        </footer>
    </body>
</html>
