<?php

namespace App\Livewire\LMS\Attendance;

use App\Models\{AcademicYear, AttendanceRecord, School, SchoolClass, Term};
use App\Support\AuditLogger;
use App\Support\AttendanceSummary;
use Illuminate\Database\Eloquent\Builder;
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

    public string $academicYearId = '', $termId = '', $classId = '', $attendanceDate = '';
    public array $statuses = [], $remarks = [];
    public bool $registerLoaded = false;

    public function mount(): void { $this->authorize('viewAny', AttendanceRecord::class); $this->attendanceDate = now()->toDateString(); }

    public function loadRegister(): void
    {
        $this->authorize('create', AttendanceRecord::class);
        $data = $this->validate(['academicYearId' => ['required', 'integer'], 'termId' => ['required', 'integer'], 'classId' => ['required', 'integer'], 'attendanceDate' => ['required', 'date', 'before_or_equal:today']]);
        $class = $this->scopedClasses()->findOrFail((int) $data['classId']);
        abort_unless($class->academic_year_id === (int) $data['academicYearId'] && $this->scopedTerms()->whereKey((int) $data['termId'])->where('academic_year_id', $class->academic_year_id)->exists(), 422, 'Choose a class and term from the same academic year.');
        $this->assertClassAccess($class);
        $existing = AttendanceRecord::where('school_class_id', $class->id)->whereDate('attendance_date', $data['attendanceDate'])->get()->keyBy('student_id');
        $this->statuses = []; $this->remarks = [];
        foreach ($class->enrollments()->with('student')->where('status', 'active')->get() as $enrollment) { $this->statuses[$enrollment->student_id] = $existing[$enrollment->student_id]->status ?? 'present'; $this->remarks[$enrollment->student_id] = $existing[$enrollment->student_id]->remarks ?? ''; }
        $this->registerLoaded = true;
    }

    public function save(): void
    {
        $this->authorize('create', AttendanceRecord::class);
        try {
            $this->validate(['academicYearId' => ['required', 'integer'], 'termId' => ['required', 'integer'], 'classId' => ['required', 'integer'], 'attendanceDate' => ['required', 'date', 'before_or_equal:today'], 'statuses' => ['required', 'array'], 'statuses.*' => [Rule::in(['present', 'absent', 'late', 'excused'])], 'remarks' => ['array'], 'remarks.*' => ['nullable', 'string', 'max:1000']]);
            $class = $this->scopedClasses()->findOrFail((int) $this->classId); $this->assertClassAccess($class); abort_unless($class->academic_year_id === (int) $this->academicYearId && $this->scopedTerms()->whereKey((int) $this->termId)->where('academic_year_id', $class->academic_year_id)->exists(), 422, 'Choose a class and term from the same academic year.');
            $allowedIds = $class->enrollments()->where('status', 'active')->pluck('student_id')->map(fn ($id) => (string) $id)->all();
            $schoolId = $this->schoolId();
            DB::transaction(function () use ($class, $allowedIds, $schoolId): void {
                foreach ($allowedIds as $studentId) {
                    $record = AttendanceRecord::firstOrNew(['student_id' => (int) $studentId, 'school_class_id' => $class->id, 'attendance_date' => $this->attendanceDate]);
                    $oldValues = $record->exists ? ['status' => $record->status, 'remarks' => $record->remarks] : [];
                    $record->fill(['academic_year_id' => (int) $this->academicYearId, 'term_id' => (int) $this->termId, 'status' => $this->statuses[(int) $studentId] ?? $this->statuses[$studentId] ?? 'present', 'remarks' => $this->remarks[(int) $studentId] ?? $this->remarks[$studentId] ?? null, 'marked_by' => auth()->id()]);
                    $record->save();
                    $newValues = ['status' => $record->status, 'remarks' => $record->remarks];
                    if ($oldValues !== $newValues) app(AuditLogger::class)->record($record->wasRecentlyCreated ? 'attendance.created' : 'attendance.updated', $record, $oldValues, $newValues, $schoolId);
                }
            });
            $attendanceSummary = app(AttendanceSummary::class);
            foreach ($allowedIds as $studentId) $attendanceSummary->invalidate((int) $studentId, $schoolId);
            LivewireAlert::title('Attendance saved')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) { LivewireAlert::title('Check the register')->error()->asToast()->position('top-end')->show(); throw $exception; } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to save attendance')->error()->asToast()->position('top-end')->show(); }
    }

    private function assertClassAccess(SchoolClass $class): void { if (auth()->user()->hasRole('teacher')) abort_unless($class->classSubjects()->where('teacher_id', auth()->user()->teacher?->id)->exists(), 403); }
    private function schoolId(): int { return (int) School::query()->value('id'); }
    private function scopedClasses(): Builder { return SchoolClass::whereHas('academicYear', fn ($q) => $q->where('school_id', $this->schoolId())); }
    private function scopedTerms(): Builder { return Term::whereHas('academicYear', fn ($q) => $q->where('school_id', $this->schoolId())); }

    public function render()
    {
        $years = AcademicYear::where('school_id', $this->schoolId())->orderByDesc('starts_at')->get(); $teacherId = auth()->user()->hasRole('teacher') ? auth()->user()->teacher?->id : null; $classes = $this->scopedClasses()->when($teacherId, fn ($q) => $q->whereHas('classSubjects', fn ($subjects) => $subjects->where('teacher_id', $teacherId)))->orderBy('name')->get();
        return view('livewire.lms.attendance.index', ['years' => $years, 'terms' => $this->scopedTerms()->whereIn('academic_year_id', $years->pluck('id'))->orderBy('sequence')->get(), 'classes' => $classes, 'students' => $this->registerLoaded ? $this->scopedClasses()->find((int) $this->classId)?->enrollments()->with('student')->where('status', 'active')->get() ?? collect() : collect()]);
    }
}
