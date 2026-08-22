<?php

namespace App\Livewire\LMS\Teachers;

use App\Models\School;
use App\Models\Teacher;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    public bool $showFormModal = false, $showDeleteModal = false;
    public ?int $editingId = null, $deletingId = null;
    public string $search = '', $filterStatus = '', $filterAssignment = '';
    public string $employeeId = '', $firstName = '', $middleName = '', $lastName = '', $phone = '', $email = '', $employmentDate = '', $status = 'active';
    public function mount(): void
    {
        $this->authorize('viewAny', Teacher::class);
    }
    public function create(): void
    {
        $this->authorize('create', Teacher::class);
        $this->ensureSchoolConfigured();
        $this->resetForm();
        $this->showFormModal = true;
    }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedFilterAssignment(): void { $this->resetPage(); }
    public function clearFilters(): void { $this->reset(['search', 'filterStatus', 'filterAssignment']); $this->resetPage(); }
    public function edit(Teacher $teacher): void
    {
        $this->ensureSchoolRecord($teacher);
        $this->authorize('update', $teacher);
        $this->editingId = $teacher->id;
        $this->employeeId = $teacher->employee_id;
        $this->firstName = $teacher->first_name;
        $this->middleName = $teacher->middle_name ?? '';
        $this->lastName = $teacher->last_name;
        $this->phone = $teacher->phone ?? '';
        $this->email = $teacher->email ?? '';
        $this->employmentDate = $teacher->employment_date?->toDateString() ?? '';
        $this->status = $teacher->status;
        $this->showFormModal = true;
    }
    public function save(): void
    {
        $teacher = $this->editingId ? Teacher::findOrFail($this->editingId) : null;
        if ($teacher) { $this->ensureSchoolRecord($teacher); }
        $this->authorize($teacher ? 'update' : 'create', $teacher ?? Teacher::class);
        $schoolId = $this->ensureSchoolConfigured();
        try {
            $data = $this->validate(['employeeId' => ['required', 'string', 'max:50', Rule::unique('teachers', 'employee_id')->ignore($teacher?->id)], 'firstName' => ['required', 'string', 'max:100'], 'middleName' => ['nullable', 'string', 'max:100'], 'lastName' => ['required', 'string', 'max:100'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'], 'employmentDate' => ['nullable', 'date'], 'status' => ['required', 'in:active,inactive,retired']]);
            Teacher::updateOrCreate(['id' => $teacher?->id], ['school_id' => $schoolId, 'employee_id' => $data['employeeId'], 'first_name' => $data['firstName'], 'middle_name' => $data['middleName'] ?: null, 'last_name' => $data['lastName'], 'phone' => $data['phone'] ?: null, 'email' => $data['email'] ?: null, 'employment_date' => $data['employmentDate'] ?: null, 'status' => $data['status']]);
            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title($teacher ? 'Teacher updated' : 'Teacher added')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $e) {
            LivewireAlert::title('Check the form')->error()->asToast()->position('top-end')->show();
            throw $e;
        } catch (Throwable $e) {
            report($e);
            LivewireAlert::title('Unable to save teacher')->error()->asToast()->position('top-end')->show();
        }
    }
    public function confirmDelete(Teacher $teacher): void
    {
        $this->ensureSchoolRecord($teacher);
        $this->authorize('delete', $teacher);
        $this->deletingId = $teacher->id;
        $this->showDeleteModal = true;
    }
    public function delete(): void
    {
        $teacher = Teacher::findOrFail($this->deletingId);
        $this->ensureSchoolRecord($teacher);
        $this->authorize('delete', $teacher);
        if ($teacher->classSubjects()->exists() || $teacher->classes()->exists()) {
            $this->addError('delete', 'Teachers with class assignments must be marked inactive instead.');
            LivewireAlert::title('Teacher cannot be deleted')->warning()->asToast()->position('top-end')->show();
            return;
        }
        try {
            $teacher->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Teacher archived')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $e) {
            report($e);
            LivewireAlert::title('Unable to archive teacher')->error()->asToast()->position('top-end')->show();
        }
    }
    public function closeModals(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
        $this->resetErrorBag();
    }
    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'employeeId', 'firstName', 'middleName', 'lastName', 'phone', 'email', 'employmentDate', 'status']);
        $this->status = 'active';
        $this->resetValidation();
    }
    private function schoolId(): int { return (int) School::query()->value('id'); }
    private function ensureSchoolConfigured(): int
    {
        $schoolId = $this->schoolId();
        abort_unless($schoolId, 422, 'Configure a school before managing teachers.');
        return $schoolId;
    }
    private function ensureSchoolRecord(Teacher $teacher): void
    {
        abort_unless($teacher->school_id === $this->schoolId(), 404);
    }
    public function render()
    {
        $search = trim($this->search);

        return view('livewire.lms.teachers.index', [
            'teachers' => Teacher::query()
                ->where('school_id', $this->schoolId())
                ->withCount(['classes', 'classSubjects'])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($teachers) use ($search): void {
                        $teachers->where('employee_id', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                })
                ->when(filled($this->filterStatus), fn ($query) => $query->where('status', $this->filterStatus))
                ->when($this->filterAssignment === 'assigned', fn ($query) => $query->where(function ($teachers): void {
                    $teachers->whereHas('classes')->orWhereHas('classSubjects');
                }))
                ->when($this->filterAssignment === 'unassigned', fn ($query) => $query
                    ->whereDoesntHave('classes')
                    ->whereDoesntHave('classSubjects'))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(15),
        ]);
    }
}
