<x-layouts.auth title="Sign in">
    <div class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700">Welcome back</p>
            <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">Sign in to your portal</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Access classes, attendance, results, and school communication from one secure place.</p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" data-loading-form class="space-y-5" novalidate>
            @csrf

            <div>
                <label for="email" class="mb-2 block text-sm font-bold text-slate-800">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm transition focus:border-blue-700 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="you@example.com">
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <label for="password" class="block text-sm font-bold text-slate-800">Password</label>
                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-blue-700 transition hover:text-blue-900">Forgot password?</a>
                </div>
                <div class="relative">
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 pr-16 text-slate-900 shadow-sm transition focus:border-blue-700 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="Enter your password">
                    <button type="button" data-password-toggle="password" aria-pressed="false" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg px-2.5 py-1.5 text-[0.7rem] font-bold uppercase tracking-[0.12em] text-slate-500 transition hover:bg-slate-200 hover:text-slate-900">Show</button>
                </div>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                Remember me
            </label>

            <button type="submit" data-submit-button class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-0.5 hover:bg-blue-900 focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-wait disabled:opacity-70">
                <span data-submit-label>Sign in</span>
                <span data-submit-loading hidden class="inline-flex items-center justify-center gap-2" style="display:none;"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>Signing in...</span>
            </button>
        </form>

        @if (Route::has('register'))
            <p class="text-center text-sm text-slate-600">Need access? <a href="{{ route('register') }}" class="font-bold text-blue-700 transition hover:text-blue-900">Contact the administrator</a></p>
        @endif
    </div>
</x-layouts.auth>
