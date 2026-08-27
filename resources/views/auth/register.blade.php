<x-layouts.auth title="Request access">
    <div class="space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-700">Portal access</p>
            <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">Contact the administrator</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Account access is managed by the school administrator, and self-service registration is not available for regular users.</p>
        </div>

        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm leading-6 text-blue-900">
            <p class="font-semibold text-blue-950">To get access, please contact the administrator directly.</p>
            <p class="mt-2 text-blue-800">A portal account can be created or assigned by the admin team once your enrollment or staff status is confirmed.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
            <p>If you already have an account, you can <a href="{{ route('login') }}" class="font-bold text-blue-700 transition hover:text-blue-900">sign in here</a>.</p>
        </div>
    </div>
</x-layouts.auth>
