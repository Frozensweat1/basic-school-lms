<x-layouts.auth title="Two-factor verification">
    <div class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700">Secure sign in</p>
            <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">Verify it is you</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Enter the six-digit code from your authenticator app or use a recovery code.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('two-factor.login.store') }}" data-loading-form class="space-y-5" novalidate>
            @csrf

            <div>
                <label for="code" class="mb-2 block text-sm font-bold text-slate-800">Authentication code</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-center text-xl tracking-[0.35em] text-slate-900 shadow-sm transition focus:border-blue-700 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="000000">
            </div>

            <div>
                <label for="recovery_code" class="mb-2 block text-sm font-bold text-slate-800">Recovery code <span class="font-normal text-slate-500">(optional)</span></label>
                <input id="recovery_code" name="recovery_code" autocomplete="one-time-code" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm transition focus:border-blue-700 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100" placeholder="Use a recovery code instead">
            </div>

            <button type="submit" data-submit-button class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/15 transition hover:-translate-y-0.5 hover:bg-blue-900 focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-wait disabled:opacity-70">
                <span data-submit-label>Verify and sign in</span>
                <span data-submit-loading hidden class="inline-flex items-center justify-center gap-2" style="display:none;">Verifying...</span>
            </button>
        </form>

        <p class="text-center text-sm text-slate-600"><a href="{{ route('login') }}" class="font-bold text-blue-700 transition hover:text-blue-900">Back to sign in</a></p>
    </div>
</x-layouts.auth>
