<x-layouts.auth title="Reset password">
    <div class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700">Account recovery</p>
            <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">Forgot your password?</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Enter your email and we will send you a secure reset link.</p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" data-loading-form class="space-y-5" novalidate>
            @csrf

            <div>
                <label for="email" class="mb-2 block text-sm font-bold text-slate-800">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm transition focus:border-blue-700 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="you@example.com">
            </div>

            <button type="submit" data-submit-button class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-0.5 hover:bg-blue-900 focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-wait disabled:opacity-70">
                <span data-submit-label>Send reset link</span>
                <span data-submit-loading hidden class="inline-flex items-center justify-center gap-2" style="display:none;">Sending...</span>
            </button>
        </form>

        <p class="text-center text-sm text-slate-600"><a href="{{ route('login') }}" class="font-bold text-blue-700 transition hover:text-blue-900">Back to sign in</a></p>
    </div>
</x-layouts.auth>
