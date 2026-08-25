<?php

namespace App\Livewire\LMS\Attendance;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ClassEnrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Support\AttendanceSummary;
use App\Support\AuditLogger;
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

    public string $academicYearId = '';

    public string $termId = '';

    public string $classId = '';

    public string $attendanceDate = '';

    public string $studentSearch = '';

    public array $statuses = [];

    public array $remarks = [];

    public bool $registerLoaded = false;

    public function mount(): void
    {
        $this->authorize('viewAny', AttendanceRecord::class);
        $this->initialiseRegisterDefaults();
    }

    public function updatedAcademicYearId(): void
    {
        $this->termId = '';
        $this->classId = '';
        $this->resetRegister();
    }

    public function updatedTermId(): void
    {
        $this->resetRegister();
    }

    public function updatedClassId(): void
    {
        $this->resetRegister();
    }

    public function updatedAttendanceDate(): void
    {
        $this->resetRegister();
    }

    public function updatedStudentSearch(): void
    {
        $this->resetPage('registerPage');
    }

    public function clearStudentSearch(): void
    {
        $this->studentSearch = '';
        $this->resetPage('registerPage');
    }

    public function loadRegister(): void
    {
        $this->authorize('create', AttendanceRecord::class);

        try {
            $data = $this->validate($this->contextRules());
            [$class] = $this->attendanceContext($data);
            $existing = AttendanceRecord::query()
                ->where('school_class_id', $class->id)
                ->whereDate('attendance_date', $data['attendanceDate'])
                ->get()
                ->keyBy('student_id');

            $this->statuses = [];
            $this->remarks = [];

            foreach ($this->activeEnrollments($class)->get() as $enrollment) {
                $studentId = (string) $enrollment->student_id;
                $record = $existing->get((int) $studentId);
                $this->statuses[$studentId] = $record?->status ?? 'present';
                $this->remarks[$studentId] = $record?->remarks ?? '';
            }

            $this->registerLoaded = true;
            $this->resetPage('registerPage');
            $this->resetValidation();

            if ($this->statuses === []) {
                LivewireAlert::title('No active students in this class')
                    ->warning()->asToast()->position('top-end')->show();
            }
        } catch (ValidationException $exception) {
            LivewireAlert::title('Choose the attendance context')
                ->text('Select a matching academic year, term, class, and date.')
                ->error()->asToast()->position('top-end')->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to load the attendance register')
                ->text('Please review the selected class and try again.')
                ->error()->asToast()->position('top-end')->show();
        }
    }

    public function markAll(string $status): void
    {
        $this->authorize('create', AttendanceRecord::class);
        abort_unless(in_array($status, AttendanceRecord::STATUSES, true), 422);

        if (! $this->registerLoaded) {
            return;
        }

        foreach (array_keys($this->statuses) as $studentId) {
            $this->statuses[$studentId] = $status;
        }
    }

    public function save(): void
    {
        $this->authorize('create', AttendanceRecord::class);

        try {
            if (! $this->registerLoaded) {
                throw ValidationException::withMessages([
                    'classId' => 'Load a class register before saving attendance.',
                ]);
            }

            $data = $this->validate([
                ...$this->contextRules(),
                'statuses' => ['required', 'array'],
                'statuses.*' => [Rule::in(AttendanceRecord::STATUSES)],
                'remarks' => ['array'],
                'remarks.*' => ['nullable', 'string', 'max:1000'],
            ]);

            [$class] = $this->attendanceContext($data);
            $studentIds = $this->activeEnrollments($class)->pluck('student_id')->map(fn ($id) => (string) $id)->all();

            if (array_diff($studentIds, array_keys($this->statuses)) !== []) {
                throw ValidationException::withMessages([
                    'statuses' => 'Reload the register to include every active student before saving.',
                ]);
            }

            $schoolId = $this->schoolId();

            DB::transaction(function () use ($class, $studentIds, $schoolId): void {
                foreach ($studentIds as $studentId) {
                    $record = AttendanceRecord::firstOrNew([
                        'student_id' => (int) $studentId,
                        'school_class_id' => $class->id,
                        'attendance_date' => $this->attendanceDate,
                    ]);

                    $oldValues = $record->exists
                        ? ['status' => $record->status, 'remarks' => $record->remarks]
                        : [];

                    $record->fill([
                        'academic_year_id' => (int) $this->academicYearId,
                        'term_id' => (int) $this->termId,
                        'status' => $this->statuses[$studentId] ?? 'present',
                        'remarks' => filled($this->remarks[$studentId] ?? null) ? $this->remarks[$studentId] : null,
                        'marked_by' => auth()->id(),
                    ]);
                    $record->save();

                    $newValues = ['status' => $record->status, 'remarks' => $record->remarks];
                    if ($oldValues !== $newValues) {
                        app(AuditLogger::class)->record(
                            $record->wasRecentlyCreated ? 'attendance.created' : 'attendance.updated',
                            $record,
                            $oldValues,
                            $newValues,
                            $schoolId,
                        );
                    }
                }
            });

            $attendanceSummary = app(AttendanceSummary::class);
            foreach ($studentIds as $studentId) {
                $attendanceSummary->invalidate((int) $studentId, $schoolId);
            }

            LivewireAlert::title('Attendance saved')
                ->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the attendance register')
                ->text('Correct the highlighted fields and try again.')
                ->error()->asToast()->position('top-end')->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save attendance')
                ->text('Please try again.')
                ->error()->asToast()->position('top-end')->show();
        }
    }

    public function render()
    {
        $schoolId = $this->schoolId();
        $years = $this->scopedYears()->orderByDesc('starts_at')->get();
        $terms = $this->scopedTerms()
            ->whereIn('academic_year_id', $years->pluck('id'))
            ->orderBy('academic_year_id')
            ->orderBy('sequence')
            ->get();
        $classes = $this->scopedClasses()
            ->when($this->managingAsTeacher(), fn (Builder $query) => $query->whereHas('classSubjects', fn (Builder $subjects) => $subjects->where('teacher_id', auth()->user()->teacher->id)))
            ->withCount([
                'enrollments as active_students_count' => fn (Builder $enrollments) => $enrollments->where('status', 'active'),
            ])
            ->orderBy('name')
            ->get();

        $students = collect();
        if ($this->registerLoaded && filled($this->classId)) {
            $class = $this->scopedClasses()->find((int) $this->classId);

            if ($class) {
                $search = trim($this->studentSearch);
                $students = $this->activeEnrollments($class)
                    ->when($search !== '', function (Builder $query) use ($search): void {
                        $query->whereHas('student', function (Builder $student) use ($search): void {
                            $student->where(function (Builder $details) use ($search): void {
                                $details->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('middle_name', 'like', "%{$search}%")
                                    ->orWhere('admission_number', 'like', "%{$search}%")
                                    ->orWhere('student_id', 'like', "%{$search}%");
                            });
                        });
                    })
                    ->orderBy('student_id')
                    ->paginate(25, ['*'], 'registerPage');
            } else {
                $this->resetRegister();
            }
        }

        $summary = collect($this->statuses)->countBy();

        return view('livewire.lms.attendance.index', [
            'years' => $years,
            'terms' => $terms,
            'classes' => $classes,
            'students' => $students,
            'registerSummary' => [
                'total' => $summary->sum(),
                'present' => (int) ($summary->get('present') ?? 0),
                'absent' => (int) ($summary->get('absent') ?? 0),
                'late' => (int) ($summary->get('late') ?? 0),
                'excused' => (int) ($summary->get('excused') ?? 0),
            ],
            'schoolId' => $schoolId,
        ]);
    }

    protected function initialiseRegisterDefaults(): void
    {
        $this->attendanceDate = now()->toDateString();

        $year = $this->scopedYears()->orderByDesc('is_active')->orderByDesc('starts_at')->first();
        if (! $year) {
            return;
        }

        $this->academicYearId = (string) $year->id;
        $term = $this->scopedTerms()
            ->where('academic_year_id', $year->id)
            ->orderByDesc('is_active')
            ->orderBy('sequence')
            ->first();

        $this->termId = (string) ($term?->id ?? '');
    }

    private function resetRegister(): void
    {
        $this->registerLoaded = false;
        $this->statuses = [];
        $this->remarks = [];
        $this->studentSearch = '';
        $this->resetPage('registerPage');
    }

    private function contextRules(): array
    {
        return [
            'academicYearId' => ['required', 'integer'],
            'termId' => ['required', 'integer'],
            'classId' => ['required', 'integer'],
            'attendanceDate' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    private function attendanceContext(array $data): array
    {
        $class = $this->scopedClasses()->findOrFail((int) $data['classId']);
        $term = $this->scopedTerms()->findOrFail((int) $data['termId']);

        if ($class->academic_year_id !== (int) $data['academicYearId'] || $term->academic_year_id !== $class->academic_year_id) {
            throw ValidationException::withMessages([
                'termId' => 'Choose a class and term that belong to the selected academic year.',
            ]);
        }

        $this->assertClassAccess($class);

        return [$class, $term];
    }

    private function activeEnrollments(SchoolClass $class): Builder
    {
        return ClassEnrollment::query()
            ->with('student')
            ->where('school_class_id', $class->id)
            ->where('status', 'active');
    }

    private function assertClassAccess(SchoolClass $class): void
    {
        if (! $this->managingAsTeacher()) {
            return;
        }

        abort_unless(
            $class->classSubjects()->where('teacher_id', auth()->user()->teacher?->id)->exists(),
            403,
        );
    }

    private function managingAsTeacher(): bool
    {
        return auth()->user()->hasRole('teacher')
            && ! auth()->user()->hasAnyRole(['super_admin', 'school_admin'])
            && (bool) auth()->user()->teacher;
    }

    private function schoolId(): int
    {
        $schoolId = School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school before recording attendance.');

        return (int) $schoolId;
    }

    private function scopedYears(): Builder
    {
        return AcademicYear::query()->where('school_id', $this->schoolId());
    }

    private function scopedClasses(): Builder
    {
        return SchoolClass::query()
            ->whereHas('academicYear', fn (Builder $years) => $years->where('school_id', $this->schoolId()));
    }

    private function scopedTerms(): Builder
    {
        return Term::query()
            ->whereHas('academicYear', fn (Builder $years) => $years->where('school_id', $this->schoolId()));
    }
}
