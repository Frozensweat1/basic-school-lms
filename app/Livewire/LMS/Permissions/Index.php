<?php

namespace App\Livewire\LMS\Permissions;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function render()
    {
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->withCount('roles')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission) => str($permission->name)->before('.')->value() ?: 'General');

        return view('livewire.lms.permissions.index', compact('permissions'));
    }
}
