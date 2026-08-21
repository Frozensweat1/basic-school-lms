<div class="space-y-6">
    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Administration</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Roles</h2>
            <p class="mt-1 text-sm text-slate-600">Create role profiles and control which permissions each profile receives.</p>
        </div>
        <x-button wire:click="create" target="create" :loading="true" icon="plus">Add role</x-button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">Role</th><th class="px-5 py-3">Permissions</th><th class="px-5 py-3">Users</th><th class="px-5 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($roles as $role)
                        <tr wire:key="role-{{ $role->id }}">
                            <td class="px-5 py-4"><span class="font-semibold text-slate-900">{{ $role->name }}</span>@if(in_array($role->name, ['super_admin','school_admin','teacher','student','parent'], true))<span class="ml-2 rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-600">Built-in</span>@endif</td>
                            <td class="px-5 py-4"><div class="flex max-w-xl flex-wrap gap-1.5">@forelse($role->permissions as $permission)<span class="rounded-full bg-blue-50 px-2 py-1 text-xs text-blue-700">{{ $permission->name }}</span>@empty<span class="text-slate-500">No permissions</span>@endforelse</div></td>
                            <td class="px-5 py-4 text-slate-600">{{ $role->users_count }}</td>
                            <td class="px-5 py-4"><div class="flex justify-end gap-2">@if(auth()->user()->hasRole('super_admin') || ! in_array($role->name, ['super_admin','school_admin','teacher','student','parent'], true))<x-ui.icon-button wire:click="edit({{ $role->id }})" icon="edit" label="Edit {{ $role->name }}" target="edit({{ $role->id }})" /><x-ui.icon-button wire:click="confirmDelete({{ $role->id }})" icon="trash" variant="danger" label="Delete {{ $role->name }}" target="confirmDelete({{ $role->id }})" />@else<span class="text-xs text-slate-400">Protected</span>@endif</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">No roles have been configured yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-modal :show="$showFormModal" :title="$editingId ? 'Edit role' : 'Create role'" close-action="closeModals" max-width="xl">
        <form wire:submit="save" class="space-y-5">
            <div><label for="role-name" class="block text-sm font-medium text-slate-700">Role name</label><input id="role-name" wire:model.blur="name" class="mt-1 block w-full rounded-lg border-slate-300" placeholder="e.g. librarian">@error('name')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
            <fieldset><legend class="text-sm font-medium text-slate-700">Permissions</legend><div class="mt-2 grid max-h-72 gap-2 overflow-y-auto rounded-xl border border-slate-200 p-3 sm:grid-cols-2">@forelse($permissions as $permission)<label class="flex items-start gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50"><input type="checkbox" value="{{ $permission->id }}" wire:model="selectedPermissions" class="mt-0.5 rounded border-slate-300 text-blue-700"><span>{{ $permission->name }}</span></label>@empty<p class="text-sm text-slate-500">No permissions are available. Seed the permission catalog first.</p>@endforelse</div>@error('selectedPermissions.*')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</fieldset>
            <div class="flex justify-end gap-3"><x-button type="button" wire:click="closeModals" variant="secondary" target="closeModals" :loading="true">Cancel</x-button><x-button type="submit" icon="save" target="save" :loading="true">Save role</x-button></div>
        </form>
    </x-modal>
    <x-modal :show="$showDeleteModal" title="Delete role?" close-action="closeModals" max-width="md"><p class="text-sm text-slate-600">Users assigned to this role will keep their accounts but lose this role's permissions.</p><x-slot:footer><div class="flex justify-end gap-3"><x-button wire:click="closeModals" variant="secondary" target="closeModals" :loading="true">Cancel</x-button><x-button wire:click="delete" variant="danger" icon="trash" target="delete" :loading="true">Delete role</x-button></div></x-slot:footer></x-modal>
</div>
