<?php

namespace App\Livewire\LMS\AcademicYears;

use App\Models\AcademicYear;
use App\Models\School;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Throwable;
use Livewire\WithPagination;

#[Layout('layouts.lms')]
class Index extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public bool $showDeleteConfirmation = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;
    public string $search = '';
    public string $name = '';
    public string $startsAt = '';
    public string $endsAt = '';
    public bool $isActive = false;

    public function mount(): void
    {
        $this->authorize('viewAny', AcademicYear::class);
    }

    public function create(): void
    {
        $this->authorize('create', AcademicYear::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(AcademicYear $academicYear): void
    {
        $this->ensureSchoolRecord($academicYear);
        $this->authorize('update', $academicYear);
        $this->editingId = $academicYear->id;
        $this->name = $academicYear->name;
        $this->startsAt = $academicYear->starts_at->toDateString();
        $this->endsAt = $academicYear->ends_at->toDateString();
        $this->isActive = $academicYear->is_active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $year = $this->editingId ? AcademicYear::findOrFail($this->editingId) : null;
        if ($year) {
            $this->ensureSchoolRecord($year);
        }
        $this->authorize($year ? 'update' : 'create', $year ?? AcademicYear::class);

        $schoolId = $year?->school_id ?? School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school before creating an academic year.');

        try {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('academic_years', 'name')->where('school_id', $schoolId)->ignore($year?->id)],
            'startsAt' => ['required', 'date', 'before:endsAt'],
            'endsAt' => ['required', 'date', 'after:startsAt'],
            'isActive' => ['boolean'],
        ]);

        DB::transaction(function () use ($year, $schoolId, $data): void {
            if ($data['isActive']) {
                AcademicYear::where('school_id', $schoolId)->whereKeyNot($year?->id)->update(['is_active' => false]);
            }

            AcademicYear::updateOrCreate(
                ['id' => $year?->id],
                [
                    'school_id' => $schoolId,
                    'name' => $data['name'],
                    'starts_at' => $data['startsAt'],
                    'ends_at' => $data['endsAt'],
                    'is_active' => $data['isActive'],
                ],
            );
        });

        $this->showForm = false;
        $this->resetForm();
        $this->resetPage();
        LivewireAlert::title($year ? 'Academic year updated' : 'Academic year created')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the form')->text('Correct the highlighted fields and try again.')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save academic year')->text('Please try again.')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(AcademicYear $academicYear): void
    {
        $this->ensureSchoolRecord($academicYear);
        $this->authorize('delete', $academicYear);
        $this->deletingId = $academicYear->id;
        $this->showDeleteConfirmation = true;
    }

    public function delete(): void
    {
        $year = AcademicYear::findOrFail($this->deletingId);
        $this->ensureSchoolRecord($year);
        $this->authorize('delete', $year);

        if ($year->is_active || $year->terms()->exists() || $year->classes()->exists()) {
            $this->addError('delete', 'Only an inactive academic year without terms or classes can be deleted.');
            LivewireAlert::title('Academic year cannot be deleted')->text('Deactivate it and remove its terms and classes first.')->warning()->asToast()->position('top-end')->show();
            return;
        }

        try {
            $year->delete();
            $this->showDeleteConfirmation = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Academic year deleted')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete academic year')->text('Please try again.')->error()->asToast()->position('top-end')->show();
        }
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirmation = false;
        $this->deletingId = null;
        $this->resetErrorBag();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'name', 'startsAt', 'endsAt', 'isActive']);
        $this->resetValidation();
    }

    private function schoolId(): int
    {
        return (int) School::query()->value('id');
    }

    private function ensureSchoolRecord(AcademicYear $academicYear): void
    {
        abort_unless((int) $academicYear->school_id === $this->schoolId(), 404);
    }

    public function render()
    {
        $search = trim($this->search);

        return view('livewire.lms.academic-years.index', [
            'years' => AcademicYear::query()
                ->where('school_id', $this->schoolId())
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->withCount(['terms', 'classes'])
                ->orderByDesc('starts_at')
                ->paginate(15),
        ]);
    }
}
