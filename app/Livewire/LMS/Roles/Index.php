<?php

namespace App\Livewire\LMS\Roles;

use App\Models\School;
use App\Support\AuditLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;
    public string $name = '';
    public array $selectedPermissions = [];

    public function mount(): void
    {
        $this->authorize('viewAny', \App\Models\User::class);
    }

    public function create(): void
    {
        $this->authorize('create', \App\Models\User::class);
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $role = $this->roleQuery()->findOrFail($id);
        $this->ensureCanManage($role, false);
        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions()->pluck('permissions.id')->map(fn ($permissionId) => (string) $permissionId)->all();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $role = $this->editingId ? $this->roleQuery()->findOrFail($this->editingId) : null;
        $this->ensureCanManage($role, ! $role);

        try {
            $data = $this->validate([
                'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->where(fn ($query) => $query->where('guard_name', 'web'))->ignore($role?->id)],
                'selectedPermissions' => ['array'],
                'selectedPermissions.*' => ['integer', Rule::exists('permissions', 'id')->where(fn ($query) => $query->where('guard_name', 'web'))],
            ]);
            $permissionIds = Permission::query()->where('guard_name', 'web')->whereIn('id', $data['selectedPermissions'] ?? [])->pluck('id')->all();
            $oldValues = $role ? ['name' => $role->name, 'permissions' => $role->permissions()->pluck('name')->sort()->values()->all()] : [];

            $record = DB::transaction(function () use ($role, $data, $permissionIds): Role {
                $record = $role ?: Role::create(['name' => trim($data['name']), 'guard_name' => 'web']);
                if ($role) {
                    $record->name = trim($data['name']);
                    $record->save();
                }
                $record->syncPermissions($permissionIds);
                return $record->fresh('permissions');
            });
            app(AuditLogger::class)->record($role ? 'role.updated' : 'role.created', $record, $oldValues, ['name' => $record->name, 'permissions' => $record->permissions->pluck('name')->sort()->values()->all()], (int) School::query()->value('id'));

            $this->closeModals();
            LivewireAlert::title($role ? 'Role updated' : 'Role created')->success()->asToast()->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the role form')->error()->asToast()->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save role')->error()->asToast()->show();
        }
    }

    public function confirmDelete(int $id): void
    {
        $role = $this->roleQuery()->findOrFail($id);
        $this->ensureCanManage($role, false);
        $this->deletingId = $role->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $role = $this->roleQuery()->findOrFail($this->deletingId);
        $this->ensureCanManage($role, false);

        try {
            $oldValues = ['name' => $role->name, 'permissions' => $role->permissions()->pluck('name')->sort()->values()->all()];
            $role->delete();
            app(AuditLogger::class)->record('role.deleted', $role, $oldValues, [], (int) School::query()->value('id'));
            $this->closeModals();
            LivewireAlert::title('Role deleted')->success()->asToast()->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete role')->error()->asToast()->show();
        }
    }

    public function closeModals(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.lms.roles.index', [
            'roles' => $this->roleQuery()->with('permissions')->withCount('users')->orderBy('name')->get(),
            'permissions' => Permission::query()->where('guard_name', 'web')->orderBy('name')->get(),
        ]);
    }

    private function roleQuery()
    {
        return Role::query()->where('guard_name', 'web');
    }

    private function ensureCanManage(?Role $role, bool $creating): void
    {
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'school_admin']), 403);

        if (! $creating && ! $role) {
            abort(404);
        }

        $protected = ['super_admin', 'school_admin', 'teacher', 'student', 'parent'];
        if (auth()->user()->hasRole('school_admin') && $role && in_array($role->name, $protected, true)) {
            abort(403, 'Built-in roles can only be managed by a super administrator.');
        }

        if (auth()->user()->hasRole('school_admin') && $creating && trim($this->name) === 'super_admin') {
            abort(403, 'School administrators cannot create the super administrator role.');
        }
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'name', 'selectedPermissions']);
        $this->selectedPermissions = [];
        $this->resetValidation();
    }
}
