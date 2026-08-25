<?php

namespace App\Livewire\LMS\AssessmentComponents;

use App\Models\AcademicYear;
use App\Models\AssessmentComponent;
use App\Models\School;
use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;
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

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $termId = '';

    public string $name = '';

    public string $weight = '';

    public string $sequence = '0';

    public string $search = '';

    public string $filterAcademicYearId = '';

    public string $filterTermId = '';

    public function mount(): void
    {
        $this->authorize('viewAny', AssessmentComponent::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterAcademicYearId(): void
    {
        $this->filterTermId = '';
        $this->resetPage();
    }

    public function updatedFilterTermId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterAcademicYearId', 'filterTermId']);
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', AssessmentComponent::class);
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(AssessmentComponent $component): void
    {
        $this->assertVisible($component);
        $this->authorize('update', $component);

        $this->editingId = $component->id;
        $this->termId = (string) $component->term_id;
        $this->name = $component->name;
        $this->weight = (string) $component->weight;
        $this->sequence = (string) $component->sequence;
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $component = $this->editingId
            ? $this->scopedComponents()->findOrFail($this->editingId)
            : null;

        $this->authorize($component ? 'update' : 'create', $component ?? AssessmentComponent::class);

        try {
            $data = $this->validate([
                'termId' => ['required', 'integer', Rule::exists('terms', 'id')],
                'name' => ['required', 'string', 'max:100'],
                'weight' => ['required', 'numeric', 'min:0.01', 'max:100'],
                'sequence' => ['required', 'integer', 'min:0', 'max:9999'],
            ]);

            $term = Term::query()
                ->whereKey($data['termId'])
                ->whereHas('academicYear', fn (Builder $years) => $years->where('school_id', $this->schoolId()))
                ->firstOrFail();

            $totalWeight = AssessmentComponent::query()
                ->where('term_id', $term->id)
                ->when($component, fn (Builder $query) => $query->whereKeyNot($component->id))
                ->sum('weight') + (float) $data['weight'];

            if ($totalWeight > 100) {
                throw ValidationException::withMessages([
                    'weight' => 'Component weights cannot exceed 100% for this term.',
                ]);
            }

            $duplicateName = AssessmentComponent::query()
                ->where('term_id', $term->id)
                ->where('name', $data['name'])
                ->when($component, fn (Builder $query) => $query->whereKeyNot($component->id))
                ->exists();

            if ($duplicateName) {
                throw ValidationException::withMessages([
                    'name' => 'This component already exists for the selected term.',
                ]);
            }

            AssessmentComponent::updateOrCreate(
                ['id' => $component?->id],
                [
                    'term_id' => $term->id,
                    'name' => $data['name'],
                    'weight' => $data['weight'],
                    'sequence' => $data['sequence'],
                ],
            );

            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title($component ? 'Component updated' : 'Component added')
                ->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the component form')
                ->text('Correct the highlighted fields and try again.')
                ->error()->asToast()->position('top-end')->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save component')
                ->text('Please try again.')
                ->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(AssessmentComponent $component): void
    {
        $this->assertVisible($component);
        $this->authorize('delete', $component);

        $this->deletingId = $component->id;
        $this->resetErrorBag('delete');
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $component = $this->deletingId
            ? $this->scopedComponents()->findOrFail($this->deletingId)
            : null;

        abort_unless($component, 404);
        $this->authorize('delete', $component);

        if ($component->assessments()->exists()) {
            $this->addError('delete', 'This component is already linked to assessments. Reassign those assessments before removing it.');
            LivewireAlert::title('Component cannot be removed')->warning()->asToast()->position('top-end')->show();

            return;
        }

        try {
            $component->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Component deleted')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete component')
                ->text('Please try again.')
                ->error()->asToast()->position('top-end')->show();
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
        $schoolId = $this->schoolId();
        $search = trim($this->search);
        $terms = Term::query()
            ->with('academicYear')
            ->whereHas('academicYear', fn (Builder $years) => $years->where('school_id', $schoolId))
            ->orderByDesc('academic_year_id')
            ->orderBy('sequence')
            ->get();

        $components = $this->scopedComponents()
            ->with('term.academicYear')
            ->withCount('assessments')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $items) use ($search): void {
                    $items->where('name', 'like', "%{$search}%")
                        ->orWhereHas('term', fn (Builder $term) => $term->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('term.academicYear', fn (Builder $year) => $year->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(filled($this->filterAcademicYearId), fn (Builder $query) => $query->whereHas('term', fn (Builder $term) => $term->where('academic_year_id', $this->filterAcademicYearId)))
            ->when(filled($this->filterTermId), fn (Builder $query) => $query->where('term_id', $this->filterTermId))
            ->orderBy('term_id')
            ->orderBy('sequence')
            ->paginate(15);

        return view('livewire.lms.assessment-components.index', [
            'components' => $components,
            'years' => AcademicYear::query()->where('school_id', $schoolId)->orderByDesc('starts_at')->get(),
            'terms' => $terms,
            'totalWeight' => (clone $this->scopedComponents())->sum('weight'),
            'assessmentRouteName' => $this->managingAsTeacher()
                ? 'lms.assessments.teacher.index'
                : 'lms.assessments.admin.index',
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'termId', 'name', 'weight', 'sequence']);
        $this->sequence = '0';
        $this->resetValidation();
    }

    private function schoolId(): int
    {
        $schoolId = School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school before managing assessment components.');

        return (int) $schoolId;
    }

    private function scopedComponents(): Builder
    {
        return AssessmentComponent::query()
            ->whereHas('term.academicYear', fn (Builder $years) => $years->where('school_id', $this->schoolId()));
    }

    private function assertVisible(AssessmentComponent $component): void
    {
        abort_unless($this->scopedComponents()->whereKey($component->id)->exists(), 404);
    }

    private function managingAsTeacher(): bool
    {
        return auth()->user()->hasRole('teacher')
            && ! auth()->user()->hasAnyRole(['super_admin', 'school_admin']);
    }
}
