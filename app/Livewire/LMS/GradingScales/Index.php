<?php

namespace App\Livewire\LMS\GradingScales;

use App\Models\GradingScale;
use App\Models\School;
use App\Models\SubjectResult;
use Illuminate\Database\Eloquent\Builder;
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

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $search = '';

    public string $filterUsage = '';

    public string $grade = '';

    public string $minimum = '';

    public string $maximum = '';

    public string $remark = '';

    public string $sequence = '0';

    public function mount(): void
    {
        $this->authorize('viewAny', GradingScale::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterUsage(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterUsage']);
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', GradingScale::class);

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(GradingScale $scale): void
    {
        $this->assertVisible($scale);
        $this->authorize('update', $scale);

        $this->editingId = $scale->id;
        $this->grade = $scale->grade;
        $this->minimum = $scale->minimum;
        $this->maximum = $scale->maximum;
        $this->remark = $scale->remark ?? '';
        $this->sequence = (string) $scale->sequence;
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $schoolId = $this->schoolId();
        $scale = $this->editingId
            ? $this->scopedScales()->findOrFail($this->editingId)
            : null;

        $this->authorize($scale ? 'update' : 'create', $scale ?? GradingScale::class);

        try {
            $this->grade = strtoupper(trim($this->grade));
            $this->remark = trim($this->remark);

            $data = $this->validate([
                'grade' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('grading_scales', 'grade')
                        ->where(fn ($query) => $query->where('school_id', $schoolId))
                        ->ignore($scale?->id),
                ],
                'minimum' => ['required', 'numeric', 'min:0', 'max:100'],
                'maximum' => ['required', 'numeric', 'min:0', 'max:100', 'gte:minimum'],
                'remark' => ['nullable', 'string', 'max:255'],
                'sequence' => ['required', 'integer', 'min:0', 'max:9999'],
            ]);

            $overlapExists = $this->scopedScales()
                ->where('minimum', '<=', $data['maximum'])
                ->where('maximum', '>=', $data['minimum'])
                ->when($scale, fn (Builder $query) => $query->whereKeyNot($scale->id))
                ->exists();

            if ($overlapExists) {
                throw ValidationException::withMessages([
                    'minimum' => 'This score range overlaps an existing grade. Adjust the minimum or maximum score.',
                ]);
            }

            DB::transaction(function () use ($scale, $schoolId, $data): void {
                GradingScale::updateOrCreate(
                    ['id' => $scale?->id],
                    [
                        'school_id' => $schoolId,
                        'grade' => $data['grade'],
                        'minimum' => $data['minimum'],
                        'maximum' => $data['maximum'],
                        'remark' => filled($data['remark']) ? $data['remark'] : null,
                        'sequence' => $data['sequence'],
                    ],
                );

                $this->synchroniseExistingResults($schoolId);
            });

            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title($scale ? 'Grading scale updated' : 'Grading scale added')
                ->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the grading scale form')
                ->text('Correct the highlighted fields and try again.')
                ->error()->asToast()->position('top-end')->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save grading scale')
                ->text('Please try again.')
                ->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(GradingScale $scale): void
    {
        $this->assertVisible($scale);
        $this->authorize('delete', $scale);

        $this->deletingId = $scale->id;
        $this->resetErrorBag('delete');
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $scale = $this->deletingId
            ? $this->scopedScales()->findOrFail($this->deletingId)
            : null;

        abort_unless($scale, 404);
        $this->authorize('delete', $scale);

        if ($scale->subjectResults()->exists()) {
            $this->addError('delete', 'This grade is already used by subject results and cannot be removed. Update the range instead.');
            LivewireAlert::title('Grade cannot be removed')
                ->warning()->asToast()->position('top-end')->show();

            return;
        }

        try {
            $scale->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Grading scale deleted')
                ->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete grading scale')
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
        $filteredScales = $this->scopedScales()
            ->withCount('subjectResults')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $items) use ($search): void {
                    $items->where('grade', 'like', "%{$search}%")
                        ->orWhere('remark', 'like', "%{$search}%")
                        ->orWhere('minimum', 'like', "%{$search}%")
                        ->orWhere('maximum', 'like', "%{$search}%");
                });
            })
            ->when($this->filterUsage === 'used', fn (Builder $query) => $query->has('subjectResults'))
            ->when($this->filterUsage === 'unused', fn (Builder $query) => $query->doesntHave('subjectResults'));

        $scales = (clone $filteredScales)
            ->orderBy('sequence')
            ->orderByDesc('maximum')
            ->paginate(15);

        return view('livewire.lms.grading-scales.index', [
            'scales' => $scales,
            'configuredCount' => (clone $filteredScales)->count(),
            'usedCount' => (clone $filteredScales)->has('subjectResults')->count(),
            'minimumScore' => (clone $filteredScales)->min('minimum'),
            'maximumScore' => (clone $filteredScales)->max('maximum'),
            'publishedResultCount' => SubjectResult::query()
                ->where('status', 'published')
                ->whereHas('classSubject.schoolClass.academicYear', fn (Builder $years) => $years->where('school_id', $schoolId))
                ->count(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'grade', 'minimum', 'maximum', 'remark', 'sequence']);
        $this->sequence = '0';
        $this->resetValidation();
    }

    private function schoolId(): int
    {
        $schoolId = School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school before managing grading scales.');

        return (int) $schoolId;
    }

    private function scopedScales(): Builder
    {
        return GradingScale::query()->where('school_id', $this->schoolId());
    }

    private function assertVisible(GradingScale $scale): void
    {
        abort_unless($this->scopedScales()->whereKey($scale->id)->exists(), 404);
    }

    private function synchroniseExistingResults(int $schoolId): void
    {
        $scales = $this->scopedScales()->orderBy('sequence')->orderByDesc('maximum')->get();

        SubjectResult::query()
            ->whereNotNull('total_score')
            ->whereHas('classSubject.schoolClass.academicYear', fn (Builder $years) => $years->where('school_id', $schoolId))
            ->orderBy('id')
            ->eachById(function (SubjectResult $result) use ($scales): void {
                $scale = $scales->first(fn (GradingScale $item) => (float) $item->minimum <= (float) $result->total_score
                    && (float) $item->maximum >= (float) $result->total_score);

                if ((int) $result->grading_scale_id !== (int) $scale?->id || $result->grade !== $scale?->grade) {
                    $result->update([
                        'grading_scale_id' => $scale?->id,
                        'grade' => $scale?->grade,
                    ]);
                }
            });
    }
}
