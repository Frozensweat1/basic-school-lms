<div class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Administration</p>
        <h2 class="mt-1 text-2xl font-bold text-slate-900">Permissions</h2>
        <p class="mt-1 text-sm text-slate-600">This catalog is read-only. Assign permissions to roles from the Roles screen.</p>
    </div>

    @forelse($permissions as $group => $items)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-3"><h3 class="font-semibold capitalize text-slate-800">{{ str_replace(['_', '-'], ' ', $group) }}</h3></div>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-left text-sm"><thead class="text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Permission</th><th class="px-5 py-3">Assigned roles</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($items as $permission)<tr wire:key="permission-{{ $permission->id }}"><td class="px-5 py-3 font-medium text-slate-800">{{ $permission->name }}</td><td class="px-5 py-3 text-slate-600">{{ $permission->roles_count }}</td></tr>@endforeach</tbody></table></div>
        </section>
    @empty
        <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500">No permissions have been seeded yet.</div>
    @endforelse
</div>
