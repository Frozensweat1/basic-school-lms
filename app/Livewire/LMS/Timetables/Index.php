<?php

namespace App\Livewire\LMS\Timetables;

use App\Models\{AcademicYear, ClassSubject, SchedulePeriod, School, Term, Timetable, TimetableEntry};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule, ValidationException};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;
    public bool $showFormModal = false, $showDeleteModal = false, $showGenerateModal = false;
    public ?int $editingId = null, $deletingId = null, $generatingId = null;
    public string $academicYearId = '', $termId = '', $name = '', $status = 'draft';

    public function mount(): void { $this->authorize('viewAny', Timetable::class); }
    public function create(): void { $this->authorize('create', Timetable::class); $this->resetForm(); $this->showFormModal = true; }

    public function edit(Timetable $timetable): void { $this->authorize('update', $timetable); $this->editingId = $timetable->id; $this->academicYearId = (string) $timetable->academic_year_id; $this->termId = (string) $timetable->term_id; $this->name = $timetable->name; $this->status = $timetable->status; $this->showFormModal = true; }

    public function save(): void
    {
        $timetable = $this->editingId ? Timetable::findOrFail($this->editingId) : null; $this->authorize($timetable ? 'update' : 'create', $timetable ?? Timetable::class);
        try {
            $data = $this->validate(['academicYearId' => ['required', 'integer', Rule::exists('academic_years', 'id')], 'termId' => ['required', 'integer', Rule::exists('terms', 'id')], 'name' => ['required', 'string', 'max:100'], 'status' => ['required', Rule::in(['draft', 'published', 'archived'])]]);
            $year = AcademicYear::whereKey($data['academicYearId'])->where('school_id', $this->schoolId())->firstOrFail(); abort_unless(Term::whereKey($data['termId'])->where('academic_year_id', $year->id)->exists(), 422, 'Choose a term from the selected academic year.');
            $duplicate = Timetable::where('academic_year_id', $year->id)->where('term_id', $data['termId'])->where('name', $data['name'])->when($timetable, fn ($q) => $q->whereKeyNot($timetable->id))->exists(); if ($duplicate) { $this->addError('name', 'A timetable with this name already exists for the selected term.'); return; }
            Timetable::updateOrCreate(['id' => $timetable?->id], ['academic_year_id' => $year->id, 'term_id' => $data['termId'], 'name' => $data['name'], 'status' => $data['status']]); $this->showFormModal = false; $this->resetForm(); LivewireAlert::title($timetable ? 'Timetable updated' : 'Timetable created')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) { LivewireAlert::title('Check the timetable form')->error()->asToast()->position('top-end')->show(); throw $exception; } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to save timetable')->error()->asToast()->position('top-end')->show(); }
    }

    public function confirmGenerate(Timetable $timetable): void { $this->authorize('update', $timetable); $this->generatingId = $timetable->id; $this->showGenerateModal = true; }

    public function generateAutomatically(): void
    {
        $timetable = Timetable::findOrFail($this->generatingId); $this->authorize('update', $timetable);
        try {
            $periods = SchedulePeriod::where('school_id', $this->schoolId())->orderBy('sequence')->get(); abort_unless($periods->isNotEmpty(), 422, 'Create schedule periods before generating a timetable.');
            $subjects = ClassSubject::with(['schoolClass', 'teacher'])->whereHas('schoolClass.academicYear', fn ($q) => $q->where('id', $timetable->academic_year_id)->where('school_id', $this->schoolId()))->orderBy('school_class_id')->get(); abort_unless($subjects->isNotEmpty(), 422, 'Assign subjects to classes before generating a timetable.');
            $unscheduled = [];
            DB::transaction(function () use ($timetable, $periods, $subjects, &$unscheduled) {
                TimetableEntry::where('timetable_id', $timetable->id)->delete(); $classLoad = []; $teacherLoad = []; $used = [];
                foreach ($subjects as $subject) {
                    $candidate = null;
                    foreach (range(1, 5) as $day) foreach ($periods as $period) { $key = $day.'-'.$period->id; if (isset($used[$subject->school_class_id][$key]) || ($subject->teacher_id && isset($used['teacher-'.$subject->teacher_id][$key]))) continue; $load = ($classLoad[$subject->school_class_id] ?? 0) * 10 + ($subject->teacher_id ? ($teacherLoad[$subject->teacher_id] ?? 0) : 0); if (! $candidate || $load < $candidate['load']) $candidate = ['day' => $day, 'period' => $period, 'load' => $load]; }
                    if (! $candidate) { $unscheduled[] = $subject->schoolClass->name.' · '.$subject->subject->name; continue; }
                    $key = $candidate['day'].'-'.$candidate['period']->id; TimetableEntry::create(['timetable_id' => $timetable->id, 'school_class_id' => $subject->school_class_id, 'class_subject_id' => $subject->id, 'teacher_id' => $subject->teacher_id, 'schedule_period_id' => $candidate['period']->id, 'day_of_week' => $candidate['day']]); $used[$subject->school_class_id][$key] = true; if ($subject->teacher_id) $used['teacher-'.$subject->teacher_id][$key] = true; $classLoad[$subject->school_class_id] = ($classLoad[$subject->school_class_id] ?? 0) + 1; if ($subject->teacher_id) $teacherLoad[$subject->teacher_id] = ($teacherLoad[$subject->teacher_id] ?? 0) + 1;
                }
            });
            $this->showGenerateModal = false; $this->generatingId = null; $message = count($unscheduled) ? 'Generated with '.count($unscheduled).' unscheduled subject(s).' : 'Timetable generated successfully.'; LivewireAlert::title($message)->success()->asToast()->position('top-end')->show();
        } catch (ValidationException|QueryException $exception) { report($exception); LivewireAlert::title('Unable to generate timetable')->error()->asToast()->position('top-end')->show(); } catch (Throwable $exception) { report($exception); LivewireAlert::title($exception->getMessage() ?: 'Unable to generate timetable')->error()->asToast()->position('top-end')->show(); }
    }

    public function confirmDelete(Timetable $timetable): void { $this->authorize('delete', $timetable); $this->deletingId = $timetable->id; $this->showDeleteModal = true; }
    public function delete(): void { $timetable = Timetable::findOrFail($this->deletingId); $this->authorize('delete', $timetable); try { $timetable->delete(); $this->showDeleteModal = false; $this->deletingId = null; LivewireAlert::title('Timetable deleted')->success()->asToast()->position('top-end')->show(); } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to delete timetable')->error()->asToast()->position('top-end')->show(); } }
    public function closeModals(): void { $this->showFormModal = false; $this->showDeleteModal = false; $this->showGenerateModal = false; $this->generatingId = null; $this->resetForm(); $this->resetErrorBag(); }

    public function render()
    {
        $years = AcademicYear::where('school_id', $this->schoolId())->orderByDesc('starts_at')->get(); return view('livewire.lms.timetables.index', ['timetables' => Timetable::with(['academicYear', 'term'])->whereIn('academic_year_id', $years->pluck('id'))->latest()->get(), 'years' => $years, 'terms' => Term::whereIn('academic_year_id', $years->pluck('id'))->orderBy('sequence')->get()]);
    }

    private function schoolId(): int { return (int) School::query()->value('id'); }
    private function resetForm(): void { $this->reset(['editingId', 'deletingId', 'academicYearId', 'termId', 'name', 'status']); $this->status = 'draft'; $this->resetValidation(); }
}
