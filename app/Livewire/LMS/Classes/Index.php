<?php

namespace App\Livewire\LMS\Classes;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Stream;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
    public string $academicYearId = '', $streamId = '', $name = '', $code = '', $status = 'active';
    public string $search = '';
    public function mount(): void
    {
        $this->authorize('viewAny', SchoolClass::class);
    }
    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }
    public function create(): void
    {
        $this->authorize('create', SchoolClass::class);
        $this->resetForm();
        $this->academicYearId = (string)AcademicYear::where('is_active', true)->value('id');
        $this->showFormModal = true;
    }
    public function edit(SchoolClass $schoolClass): void
    {
        $this->authorize('update', $schoolClass);
        $this->editingId = $schoolClass->id;
        $this->academicYearId = (string)$schoolClass->academic_year_id;
        $this->streamId = (string)($schoolClass->stream_id ?? '');
        $this->name = $schoolClass->name;
        $this->code = $schoolClass->code ?? '';
        $this->status = $schoolClass->status;
        $this->resetValidation();
        $this->showFormModal = true;
    }
    public function save(): void
    {
        $class = $this->editingId ? SchoolClass::findOrFail($this->editingId) : null;
        $this->authorize($class ? 'update' : 'create', $class ?? SchoolClass::class);
        try {
            $data = $this->validate(['academicYearId' => ['required', 'integer', 'exists:academic_years,id'], 'streamId' => ['nullable', 'integer', 'exists:streams,id'], 'name' => ['required', 'string', 'max:100'], 'code' => ['nullable', 'string', 'max:50'], 'status' => ['required', 'in:active,archived']]);
            $duplicate = SchoolClass::where('academic_year_id', $data['academicYearId'])->where('name', $data['name'])->when($data['streamId'] ?? null, fn($query, $streamId) => $query->where('stream_id', $streamId), fn($query) => $query->whereNull('stream_id'))->whereKeyNot($class?->id)->exists();
            if ($duplicate) throw ValidationException::withMessages(['name' => 'This class and stream already exist for the selected academic year.']);
            SchoolClass::updateOrCreate(['id' => $class?->id], ['academic_year_id' => $data['academicYearId'], 'stream_id' => $data['streamId'] ?: null, 'name' => $data['name'], 'code' => $data['code'] ?: null, 'status' => $data['status']]);
            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title($class ? 'Class updated' : 'Class created')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $e) {
            LivewireAlert::title('Check the form')->text('Correct the highlighted fields and try again.')->error()->asToast()->position('top-end')->show();
            throw $e;
        } catch (Throwable $e) {
            report($e);
            LivewireAlert::title('Unable to save class')->text('Please try again.')->error()->asToast()->position('top-end')->show();
        }
    }
    public function confirmDelete(SchoolClass $schoolClass): void
    {
        $this->authorize('delete', $schoolClass);
        $this->deletingId = $schoolClass->id;
        $this->showDeleteModal = true;
    }
    public function delete(): void
    {
        $class = SchoolClass::findOrFail($this->deletingId);
        $this->authorize('delete', $class);
        if ($class->enrollments()->exists() || $class->classSubjects()->exists() || $class->attendanceRecords()->exists()) {
            $this->addError('delete', 'Classes with enrolments, subjects, or attendance records cannot be deleted.');
            LivewireAlert::title('Class cannot be deleted')->warning()->asToast()->position('top-end')->show();
            return;
        }
        try {
            $class->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            LivewireAlert::title('Class deleted')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $e) {
            report($e);
            LivewireAlert::title('Unable to delete class')->error()->asToast()->position('top-end')->show();
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
        $this->reset(['editingId', 'deletingId', 'academicYearId', 'streamId', 'name', 'code', 'status']);
        $this->status = 'active';
        $this->resetValidation();
    }
    public function render()
    {
        $search = trim($this->search);

        return view('livewire.lms.classes.index', [
            'classes' => SchoolClass::query()
                ->with(['academicYear', 'stream'])
                ->withCount('enrollments')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($classes) use ($search): void {
                        $classes->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('status', 'like', "%{$search}%")
                            ->orWhereHas('academicYear', fn ($years) => $years->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('stream', fn ($streams) => $streams->where('name', 'like', "%{$search}%"));
                    });
                })
                ->orderByDesc('academic_year_id')
                ->orderBy('name')
                ->paginate(15),
            'years' => AcademicYear::orderByDesc('starts_at')->get(),
            'streams' => Stream::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
