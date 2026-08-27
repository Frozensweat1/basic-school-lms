<x-layouts.auth title="Confirm password">
    <div class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700">Security check</p>
            <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">Confirm your password</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">For your security, please confirm your password before continuing.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.confirm.store') }}" data-loading-form class="space-y-5" novalidate>
            @csrf

            <div>
                <label for="password" class="mb-2 block text-sm font-bold text-slate-800">Password</label>
                <div class="relative">
                    <input id="password" name="password" type="password" required autofocus autocomplete="current-password" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 pr-16 text-slate-900 shadow-sm transition focus:border-blue-700 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="Enter your password">
                    <button type="button" data-password-toggle="password" aria-pressed="false" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg px-2.5 py-1.5 text-[0.7rem] font-bold uppercase tracking-[0.12em] text-slate-500 transition hover:bg-slate-200 hover:text-slate-900">Show</button>
                </div>
            </div>

            <button type="submit" data-submit-button class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-0.5 hover:bg-blue-900 focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-wait disabled:opacity-70">
                <span data-submit-label>Confirm password</span>
                <span data-submit-loading hidden class="inline-flex items-center justify-center gap-2" style="display:none;">Checking...</span>
            </button>
        </form>
    </div>
</x-layouts.auth>
