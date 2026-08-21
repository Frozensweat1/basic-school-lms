<?php

namespace App\Livewire\LMS\AuditLogs;

use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function render()
    {
        $schoolId = (int) School::query()->value('id');
        $logs = AuditLog::with('user')
            ->where('school_id', $schoolId)
            ->when($this->search !== '', fn ($query) => $query->where(fn ($searchQuery) => $searchQuery->where('event', 'like', '%'.$this->search.'%')->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$this->search.'%'))))
            ->latest()
            ->paginate(25);

        return view('livewire.lms.audit-logs.index', compact('logs'));
    }
}
