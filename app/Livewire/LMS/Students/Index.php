<?php

namespace App\Livewire\LMS\Students;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\SpreadsheetExporter;
use App\Services\StudentAdmissionService;
use App\Support\Concerns\ImportsTabularFiles;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;
    use ImportsTabularFiles;
    use WithFileUploads;
    use WithPagination;

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public bool $showImportForm = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $filterStatus = '';

    #[Url(as: 'gender', except: '')]
    public string $filterGender = '';

    #[Url(as: 'class', except: '')]
    public string $filterClassId = '';

    #[Url(as: 'sort', except: 'latest')]
    public string $sortBy = 'latest';

    #[Url(as: 'per_page', except: 15)]
    public int $perPage = 15;

    public string $studentId = '';

    public string $admissionNumber = '';

    public string $firstName = '';

    public string $middleName = '';

    public string $lastName = '';

    public string $email = '';

    public string $password = '';

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

    /** @var TemporaryUploadedFile|UploadedFile|null */
    public $importFile = null;

    /** @var array<int, string> */
    public array $importErrors = [];

    private const STUDENT_STATUSES = ['active', 'graduated', 'transferred', 'withdrawn', 'suspended'];

    private const IMPORT_HEADERS = [
        'student_id', 'admission_number', 'first_name', 'middle_name', 'last_name',
        'email', 'temporary_password', 'date_of_birth', 'gender', 'home_town', 'region',
        'nationality', 'denomination', 'health_insurance_id', 'admission_date', 'status',
        'class_name', 'enrollment_type', 'previous_school_name', 'previous_school_city',
        'previous_school_country', 'previous_school_gps_address', 'previous_school_phone',
        'previous_school_last_class', 'guardian_first_name', 'guardian_last_name',
        'guardian_relationship', 'guardian_email', 'guardian_phone', 'guardian_information_date',
        'guardian_gps_address', 'guardian_city', 'guardian_workplace',
        'guardian_ghana_card_number', 'has_allergies', 'allergy_details',
    ];

    public function mount(): void
    {
        $this->authorize('viewAny', Student::class);
    }

    public function create(): void
    {
        $this->authorize('create', Student::class);
        $this->ensureSchoolConfigured();

        $this->resetForm();
        $this->admissionDate = now()->toDateString();
        $this->guardianInformationDate = now()->toDateString();
        $this->showFormModal = true;
    }

    public function openImport(): void
    {
        $this->authorize('create', Student::class);
        $this->ensureSchoolConfigured();

        $this->resetImportForm();
        $this->showImportForm = true;
    }

    public function closeImport(): void
    {
        $this->showImportForm = false;
        $this->resetImportForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterGender(): void
    {
        $this->resetPage();
    }

    public function updatedFilterClassId(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $allowed = ['latest', 'name_asc', 'name_desc', 'admission_latest', 'admission_oldest'];

        if (! in_array($this->sortBy, $allowed, true)) {
            $this->sortBy = 'latest';
        }

        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $allowed = [10, 15, 25, 50];

        if (! in_array($this->perPage, $allowed, true)) {
            $this->perPage = 15;
        }

        $this->resetPage();
    }

    public function updatedImportFile(): void
    {
        $this->importErrors = [];
        $this->resetValidation('importFile');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterStatus', 'filterGender', 'filterClassId', 'sortBy', 'perPage']);
        $this->sortBy = 'latest';
        $this->perPage = 15;
        $this->resetPage();
    }

    public function edit(Student $student): void
    {
        $this->ensureSchoolRecord($student);
        $this->authorize('update', $student);

        $this->editingId = $student->id;
        $this->studentId = $student->student_id;
        $this->admissionNumber = $student->admission_number;
        $this->firstName = $student->first_name;
        $this->middleName = $student->middle_name ?? '';
        $this->lastName = $student->last_name;
        $this->email = $student->user?->email ?? '';
        $this->password = '';
        $this->dateOfBirth = $student->date_of_birth->toDateString();
        $this->gender = $student->gender;
        $this->homeTown = $student->home_town ?? '';
        $this->region = $student->region ?? '';
        $this->nationality = $student->nationality ?? '';
        $this->denomination = $student->denomination ?? '';
        $this->healthInsuranceId = $student->health_insurance_id ?? '';
        $this->admissionDate = $student->admission_date->toDateString();
        $activeEnrollment = $student->enrollments()
            ->where('status', 'active')
            ->first();
        $this->schoolClassId = (string) ($activeEnrollment?->school_class_id ?? '');
        $this->enrollmentType = $activeEnrollment?->enrollment_type ?? 'day';
        $this->status = $student->status;
        $this->previousSchoolName = $student->previous_school_name ?? '';
        $this->previousSchoolCity = $student->previous_school_city ?? '';
        $this->previousSchoolCountry = $student->previous_school_country ?? '';
        $this->previousSchoolGpsAddress = $student->previous_school_gps_address ?? '';
        $this->previousSchoolPhone = $student->previous_school_phone ?? '';
        $this->previousSchoolLastClass = $student->previous_school_last_class ?? '';
        $this->hasAllergies = (bool) $student->has_allergies;
        $this->allergyDetails = $student->allergy_details ?? '';

        $guardian = $student->parents()
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
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $student = $this->editingId ? Student::findOrFail($this->editingId) : null;

        if ($student) {
            $this->ensureSchoolRecord($student);
        }

        $this->authorize($student ? 'update' : 'create', $student ?? Student::class);
        $schoolId = $this->ensureSchoolConfigured();

        try {
            $matchingAccountId = $student?->user_id
                ?? User::query()->whereRaw('LOWER(email) = ?', [strtolower(trim($this->email))])->value('id');
            $data = $this->validate([
                'studentId' => ['required', 'string', 'max:50', Rule::unique('students', 'student_id')->ignore($student?->id)],
                'admissionNumber' => ['required', 'string', 'max:50', Rule::unique('students', 'admission_number')->ignore($student?->id)],
                'firstName' => ['required', 'string', 'max:100'],
                'middleName' => ['nullable', 'string', 'max:100'],
                'lastName' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($matchingAccountId)],
                'password' => [$matchingAccountId ? 'nullable' : 'required', 'string', 'min:10'],
                'dateOfBirth' => ['required', 'date', 'before:today'],
                'gender' => ['required', 'in:male,female,other'],
                'homeTown' => ['required', 'string', 'max:100'],
                'region' => ['required', 'string', 'max:100'],
                'nationality' => ['required', 'string', 'max:100'],
                'denomination' => ['nullable', 'string', 'max:100'],
                'healthInsuranceId' => ['nullable', 'string', 'max:100'],
                'admissionDate' => ['required', 'date'],
                'schoolClassId' => [Rule::requiredIf(fn () => in_array($this->status, ['active', 'suspended'], true)), 'nullable', 'integer', 'exists:school_classes,id'],
                'enrollmentType' => ['required', Rule::in(['day', 'boarding'])],
                'status' => ['required', Rule::in(self::STUDENT_STATUSES)],
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
            ]);

            if (filled($data['schoolClassId'])) {
                $this->schoolClassesQuery($schoolId)->whereKey($data['schoolClassId'])->firstOrFail();
            }

            app(StudentAdmissionService::class)->admit(
                $student,
                $schoolId,
                [
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
                ],
                [
                    'name' => trim(implode(' ', array_filter([$data['firstName'], $data['middleName'], $data['lastName']]))),
                    'email' => $data['email'],
                    'password' => $data['password'] ?: null,
                ],
                filled($data['schoolClassId']) ? (int) $data['schoolClassId'] : null,
                $data['enrollmentType'],
                [
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
                ],
            );

            $this->showFormModal = false;
            $this->resetForm();
            $this->resetPage();
            LivewireAlert::title($student ? 'Student updated' : 'Student enrolled')
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
            LivewireAlert::title('Unable to save student')
                ->text('Please try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function importStudents(): void
    {
        $this->authorize('create', Student::class);
        $schoolId = $this->ensureSchoolConfigured();
        $this->importErrors = [];

        try {
            $data = $this->validate([
                'importFile' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
            ]);

            /** @var UploadedFile $file */
            $file = $data['importFile'];
            $rows = $this->readImportRows($file);
            [$imports, $errors] = $this->prepareImportRows($rows, $schoolId);

            if ($errors !== []) {
                $this->importErrors = array_slice($errors, 0, 25);
                LivewireAlert::title('Import needs attention')
                    ->text('Correct the listed rows and upload the file again.')
                    ->warning()
                    ->asToast()
                    ->position('top-end')
                    ->show();

                return;
            }

            DB::transaction(function () use ($imports, $schoolId): void {
                foreach ($imports as $import) {
                    app(StudentAdmissionService::class)->admit(
                        null,
                        $schoolId,
                        $import['student'],
                        [
                            'name' => trim(implode(' ', array_filter([
                                $import['student']['first_name'],
                                $import['student']['middle_name'],
                                $import['student']['last_name'],
                            ]))),
                            'email' => $import['account']['email'],
                            'password' => $import['account']['password'],
                        ],
                        $import['school_class_id'],
                        $import['enrollment_type'],
                        $import['guardian'],
                    );
                }
            });

            $imported = count($imports);
            $this->showImportForm = false;
            $this->resetImportForm();
            $this->resetPage();
            LivewireAlert::title("{$imported} ".str('student')->plural($imported).' imported')
                ->success()
                ->asToast()
                ->position('top-end')
                ->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the import file')
                ->text('Use the required columns and a supported CSV or XLSX file.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to import students')
                ->text('No student records were added. Check the file and try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        $this->authorize('create', Student::class);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, self::IMPORT_HEADERS);
            fputcsv($output, [
                'STU-001', 'ADM-001', 'Ama', '', 'Mensah',
                'ama.mensah@example.com', 'ChangeMe123!', '2015-06-12', 'female', 'Kumasi',
                'Ashanti', 'Ghanaian', 'Christian', 'NHIS-001', now()->toDateString(), 'active',
                'Basic 1', 'day', 'Happy Kids School', 'Kumasi', 'Ghana', 'AK-000-0000',
                '0240000000', 'KG 2', 'Adwoa', 'Mensah', 'Mother', 'adwoa.mensah@example.com',
                '0241111111', now()->toDateString(), 'AK-111-1111', 'Kumasi', 'Example Company',
                'GHA-000000000-0', 'no', '',
            ]);
            fclose($output);
        }, 'student-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function confirmDelete(Student $student): void
    {
        $this->ensureSchoolRecord($student);
        $this->authorize('delete', $student);

        $this->deletingId = $student->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $student = Student::findOrFail($this->deletingId);
        $this->ensureSchoolRecord($student);
        $this->authorize('delete', $student);

        try {
            $student->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Student archived')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to archive student')
                ->text('Please try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
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
        $this->reset([
            'editingId', 'deletingId', 'studentId', 'admissionNumber', 'firstName', 'middleName',
            'lastName', 'email', 'password', 'dateOfBirth', 'gender', 'homeTown', 'region',
            'nationality', 'denomination', 'healthInsuranceId', 'admissionDate', 'schoolClassId',
            'enrollmentType', 'status', 'previousSchoolName', 'previousSchoolCity',
            'previousSchoolCountry', 'previousSchoolGpsAddress', 'previousSchoolPhone',
            'previousSchoolLastClass', 'guardianFirstName', 'guardianLastName',
            'guardianGpsAddress', 'guardianCity', 'guardianPhone', 'guardianWorkplace',
            'guardianEmail', 'guardianGhanaCardNumber', 'guardianInformationDate',
            'guardianRelationship', 'hasAllergies', 'allergyDetails',
        ]);
        $this->status = 'active';
        $this->enrollmentType = 'day';
        $this->guardianRelationship = 'Guardian';
        $this->hasAllergies = false;
        $this->resetValidation();
    }

    private function resetImportForm(): void
    {
        $this->reset(['importFile', 'importErrors']);
        $this->resetValidation('importFile');
    }

    private function schoolId(): int
    {
        return (int) School::query()->value('id');
    }

    private function ensureSchoolConfigured(): int
    {
        $schoolId = $this->schoolId();
        abort_unless($schoolId, 422, 'Configure a school before managing students.');

        return $schoolId;
    }

    private function ensureSchoolRecord(Student $student): void
    {
        abort_unless($student->school_id === $this->schoolId(), 404);
    }

    private function schoolClassesQuery(int $schoolId)
    {
        return SchoolClass::query()->whereHas('academicYear', fn ($years) => $years
            ->where('school_id', $schoolId)
            ->where('is_active', true));
    }

    /**
     * @param  array<int, array<int, string|int|float|null>>  $rows
     * @return array{0: array<int, array{student: array<string, mixed>, account: array{email: string, password: string}, guardian: array<string, mixed>, school_class_id: int, enrollment_type: string}>, 1: array<int, string>}
     */
    private function prepareImportRows(array $rows, int $schoolId): array
    {
        throw_if($rows === [], ValidationException::withMessages([
            'importFile' => 'The import file is empty.',
        ]));

        $headers = array_map(fn ($header) => $this->normaliseImportHeader((string) $header), array_shift($rows));
        $requiredHeaders = [
            'student_id', 'admission_number', 'first_name', 'last_name', 'email',
            'temporary_password', 'date_of_birth', 'gender', 'home_town', 'region',
            'nationality', 'admission_date', 'class_name', 'enrollment_type',
            'guardian_first_name', 'guardian_last_name', 'guardian_email', 'guardian_phone',
            'guardian_information_date', 'guardian_gps_address', 'guardian_city', 'has_allergies',
        ];
        $missingHeaders = array_diff($requiredHeaders, $headers);

        throw_if($missingHeaders !== [], ValidationException::withMessages([
            'importFile' => 'Missing required column(s): '.implode(', ', $missingHeaders).'.',
        ]));
        throw_if(count($headers) !== count(array_unique($headers)), ValidationException::withMessages([
            'importFile' => 'Column headings must be unique.',
        ]));
        throw_if($rows === [], ValidationException::withMessages([
            'importFile' => 'Add at least one student row below the column headings.',
        ]));
        throw_if(count($rows) > 500, ValidationException::withMessages([
            'importFile' => 'Import a maximum of 500 student rows at a time.',
        ]));

        $classesByName = $this->schoolClassesQuery($schoolId)
            ->where('status', 'active')
            ->get()
            ->groupBy(fn (SchoolClass $schoolClass) => strtolower(trim($schoolClass->name)));

        $imports = [];
        $errors = [];
        $studentIds = [];
        $admissionNumbers = [];
        $emails = [];

        foreach ($rows as $offset => $row) {
            $line = $offset + 2;
            $values = [];
            foreach ($headers as $index => $header) {
                $values[$header] = trim((string) ($row[$index] ?? ''));
            }

            $studentData = [
                'student_id' => $values['student_id'] ?? '',
                'admission_number' => $values['admission_number'] ?? '',
                'first_name' => $values['first_name'] ?? '',
                'middle_name' => filled($values['middle_name'] ?? '') ? $values['middle_name'] : null,
                'last_name' => $values['last_name'] ?? '',
                'date_of_birth' => $this->normaliseImportDate($values['date_of_birth'] ?? ''),
                'gender' => strtolower($values['gender'] ?? ''),
                'home_town' => $values['home_town'] ?? '',
                'region' => $values['region'] ?? '',
                'nationality' => $values['nationality'] ?? '',
                'denomination' => filled($values['denomination'] ?? '') ? $values['denomination'] : null,
                'health_insurance_id' => filled($values['health_insurance_id'] ?? '') ? $values['health_insurance_id'] : null,
                'admission_date' => $this->normaliseImportDate($values['admission_date'] ?? ''),
                'status' => strtolower($values['status'] ?? 'active') ?: 'active',
                'previous_school_name' => filled($values['previous_school_name'] ?? '') ? $values['previous_school_name'] : null,
                'previous_school_city' => filled($values['previous_school_city'] ?? '') ? $values['previous_school_city'] : null,
                'previous_school_country' => filled($values['previous_school_country'] ?? '') ? $values['previous_school_country'] : null,
                'previous_school_gps_address' => filled($values['previous_school_gps_address'] ?? '') ? $values['previous_school_gps_address'] : null,
                'previous_school_phone' => filled($values['previous_school_phone'] ?? '') ? $values['previous_school_phone'] : null,
                'previous_school_last_class' => filled($values['previous_school_last_class'] ?? '') ? $values['previous_school_last_class'] : null,
                'has_allergies' => $this->normaliseImportBoolean($values['has_allergies'] ?? ''),
                'allergy_details' => filled($values['allergy_details'] ?? '') ? $values['allergy_details'] : null,
            ];
            $accountData = [
                'email' => strtolower($values['email'] ?? ''),
                'password' => $values['temporary_password'] ?? '',
            ];
            $guardianData = [
                'first_name' => $values['guardian_first_name'] ?? '',
                'last_name' => $values['guardian_last_name'] ?? '',
                'relationship' => $values['guardian_relationship'] ?? 'Guardian',
                'email' => strtolower($values['guardian_email'] ?? ''),
                'phone' => $values['guardian_phone'] ?? '',
                'information_date' => $this->normaliseImportDate($values['guardian_information_date'] ?? ''),
                'gps_address' => $values['guardian_gps_address'] ?? '',
                'city' => $values['guardian_city'] ?? '',
                'workplace' => filled($values['guardian_workplace'] ?? '') ? $values['guardian_workplace'] : null,
                'ghana_card_number' => filled($values['guardian_ghana_card_number'] ?? '') ? $values['guardian_ghana_card_number'] : null,
            ];
            $enrollmentType = strtolower($values['enrollment_type'] ?? '');

            $validator = Validator::make($studentData + [
                'student_email' => $accountData['email'],
                'temporary_password' => $accountData['password'],
                'guardian_first_name' => $guardianData['first_name'],
                'guardian_last_name' => $guardianData['last_name'],
                'guardian_relationship' => $guardianData['relationship'],
                'guardian_email' => $guardianData['email'],
                'guardian_phone' => $guardianData['phone'],
                'guardian_information_date' => $guardianData['information_date'],
                'guardian_gps_address' => $guardianData['gps_address'],
                'guardian_city' => $guardianData['city'],
                'enrollment_type' => $enrollmentType,
                'class_name' => $values['class_name'] ?? '',
            ], [
                'student_id' => ['required', 'string', 'max:50'],
                'admission_number' => ['required', 'string', 'max:50'],
                'first_name' => ['required', 'string', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'date_of_birth' => ['required', 'date', 'before:today'],
                'gender' => ['required', 'in:male,female,other'],
                'home_town' => ['required', 'string', 'max:100'],
                'region' => ['required', 'string', 'max:100'],
                'nationality' => ['required', 'string', 'max:100'],
                'denomination' => ['nullable', 'string', 'max:100'],
                'health_insurance_id' => ['nullable', 'string', 'max:100'],
                'admission_date' => ['required', 'date'],
                'status' => ['required', Rule::in(self::STUDENT_STATUSES)],
                'previous_school_name' => ['nullable', 'string', 'max:255'],
                'previous_school_city' => ['nullable', 'string', 'max:100'],
                'previous_school_country' => ['nullable', 'string', 'max:100'],
                'previous_school_gps_address' => ['nullable', 'string', 'max:255'],
                'previous_school_phone' => ['nullable', 'string', 'max:30'],
                'previous_school_last_class' => ['nullable', 'string', 'max:100'],
                'has_allergies' => ['required', 'boolean'],
                'allergy_details' => [Rule::requiredIf($studentData['has_allergies'] === true), 'nullable', 'string', 'max:5000'],
                'student_email' => ['required', 'email', 'max:255'],
                'temporary_password' => ['required', 'string', 'min:10'],
                'enrollment_type' => ['required', Rule::in(['day', 'boarding'])],
                'class_name' => ['required', 'string', 'max:255'],
                'guardian_first_name' => ['required', 'string', 'max:100'],
                'guardian_last_name' => ['required', 'string', 'max:100'],
                'guardian_relationship' => ['required', 'string', 'max:50'],
                'guardian_email' => ['required', 'email', 'max:255', 'different:student_email'],
                'guardian_phone' => ['required', 'string', 'min:8', 'max:30'],
                'guardian_information_date' => ['required', 'date'],
                'guardian_gps_address' => ['required', 'string', 'max:255'],
                'guardian_city' => ['required', 'string', 'max:100'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$line}: ".implode(' ', $validator->errors()->all());

                continue;
            }

            $studentKey = strtolower($studentData['student_id']);
            $admissionKey = strtolower($studentData['admission_number']);
            $emailKey = strtolower($accountData['email']);
            if (isset($studentIds[$studentKey])) {
                $errors[] = "Row {$line}: Student ID '{$studentData['student_id']}' is repeated in this file.";

                continue;
            }
            if (isset($admissionNumbers[$admissionKey])) {
                $errors[] = "Row {$line}: Admission number '{$studentData['admission_number']}' is repeated in this file.";

                continue;
            }
            if (isset($emails[$emailKey])) {
                $errors[] = "Row {$line}: Email '{$accountData['email']}' is repeated in this file.";

                continue;
            }
            if (Student::withTrashed()->where('student_id', $studentData['student_id'])->exists()) {
                $errors[] = "Row {$line}: Student ID '{$studentData['student_id']}' already exists.";

                continue;
            }
            if (Student::withTrashed()->where('admission_number', $studentData['admission_number'])->exists()) {
                $errors[] = "Row {$line}: Admission number '{$studentData['admission_number']}' already exists.";

                continue;
            }
            if (User::query()->whereRaw('LOWER(email) = ?', [$emailKey])->exists()) {
                $errors[] = "Row {$line}: Email '{$accountData['email']}' already belongs to a user account.";

                continue;
            }

            $studentIds[$studentKey] = true;
            $admissionNumbers[$admissionKey] = true;
            $emails[$emailKey] = true;
            $schoolClassId = null;
            $className = trim($values['class_name'] ?? '');

            $matchingClasses = $classesByName->get(strtolower($className), collect());
            if ($matchingClasses->count() !== 1) {
                $errors[] = $matchingClasses->isEmpty()
                    ? "Row {$line}: '{$className}' is not an active class."
                    : "Row {$line}: '{$className}' matches multiple active classes; use a unique class name.";

                continue;
            }

            if ($studentData['status'] !== 'active') {
                $errors[] = "Row {$line}: Imported admissions must have active status when a class is assigned.";

                continue;
            }

            $schoolClassId = $matchingClasses->first()->id;

            $imports[] = [
                'student' => $studentData,
                'account' => $accountData,
                'guardian' => $guardianData,
                'school_class_id' => $schoolClassId,
                'enrollment_type' => $enrollmentType,
            ];
        }

        return [$imports, $errors];
    }

    public function render()
    {
        $schoolId = $this->schoolId();
        $perPage = in_array($this->perPage, [10, 15, 25, 50], true) ? $this->perPage : 15;

        $students = $this->filteredStudentsQuery($schoolId);

        match ($this->sortBy) {
            'name_asc' => $students->orderBy('first_name')->orderBy('last_name'),
            'name_desc' => $students->orderByDesc('first_name')->orderByDesc('last_name'),
            'admission_latest' => $students->orderByDesc('admission_date')->orderByDesc('created_at'),
            'admission_oldest' => $students->orderBy('admission_date')->orderBy('created_at'),
            default => $students->latest(),
        };

        return view('livewire.lms.students.index', [
            'students' => $students->paginate($perPage),
            'classes' => $this->schoolClassesQuery($schoolId)
                ->with('stream')
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function filteredStudentsQuery(int $schoolId)
    {
        $search = trim($this->search);

        return Student::query()
            ->where('school_id', $schoolId)
            ->with([
                'enrollments' => fn ($enrollments) => $enrollments
                    ->where('status', 'active')
                    ->with('schoolClass'),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($students) use ($search): void {
                    $students->where('student_id', 'like', "%{$search}%")
                        ->orWhere('admission_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereHas('enrollments.schoolClass', fn ($classes) => $classes->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(filled($this->filterStatus), fn ($query) => $query->where('status', $this->filterStatus))
            ->when(filled($this->filterGender), fn ($query) => $query->where('gender', $this->filterGender))
            ->when(filled($this->filterClassId), fn ($query) => $query->whereHas('enrollments', fn ($enrollments) => $enrollments
                ->where('status', 'active')
                ->where('school_class_id', $this->filterClassId)));
    }

    public function exportStudents(string $format = 'csv'): StreamedResponse|BinaryFileResponse
    {
        $this->authorize('viewAny', Student::class);
        $schoolId = $this->ensureSchoolConfigured();
        $format = $format === 'xlsx' ? 'xlsx' : 'csv';

        $students = $this->filteredStudentsQuery($schoolId)
            ->with(['parents' => fn ($parents) => $parents->orderByPivot('is_primary_contact', 'desc')])
            ->latest()
            ->get();

        $rows = $students->map(function (Student $student): array {
            $enrollment = $student->enrollments->first();
            $guardian = $student->parents->first();

            return [
                $student->student_id,
                $student->admission_number,
                $student->first_name,
                $student->middle_name,
                $student->last_name,
                $student->user?->email ?? '',
                '',
                $student->date_of_birth->toDateString(),
                $student->gender,
                $student->home_town,
                $student->region,
                $student->nationality,
                $student->denomination,
                $student->health_insurance_id,
                $student->admission_date->toDateString(),
                $student->status,
                $enrollment?->schoolClass?->name ?? '',
                $enrollment?->enrollment_type ?? '',
                $student->previous_school_name,
                $student->previous_school_city,
                $student->previous_school_country,
                $student->previous_school_gps_address,
                $student->previous_school_phone,
                $student->previous_school_last_class,
                $guardian?->first_name ?? '',
                $guardian?->last_name ?? '',
                $guardian?->pivot->relationship ?? '',
                $guardian?->user?->email ?? $guardian?->email ?? '',
                $guardian?->phone ?? '',
                filled($guardian?->pivot->information_date ?? null) ? Carbon::parse($guardian->pivot->information_date)->toDateString() : '',
                $guardian?->gps_address ?? '',
                $guardian?->city ?? '',
                $guardian?->workplace ?? '',
                $guardian?->ghana_card_number ?? '',
                $student->has_allergies ? 'yes' : 'no',
                $student->allergy_details,
            ];
        });

        $filename = 'students-export-'.now()->format('Y-m-d').'.'.$format;

        return $format === 'xlsx'
            ? SpreadsheetExporter::xlsx($filename, self::IMPORT_HEADERS, $rows)
            : SpreadsheetExporter::csv($filename, self::IMPORT_HEADERS, $rows);
    }
}
