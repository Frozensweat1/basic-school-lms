<?php

namespace App\Livewire\LMS\TimetableEntries;

use App\Models\{ClassSubject, SchedulePeriod, Timetable, TimetableEntry};
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\{Rule, ValidationException};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;
    public Timetable $timetable;
    public bool $showFormModal = false;
    public string $classSubjectId = '', $periodId = '', $dayOfWeek = '1', $room = '';

    public function mount(Timetable $timetable): void { $this->authorize('update', $timetable); $this->timetable = $timetable; }
    public function create(): void { $this->authorize('update', $this->timetable); $this->reset(['classSubjectId', 'periodId', 'dayOfWeek', 'room']); $this->dayOfWeek = '1'; $this->showFormModal = true; }

    public function save(): void
    {
        $this->authorize('update', $this->timetable);
        try {
            $data = $this->validate(['classSubjectId' => ['required', 'integer', Rule::exists('class_subjects', 'id')], 'periodId' => ['required', 'integer', Rule::exists('schedule_periods', 'id')], 'dayOfWeek' => ['required', 'integer', 'between:1,5'], 'room' => ['nullable', 'string', 'max:100']]);
            $subject = ClassSubject::with(['schoolClass.academicYear'])->findOrFail((int) $data['classSubjectId']); $schoolId = (int) $this->timetable->academicYear->school_id; $period = SchedulePeriod::whereKey((int) $data['periodId'])->where('school_id', $schoolId)->firstOrFail();
            abort_unless($subject->schoolClass->academic_year_id === $this->timetable->academic_year_id, 422, 'Choose a class subject from this timetable academic year.');
            $teacherId = $subject->teacher_id; $classConflict = TimetableEntry::where('timetable_id', $this->timetable->id)->where('school_class_id', $subject->school_class_id)->where('schedule_period_id', $period->id)->where('day_of_week', $data['dayOfWeek'])->exists(); $teacherConflict = $teacherId && TimetableEntry::where('timetable_id', $this->timetable->id)->where('teacher_id', $teacherId)->where('schedule_period_id', $period->id)->where('day_of_week', $data['dayOfWeek'])->exists();
            if ($classConflict || $teacherConflict) { $this->addError('periodId', $classConflict ? 'This class already has a lesson in this slot.' : 'This teacher is already assigned in this slot.'); return; }
            TimetableEntry::create(['timetable_id' => $this->timetable->id, 'school_class_id' => $subject->school_class_id, 'class_subject_id' => $subject->id, 'teacher_id' => $teacherId, 'schedule_period_id' => $period->id, 'day_of_week' => $data['dayOfWeek'], 'room' => $data['room'] ?: null]); $this->showFormModal = false; LivewireAlert::title('Timetable entry added')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) { LivewireAlert::title('Check the entry')->error()->asToast()->position('top-end')->show(); throw $exception; } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to save entry')->error()->asToast()->position('top-end')->show(); }
    }

    public function delete(int $id): void { $this->authorize('update', $this->timetable); TimetableEntry::whereKey($id)->where('timetable_id', $this->timetable->id)->firstOrFail()->delete(); LivewireAlert::title('Entry removed')->success()->asToast()->position('top-end')->show(); }
    public function closeModal(): void { $this->showFormModal = false; $this->resetErrorBag(); }

    public function render()
    {
        return view('livewire.lms.timetable-entries.index', ['entries' => $this->timetable->entries()->with(['schoolClass', 'classSubject.subject', 'teacher', 'schedulePeriod'])->orderBy('day_of_week')->get(), 'classSubjects' => ClassSubject::with(['schoolClass', 'subject'])->whereHas('schoolClass', fn ($q) => $q->where('academic_year_id', $this->timetable->academic_year_id))->get(), 'periods' => SchedulePeriod::where('school_id', $this->timetable->academicYear->school_id)->orderBy('sequence')->get()]);
    }
}
