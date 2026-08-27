<x-layouts.auth title="Choose a new password">
    <div class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700">Account recovery</p>
            <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">Choose a new password</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Use a strong password that you don't use anywhere else.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" data-loading-form class="space-y-5" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <label for="email" class="mb-2 block text-sm font-bold text-slate-800">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autocomplete="email" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm transition focus:border-blue-700 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="you@example.com">
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-bold text-slate-800">New password</label>
                <div class="relative">
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 pr-16 text-slate-900 shadow-sm transition focus:border-blue-700 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="Create a new password">
                    <button type="button" data-password-toggle="password" aria-pressed="false" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg px-2.5 py-1.5 text-[0.7rem] font-bold uppercase tracking-[0.12em] text-slate-500 transition hover:bg-slate-200 hover:text-slate-900">Show</button>
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-bold text-slate-800">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm transition focus:border-blue-700 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="Confirm your new password">
            </div>

            <button type="submit" data-submit-button class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-0.5 hover:bg-blue-900 focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-wait disabled:opacity-70">
                <span data-submit-label>Reset password</span>
                <span data-submit-loading hidden class="inline-flex items-center justify-center gap-2" style="display:none;">Resetting...</span>
            </button>
        </form>
    </div>
</x-layouts.auth>
