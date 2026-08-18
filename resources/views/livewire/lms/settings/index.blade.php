<div class="space-y-6">
    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Administration</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Settings</h2>
        </div>
        <x-button variant="primary" size="md">Save changes</x-button>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">School profile</h3>
            <p class="mt-2 text-sm text-slate-600">Update school name, contact details, and basic information.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">System preferences</h3>
            <p class="mt-2 text-sm text-slate-600">Configure notifications, security, and global app behavior.</p>
        </div>
    </div>
</div>
