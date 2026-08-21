<?php

namespace App\Livewire\LMS\Users;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = '';
    public string $search = '';

    public function mount(): void { $this->authorize('viewAny', User::class); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function create(): void { $this->authorize('create', User::class); $this->resetForm(); $this->showFormModal = true; }
    public function edit(User $user): void { $this->authorize('update', $user); $this->editingId=$user->id; $this->name=$user->name; $this->email=$user->email; $this->role=$user->roles->first()?->name ?? ''; $this->showFormModal=true; }

    public function save(): void
    {
        $user = $this->editingId ? User::findOrFail($this->editingId) : null;
        $this->authorize($user ? 'update' : 'create', $user ?? User::class);
        try {
            $data = $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user?->id)],
                'password' => [$user ? 'nullable' : 'required', 'string', 'min:10'],
                'role' => ['required', Rule::exists('roles', 'name')],
            ]);
            if (auth()->user()->hasRole('school_admin') && $data['role'] === 'super_admin') {
                $this->addError('role', 'School administrators cannot assign the super administrator role.');
                return;
            }
            if ($user?->id === auth()->id() && $data['role'] !== $user->roles->first()?->name) {
                $this->addError('role', 'You cannot change your own role.');
                return;
            }
            DB::transaction(function () use ($user, $data): void {
                $record = User::updateOrCreate(['id' => $user?->id], ['name'=>$data['name'], 'email'=>$data['email']] + (filled($data['password']) ? ['password'=>Hash::make($data['password'])] : []));
                $record->syncRoles($data['role']);
            });
            $this->showFormModal=false; $this->resetForm();
            LivewireAlert::title($user ? 'User updated' : 'User created')->success()->asToast()->show();
        } catch (ValidationException $exception) { LivewireAlert::title('Check the form')->error()->asToast()->show(); throw $exception; }
        catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to save user')->error()->asToast()->show(); }
    }

    public function confirmDelete(User $user): void { $this->authorize('delete', $user); $this->deletingId=$user->id; $this->showDeleteModal=true; }
    public function delete(): void { $user=User::findOrFail($this->deletingId); $this->authorize('delete',$user); try { $user->delete(); $this->showDeleteModal=false; LivewireAlert::title('User deleted')->success()->asToast()->show(); } catch(Throwable $e){report($e);LivewireAlert::title('Unable to delete user')->error()->asToast()->show();} }
    public function closeModals(): void { $this->showFormModal=false;$this->showDeleteModal=false;$this->resetForm();$this->resetErrorBag(); }
    private function resetForm(): void { $this->reset(['editingId','deletingId','name','email','password','role']);$this->resetValidation(); }
    public function render() { return view('livewire.lms.users.index',['users'=>User::with('roles')->where(fn($q)=>$q->where('name','like',"%{$this->search}%")->orWhere('email','like',"%{$this->search}%"))->when(auth()->user()->hasRole('school_admin'),fn($q)=>$q->whereDoesntHave('roles',fn($roles)=>$roles->where('name','super_admin')))->latest()->paginate(25),'roles'=>Role::when(auth()->user()->hasRole('school_admin'),fn($q)=>$q->where('name','!=','super_admin'))->orderBy('name')->get()]); }
}
