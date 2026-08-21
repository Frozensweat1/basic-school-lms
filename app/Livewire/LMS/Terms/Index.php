<?php

namespace App\Livewire\LMS\Terms;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Term;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
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

    public bool $showForm = false;
    public bool $showDeleteConfirmation = false;
    public bool $isActive = false;
    public bool $isLocked = false;

    public ?int $editingId = null;
    public ?int $deletingId = null;

    public string $search = '';
    public string $academicYearId = '';
    public string $name = '';
    public string $sequence = '1';
    public string $startsAt = '';
    public string $endsAt = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Term::class);
    }

    public function create(): void
    {
        $this->authorize('create', Term::class);
        $this->ensureSchoolConfigured();

        $this->resetForm();
        $this->academicYearId = (string) $this->academicYearsQuery()
            ->where('is_active', true)
            ->value('id');
        $this->showForm = true;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(Term $term): void
    {
        $this->ensureSchoolRecord($term);
        $this->authorize('update', $term);

        $this->editingId = $term->id;
        $this->academicYearId = (string) $term->academic_year_id;
        $this->name = $term->name;
        $this->sequence = (string) $term->sequence;
        $this->startsAt = $term->starts_at->toDateString();
        $this->endsAt = $term->ends_at->toDateString();
        $this->isActive = $term->is_active;
        $this->isLocked = $term->is_locked;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $term = $this->editingId ? Term::findOrFail($this->editingId) : null;

        if ($term) {
            $this->ensureSchoolRecord($term);
        }

        $this->authorize($term ? 'update' : 'create', $term ?? Term::class);
        $schoolId = $this->ensureSchoolConfigured();

        try {
            $data = $this->validate([
                'academicYearId' => [
                    'required',
                    'integer',
                    Rule::exists('academic_years', 'id')->where('school_id', $schoolId),
                ],
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('terms', 'name')
                        ->where('academic_year_id', $this->academicYearId)
                        ->ignore($term?->id),
                ],
                'sequence' => ['required', 'integer', 'min:1', 'max:20'],
                'startsAt' => ['required', 'date', 'before:endsAt'],
                'endsAt' => ['required', 'date', 'after:startsAt'],
                'isActive' => ['boolean'],
                'isLocked' => ['boolean'],
            ]);

            $year = $this->academicYearsQuery()
                ->whereKey($data['academicYearId'])
                ->firstOrFail();

            if ($data['startsAt'] < $year->starts_at->toDateString()
                || $data['endsAt'] > $year->ends_at->toDateString()) {
                throw ValidationException::withMessages([
                    'startsAt' => 'Term dates must fall within the selected academic year.',
                ]);
            }

            if ($data['isActive'] && ! $year->is_active) {
                throw ValidationException::withMessages([
                    'isActive' => 'Activate the academic year before activating one of its terms.',
                ]);
            }

            DB::transaction(function () use ($term, $data): void {
                if ($data['isActive']) {
                    Term::query()
                        ->where('academic_year_id', $data['academicYearId'])
                        ->whereKeyNot($term?->id)
                        ->update(['is_active' => false]);
                }

                Term::updateOrCreate(
                    ['id' => $term?->id],
                    [
                        'academic_year_id' => $data['academicYearId'],
                        'name' => $data['name'],
                        'sequence' => $data['sequence'],
                        'starts_at' => $data['startsAt'],
                        'ends_at' => $data['endsAt'],
                        'is_active' => $data['isActive'],
                        'is_locked' => $data['isLocked'],
                    ],
                );
            });

            $this->showForm = false;
            $this->resetForm();
            $this->resetPage();
            LivewireAlert::title($term ? 'Term updated' : 'Term created')
                ->success()
                ->asToast()
                ->position('top-end')
                ->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the form')
                ->text('Correct the highlighted fields and try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save term')
                ->text('Please try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function confirmDelete(Term $term): void
    {
        $this->ensureSchoolRecord($term);
        $this->authorize('delete', $term);

        $this->deletingId = $term->id;
        $this->showDeleteConfirmation = true;
    }

    public function delete(): void
    {
        $term = Term::findOrFail($this->deletingId);
        $this->ensureSchoolRecord($term);
        $this->authorize('delete', $term);

        if ($term->is_active || $term->assessments()->exists() || $term->attendanceRecords()->exists()) {
            $this->addError('delete', 'Only an inactive term without assessments or attendance records can be deleted.');
            LivewireAlert::title('Term cannot be deleted')
                ->text('Deactivate it and remove its linked records first.')
                ->warning()
                ->asToast()
                ->position('top-end')
                ->show();

            return;
        }

        try {
            $term->delete();
            $this->showDeleteConfirmation = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Term deleted')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete term')
                ->text('Please try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
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
        $this->reset([
            'editingId',
            'deletingId',
            'academicYearId',
            'name',
            'sequence',
            'startsAt',
            'endsAt',
            'isActive',
            'isLocked',
        ]);

        $this->sequence = '1';
        $this->resetValidation();
    }

    private function schoolId(): int
    {
        return (int) School::query()->value('id');
    }

    private function ensureSchoolConfigured(): int
    {
        $schoolId = $this->schoolId();
        abort_unless($schoolId, 422, 'Configure a school before managing terms.');

        return $schoolId;
    }

    private function academicYearsQuery()
    {
        return AcademicYear::query()->where('school_id', $this->schoolId());
    }

    private function ensureSchoolRecord(Term $term): void
    {
        abort_unless(
            $this->academicYearsQuery()->whereKey($term->academic_year_id)->exists(),
            404,
        );
    }

    public function render()
    {
        $search = trim($this->search);

        return view('livewire.lms.terms.index', [
            'terms' => Term::query()
                ->with('academicYear')
                ->whereHas('academicYear', fn ($query) => $query->where('school_id', $this->schoolId()))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($termQuery) use ($search) {
                        $termQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhereHas('academicYear', fn ($yearQuery) => $yearQuery->where('name', 'like', "%{$search}%"));
                    });
                })
                ->orderByDesc('academic_year_id')
                ->orderBy('sequence')
                ->paginate(15),
            'years' => $this->academicYearsQuery()->orderByDesc('starts_at')->get(),
        ]);
    }
}
