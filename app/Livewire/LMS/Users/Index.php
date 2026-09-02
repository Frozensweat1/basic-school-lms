<?php

namespace App\Livewire\LMS\Users;

use App\Models\ClassEnrollment;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\StudentAdmissionService;
use App\Services\UserProfileService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
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

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = '';

    public string $search = '';

    public string $firstName = '';

    public string $middleName = '';

    public string $lastName = '';

    public string $phone = '';

    public string $address = '';

    public string $profileStatus = 'active';

    public string $employeeId = '';

    public string $employmentDate = '';

    public string $studentId = '';

    public string $admissionNumber = '';

    public string $dateOfBirth = '';

    public string $gender = '';

    public string $homeTown = '';

    public string $region = '';

    public string $nationality = '';

    public string $denomination = '';

    public string $healthInsuranceId = '';

    public string $admissionDate = '';

    public string $schoolClassId = '';

    public string $enrollmentType = 'day';

    public string $status = 'active';

    public string $previousSchoolName = '';

    public string $previousSchoolCity = '';

    public string $previousSchoolCountry = '';

    public string $previousSchoolGpsAddress = '';

    public string $previousSchoolPhone = '';

    public string $previousSchoolLastClass = '';

    public string $guardianFirstName = '';

    public string $guardianLastName = '';

    public string $guardianGpsAddress = '';

    public string $guardianCity = '';

    public string $guardianPhone = '';

    public string $guardianWorkplace = '';

    public string $guardianEmail = '';

    public string $guardianGhanaCardNumber = '';

    public string $guardianInformationDate = '';

    public string $guardianRelationship = 'Guardian';

    public bool $hasAllergies = false;

    public string $allergyDetails = '';

    public string $relationship = 'Guardian';

    /** @var array<int, string> */
    public array $studentIds = [];

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        if ($this->role === 'student' && blank($this->admissionDate)) {
            $this->admissionDate = now()->toDateString();
        }

        if ($this->role === 'student' && blank($this->guardianInformationDate)) {
            $this->guardianInformationDate = now()->toDateString();
        }

        if ($this->role === 'teacher' && ! in_array($this->profileStatus, ['active', 'inactive', 'retired'], true)) {
            $this->profileStatus = 'active';
        }

        if ($this->role === 'student' && ! in_array($this->status, ['active', 'graduated', 'transferred', 'withdrawn', 'suspended'], true)) {
            $this->status = 'active';
        }

        $this->resetValidation();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', User::class);
        $this->resetForm();
        $this->admissionDate = now()->toDateString();
        $this->guardianInformationDate = now()->toDateString();
        $this->showFormModal = true;
    }

    public function edit(User $user): void
    {
        $this->authorize('update', $user);
        $this->resetForm();
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->first()?->name ?? '';
        $this->loadProfileFields($user);
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $user = $this->editingId ? User::findOrFail($this->editingId) : null;
        $this->authorize($user ? 'update' : 'create', $user ?? User::class);
        $profileRole = in_array($this->role, UserProfileService::profileRoles(), true);
        $schoolId = (int) School::query()->value('id');

        if ($profileRole) {
            abort_unless($schoolId, 422, 'Configure a school before creating teacher, student, or parent accounts.');
        }

        try {
            $data = $this->validate($this->rules($user, $profileRole, $schoolId));

            if (auth()->user()->hasRole('school_admin') && $data['role'] === 'super_admin') {
                $this->addError('role', 'School administrators cannot assign the super administrator role.');

                return;
            }

            if ($user?->id === auth()->id() && $data['role'] !== $user->roles->first()?->name) {
                $this->addError('role', 'You cannot change your own role.');

                return;
            }

            DB::transaction(function () use ($user, $data, $profileRole, $schoolId): void {
                if ($user) {
                    app(UserProfileService::class)->assertRoleChangeAllowed($user, $data['role']);
                }

                $record = $user ?? new User;
                $record->name = $profileRole ? $this->profileFullName($data) : $data['name'];
                $record->email = strtolower(trim($data['email']));

                if (filled($data['password'])) {
                    $record->password = $data['password'];
                }

                $record->save();

                Role::findOrCreate($data['role'], config('auth.defaults.guard', 'web'));
                $record->syncRoles([$data['role']]);

                if ($data['role'] === 'student') {
                    $profile = app(StudentAdmissionService::class)->admit(
                        $this->matchingStudentProfile($record),
                        $schoolId,
                        $this->studentAttributes($data),
                        [
                            'name' => $this->profileFullName($data),
                            'email' => $data['email'],
                            'password' => filled($data['password']) ? $data['password'] : null,
                        ],
                        filled($data['schoolClassId']) ? (int) $data['schoolClassId'] : null,
                        $data['enrollmentType'],
                        $this->guardianAttributes($data),
                    );
                } else {
                    $profile = app(UserProfileService::class)->synchronizeProfile(
                        $record,
                        $schoolId,
                        $data['role'],
                        $this->profileAttributes($data),
                    );
                }

                if ($profile instanceof ParentGuardian) {
                    $profile->students()->sync(
                        collect($data['studentIds'])
                            ->mapWithKeys(fn ($id) => [$id => [
                                'relationship' => $data['relationship'],
                                'is_primary_contact' => false,
                            ]])
                            ->all(),
                    );
                }

            });

            $this->showFormModal = false;
            $this->resetForm();
            $this->resetPage();
            $alert = LivewireAlert::title($user ? 'User updated' : 'User created');
            if ($profileRole) {
                $alert->text('The login account and matching profile are ready.');
            }
            $alert->success()
                ->asToast()
                ->position('top-end')
                ->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the form')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save user')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(User $user): void
    {
        $this->authorize('delete', $user);
        $this->deletingId = $user->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $user = User::findOrFail($this->deletingId);
        $this->authorize('delete', $user);

        try {
            DB::transaction(function () use ($user): void {
                if ($user->teacher && ($user->teacher->classSubjects()->exists() || $user->teacher->classes()->exists())) {
                    throw ValidationException::withMessages([
                        'delete' => 'Teachers with class assignments must be marked inactive instead.',
                    ]);
                }

                $user->student?->delete();
                $user->teacher?->delete();
                $user->parentGuardian?->delete();
                $user->delete();
            });

            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('User and linked profile archived')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            $this->addError('delete', $exception->validator->errors()->first('delete'));
            LivewireAlert::title('User cannot be deleted')->warning()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete user')->error()->asToast()->position('top-end')->show();
        }
    }

    public function closeModals(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    private function rules(?User $user, bool $profileRole, int $schoolId): array
    {
        $rules = [
            'name' => [$profileRole ? 'nullable' : 'required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:10'],
            'role' => ['required', Rule::exists('roles', 'name')],
        ];

        if (! $profileRole) {
            return $rules;
        }

        $rules += [
            'firstName' => ['required', 'string', 'max:100'],
            'middleName' => ['nullable', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];

        if ($this->role === 'teacher') {
            $candidate = $this->matchingTeacherProfile($user);
            $rules += [
                'employeeId' => ['required', 'string', 'max:50', Rule::unique('teachers', 'employee_id')->ignore($candidate?->id)],
                'employmentDate' => ['nullable', 'date'],
                'profileStatus' => ['required', 'in:active,inactive,retired'],
            ];
        } elseif ($this->role === 'student') {
            $candidate = $this->matchingStudentProfile($user);
            $rules += [
                'studentId' => ['required', 'string', 'max:50', Rule::unique('students', 'student_id')->ignore($candidate?->id)],
                'admissionNumber' => ['required', 'string', 'max:50', Rule::unique('students', 'admission_number')->ignore($candidate?->id)],
                'dateOfBirth' => ['required', 'date', 'before:today'],
                'gender' => ['required', 'in:male,female,other'],
                'homeTown' => ['required', 'string', 'max:100'],
                'region' => ['required', 'string', 'max:100'],
                'nationality' => ['required', 'string', 'max:100'],
                'denomination' => ['nullable', 'string', 'max:100'],
                'healthInsuranceId' => ['nullable', 'string', 'max:100'],
                'admissionDate' => ['required', 'date'],
                'schoolClassId' => [
                    Rule::requiredIf(fn () => in_array($this->status, ['active', 'suspended'], true)),
                    'nullable',
                    'integer',
                    Rule::exists('school_classes', 'id')->where(fn ($query) => $query
                        ->whereIn('academic_year_id', SchoolClass::query()
                            ->select('academic_year_id')
                            ->whereHas('academicYear', fn ($years) => $years->where('school_id', $schoolId)->where('is_active', true)))),
                ],
                'enrollmentType' => ['required', Rule::in(ClassEnrollment::ENROLLMENT_TYPES)],
                'status' => ['required', Rule::in(['active', 'graduated', 'transferred', 'withdrawn', 'suspended'])],
                'previousSchoolName' => ['nullable', 'string', 'max:255'],
                'previousSchoolCity' => ['nullable', 'string', 'max:100'],
                'previousSchoolCountry' => ['nullable', 'string', 'max:100'],
                'previousSchoolGpsAddress' => ['nullable', 'string', 'max:255'],
                'previousSchoolPhone' => ['nullable', 'string', 'max:30'],
                'previousSchoolLastClass' => ['nullable', 'string', 'max:100'],
                'guardianFirstName' => ['required', 'string', 'max:100'],
                'guardianLastName' => ['required', 'string', 'max:100'],
                'guardianGpsAddress' => ['required', 'string', 'max:255'],
                'guardianCity' => ['required', 'string', 'max:100'],
                'guardianPhone' => ['required', 'string', 'min:8', 'max:30'],
                'guardianWorkplace' => ['nullable', 'string', 'max:255'],
                'guardianEmail' => ['required', 'email', 'max:255', 'different:email'],
                'guardianGhanaCardNumber' => ['nullable', 'string', 'max:50'],
                'guardianInformationDate' => ['required', 'date'],
                'guardianRelationship' => ['required', 'string', 'max:50'],
                'hasAllergies' => ['boolean'],
                'allergyDetails' => [Rule::requiredIf($this->hasAllergies), 'nullable', 'string', 'max:5000'],
            ];
        } else {
            $rules += [
                'address' => ['nullable', 'string', 'max:1000'],
                'relationship' => ['required', 'string', 'max:50'],
                'studentIds' => ['array'],
                'studentIds.*' => [Rule::exists('students', 'id')->where('school_id', $schoolId)],
            ];
        }

        return $rules;
    }

    private function profileAttributes(array $data): array
    {
        return match ($data['role']) {
            'teacher' => [
                'employee_id' => $data['employeeId'],
                'first_name' => $data['firstName'],
                'middle_name' => $data['middleName'] ?: null,
                'last_name' => $data['lastName'],
                'phone' => $data['phone'] ?: null,
                'email' => strtolower(trim($data['email'])),
                'employment_date' => $data['employmentDate'] ?: null,
                'status' => $data['profileStatus'],
            ],
            'student' => $this->studentAttributes($data),
            'parent' => [
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'],
                'phone' => $data['phone'] ?: null,
                'email' => strtolower(trim($data['email'])),
                'address' => $data['address'] ?: null,
            ],
            default => [],
        };
    }

    /** @param array<string, mixed> $data */
    private function studentAttributes(array $data): array
    {
        return [
            'student_id' => $data['studentId'],
            'admission_number' => $data['admissionNumber'],
            'first_name' => $data['firstName'],
            'middle_name' => filled($data['middleName']) ? $data['middleName'] : null,
            'last_name' => $data['lastName'],
            'date_of_birth' => $data['dateOfBirth'],
            'gender' => $data['gender'],
            'home_town' => $data['homeTown'],
            'region' => $data['region'],
            'nationality' => $data['nationality'],
            'denomination' => $data['denomination'] ?: null,
            'health_insurance_id' => $data['healthInsuranceId'] ?: null,
            'admission_date' => $data['admissionDate'],
            'status' => $data['status'],
            'previous_school_name' => $data['previousSchoolName'] ?: null,
            'previous_school_city' => $data['previousSchoolCity'] ?: null,
            'previous_school_country' => $data['previousSchoolCountry'] ?: null,
            'previous_school_gps_address' => $data['previousSchoolGpsAddress'] ?: null,
            'previous_school_phone' => $data['previousSchoolPhone'] ?: null,
            'previous_school_last_class' => $data['previousSchoolLastClass'] ?: null,
            'has_allergies' => $data['hasAllergies'],
            'allergy_details' => $data['allergyDetails'] ?: null,
        ];
    }

    /** @param array<string, mixed> $data */
    private function guardianAttributes(array $data): array
    {
        return [
            'first_name' => $data['guardianFirstName'],
            'last_name' => $data['guardianLastName'],
            'gps_address' => $data['guardianGpsAddress'],
            'city' => $data['guardianCity'],
            'phone' => $data['guardianPhone'],
            'workplace' => $data['guardianWorkplace'] ?: null,
            'email' => $data['guardianEmail'],
            'ghana_card_number' => $data['guardianGhanaCardNumber'] ?: null,
            'relationship' => $data['guardianRelationship'],
            'information_date' => $data['guardianInformationDate'],
        ];
    }

    private function profileFullName(array $data): string
    {
        return trim(implode(' ', array_filter([
            $data['firstName'] ?? null,
            $data['middleName'] ?? null,
            $data['lastName'] ?? null,
        ])));
    }

    private function loadProfileFields(User $user): void
    {
        $profile = match ($this->role) {
            'teacher' => $user->teacher()->withTrashed()->first(),
            'student' => $user->student()->withTrashed()->first(),
            'parent' => $user->parentGuardian()->withTrashed()->first(),
            default => null,
        };

        if (! $profile) {
            return;
        }

        $this->firstName = $profile->first_name;
        $this->middleName = $profile->middle_name ?? '';
        $this->lastName = $profile->last_name;
        $this->phone = $profile->phone ?? '';

        if ($profile instanceof Teacher) {
            $this->employeeId = $profile->employee_id;
            $this->employmentDate = $profile->employment_date?->toDateString() ?? '';
            $this->profileStatus = $profile->status;
        } elseif ($profile instanceof Student) {
            $this->studentId = $profile->student_id;
            $this->admissionNumber = $profile->admission_number;
            $this->dateOfBirth = $profile->date_of_birth->toDateString();
            $this->gender = $profile->gender;
            $this->homeTown = $profile->home_town ?? '';
            $this->region = $profile->region ?? '';
            $this->nationality = $profile->nationality ?? '';
            $this->denomination = $profile->denomination ?? '';
            $this->healthInsuranceId = $profile->health_insurance_id ?? '';
            $this->admissionDate = $profile->admission_date->toDateString();
            $this->status = $profile->status;
            $activeEnrollment = $profile->enrollments()
                ->where('status', ClassEnrollment::STATUS_ACTIVE)
                ->first();
            $this->schoolClassId = (string) ($activeEnrollment?->school_class_id ?? '');
            $this->enrollmentType = $activeEnrollment?->enrollment_type ?? ClassEnrollment::ENROLLMENT_TYPE_DAY;
            $this->previousSchoolName = $profile->previous_school_name ?? '';
            $this->previousSchoolCity = $profile->previous_school_city ?? '';
            $this->previousSchoolCountry = $profile->previous_school_country ?? '';
            $this->previousSchoolGpsAddress = $profile->previous_school_gps_address ?? '';
            $this->previousSchoolPhone = $profile->previous_school_phone ?? '';
            $this->previousSchoolLastClass = $profile->previous_school_last_class ?? '';
            $this->hasAllergies = (bool) $profile->has_allergies;
            $this->allergyDetails = $profile->allergy_details ?? '';

            $guardian = $profile->parents()
                ->orderByPivot('is_primary_contact', 'desc')
                ->orderBy('parents.id')
                ->first();

            if ($guardian) {
                $this->guardianFirstName = $guardian->first_name;
                $this->guardianLastName = $guardian->last_name;
                $this->guardianGpsAddress = $guardian->gps_address ?? '';
                $this->guardianCity = $guardian->city ?? '';
                $this->guardianPhone = $guardian->phone ?? '';
                $this->guardianWorkplace = $guardian->workplace ?? '';
                $this->guardianEmail = $guardian->user?->email ?? $guardian->email ?? '';
                $this->guardianGhanaCardNumber = $guardian->ghana_card_number ?? '';
                $this->guardianInformationDate = filled($guardian->pivot->information_date)
                    ? Carbon::parse($guardian->pivot->information_date)->toDateString()
                    : '';
                $this->guardianRelationship = $guardian->pivot->relationship ?? 'Guardian';
            }
        } elseif ($profile instanceof ParentGuardian) {
            $this->address = $profile->address ?? '';
            $this->relationship = $profile->students()->first()?->pivot->relationship ?? 'Guardian';
            $this->studentIds = $profile->students()->pluck('students.id')->map(fn ($id) => (string) $id)->all();
        }
    }

    private function matchingTeacherProfile(?User $user): ?Teacher
    {
        return $user?->teacher()->withTrashed()->first()
            ?? Teacher::withTrashed()
                ->where('employee_id', $this->employeeId)
                ->where(fn ($query) => $query->whereNull('user_id')->when($user, fn ($profiles) => $profiles->orWhere('user_id', $user->id)))
                ->first();
    }

    private function matchingStudentProfile(?User $user): ?Student
    {
        return $user?->student()->withTrashed()->first()
            ?? Student::withTrashed()
                ->where(function ($query): void {
                    $query->where('student_id', $this->studentId)
                        ->orWhere('admission_number', $this->admissionNumber);
                })
                ->where(fn ($query) => $query->whereNull('user_id')->when($user, fn ($profiles) => $profiles->orWhere('user_id', $user->id)))
                ->first();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'deletingId', 'name', 'email', 'password', 'role',
            'firstName', 'middleName', 'lastName', 'phone', 'address', 'employeeId',
            'employmentDate', 'studentId', 'admissionNumber', 'dateOfBirth', 'gender',
            'homeTown', 'region', 'nationality', 'denomination', 'healthInsuranceId',
            'admissionDate', 'schoolClassId', 'enrollmentType', 'status',
            'previousSchoolName', 'previousSchoolCity', 'previousSchoolCountry',
            'previousSchoolGpsAddress', 'previousSchoolPhone', 'previousSchoolLastClass',
            'guardianFirstName', 'guardianLastName', 'guardianGpsAddress', 'guardianCity',
            'guardianPhone', 'guardianWorkplace', 'guardianEmail', 'guardianGhanaCardNumber',
            'guardianInformationDate', 'guardianRelationship', 'hasAllergies', 'allergyDetails',
            'relationship', 'studentIds', 'profileStatus',
        ]);
        $this->relationship = 'Guardian';
        $this->guardianRelationship = 'Guardian';
        $this->enrollmentType = ClassEnrollment::ENROLLMENT_TYPE_DAY;
        $this->status = 'active';
        $this->hasAllergies = false;
        $this->profileStatus = 'active';
        $this->resetValidation();
    }

    public function render()
    {
        $search = trim($this->search);
        $schoolId = (int) School::query()->value('id');

        return view('livewire.lms.users.index', [
            'users' => User::query()
                ->with(['roles', 'teacher', 'student', 'parentGuardian'])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($users) use ($search): void {
                        $users->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when(auth()->user()->hasRole('school_admin'), fn ($query) => $query->whereDoesntHave('roles', fn ($roles) => $roles->where('name', 'super_admin')))
                ->latest()
                ->paginate(25),
            'roles' => Role::query()
                ->when(auth()->user()->hasRole('school_admin'), fn ($query) => $query->where('name', '!=', 'super_admin'))
                ->orderBy('name')
                ->get(),
            'students' => Student::query()
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'classes' => SchoolClass::query()
                ->whereHas('academicYear', fn ($years) => $years->where('school_id', $schoolId)->where('is_active', true))
                ->with('stream')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
