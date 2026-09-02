<?php

namespace App\Livewire\LMS\Teachers;

use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use App\Services\SpreadsheetExporter;
use App\Services\UserProfileService;
use App\Support\Concerns\ImportsTabularFiles;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
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

    public string $search = '';

    public string $filterStatus = '';

    public string $filterAssignment = '';

    public string $employeeId = '';

    public string $firstName = '';

    public string $middleName = '';

    public string $lastName = '';

    public string $phone = '';

    public string $email = '';

    public string $password = '';

    public string $employmentDate = '';

    public string $status = 'active';

    public string $gender = '';

    public string $dateOfBirth = '';

    public string $nationality = '';

    public string $postalAddress = '';

    public string $residentialAddress = '';

    public string $gpsAddress = '';

    public string $maritalStatus = '';

    public string $religion = '';

    public string $emergencyContactName = '';

    public string $emergencyContactPhone = '';

    public string $ssnitNumber = '';

    public string $ghanaCardNumber = '';

    /** @var array<int, array<string, mixed>> */
    public array $dependants = [];

    /** @var array<int, array<string, mixed>> */
    public array $qualifications = [];

    /** @var array<int, array<string, mixed>> */
    public array $workExperiences = [];

    /** @var array<int, array<string, mixed>> */
    public array $referees = [];

    /** @var TemporaryUploadedFile|UploadedFile|null */
    public $importFile = null;

    /** @var array<int, string> */
    public array $importErrors = [];

    private const IMPORT_HEADERS = [
        'employee_id', 'first_name', 'middle_name', 'last_name', 'email', 'temporary_password',
        'phone', 'employment_date', 'status', 'gender', 'date_of_birth', 'nationality',
        'postal_address', 'residential_address', 'gps_address', 'marital_status', 'religion',
        'emergency_contact_name', 'emergency_contact_phone', 'ssnit_number', 'ghana_card_number',
    ];

    public function mount(): void
    {
        $this->authorize('viewAny', Teacher::class);
    }

    public function create(): void
    {
        $this->authorize('create', Teacher::class);
        $this->ensureSchoolConfigured();
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openImport(): void
    {
        $this->authorize('create', Teacher::class);
        $this->ensureSchoolConfigured();
        $this->resetImportForm();
        $this->showImportForm = true;
    }

    public function closeImport(): void
    {
        $this->showImportForm = false;
        $this->resetImportForm();
    }

    public function updatedImportFile(): void
    {
        $this->importErrors = [];
        $this->resetValidation('importFile');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterAssignment(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterStatus', 'filterAssignment']);
        $this->resetPage();
    }

    public function addDependant(): void
    {
        $this->dependants[] = ['relation' => '', 'name' => '', 'dateOfBirth' => '', 'isNextOfKin' => false];
    }

    public function removeDependant(int $index): void
    {
        unset($this->dependants[$index]);
        $this->dependants = array_values($this->dependants);
    }

    public function addQualification(): void
    {
        $this->qualifications[] = ['qualification' => '', 'institution' => '', 'programOfStudy' => '', 'yearOfGraduation' => ''];
    }

    public function removeQualification(int $index): void
    {
        unset($this->qualifications[$index]);
        $this->qualifications = array_values($this->qualifications);
    }

    public function addWorkExperience(): void
    {
        $this->workExperiences[] = ['institution' => '', 'country' => '', 'position' => '', 'address' => '', 'startDate' => '', 'endDate' => ''];
    }

    public function removeWorkExperience(int $index): void
    {
        unset($this->workExperiences[$index]);
        $this->workExperiences = array_values($this->workExperiences);
    }

    public function addReferee(): void
    {
        $this->referees[] = ['name' => '', 'contact' => '', 'placeOfWork' => '', 'position' => ''];
    }

    public function removeReferee(int $index): void
    {
        unset($this->referees[$index]);
        $this->referees = array_values($this->referees);
    }

    public function edit(Teacher $teacher): void
    {
        $this->ensureSchoolRecord($teacher);
        $this->authorize('update', $teacher);
        $this->editingId = $teacher->id;
        $this->employeeId = $teacher->employee_id;
        $this->firstName = $teacher->first_name;
        $this->middleName = $teacher->middle_name ?? '';
        $this->lastName = $teacher->last_name;
        $this->phone = $teacher->phone ?? '';
        $this->email = $teacher->user?->email ?? $teacher->email ?? '';
        $this->password = '';
        $this->employmentDate = $teacher->employment_date?->toDateString() ?? '';
        $this->status = $teacher->status;
        $this->gender = $teacher->gender ?? '';
        $this->dateOfBirth = $teacher->date_of_birth?->toDateString() ?? '';
        $this->nationality = $teacher->nationality ?? '';
        $this->postalAddress = $teacher->postal_address ?? '';
        $this->residentialAddress = $teacher->residential_address ?? '';
        $this->gpsAddress = $teacher->gps_address ?? '';
        $this->maritalStatus = $teacher->marital_status ?? '';
        $this->religion = $teacher->religion ?? '';
        $this->emergencyContactName = $teacher->emergency_contact_name ?? '';
        $this->emergencyContactPhone = $teacher->emergency_contact_phone ?? '';
        $this->ssnitNumber = $teacher->ssnit_number ?? '';
        $this->ghanaCardNumber = $teacher->ghana_card_number ?? '';

        $this->dependants = $teacher->dependants->map(fn ($dependant) => [
            'relation' => $dependant->relation ?? '',
            'name' => $dependant->name ?? '',
            'dateOfBirth' => $dependant->date_of_birth?->toDateString() ?? '',
            'isNextOfKin' => (bool) $dependant->is_next_of_kin,
        ])->all();

        $this->qualifications = $teacher->qualifications->map(fn ($qualification) => [
            'qualification' => $qualification->qualification ?? '',
            'institution' => $qualification->institution ?? '',
            'programOfStudy' => $qualification->program_of_study ?? '',
            'yearOfGraduation' => $qualification->year_of_graduation ?? '',
        ])->all();

        $this->workExperiences = $teacher->workExperiences->map(fn ($experience) => [
            'institution' => $experience->institution ?? '',
            'country' => $experience->country ?? '',
            'position' => $experience->position ?? '',
            'address' => $experience->address ?? '',
            'startDate' => $experience->start_date?->toDateString() ?? '',
            'endDate' => $experience->end_date?->toDateString() ?? '',
        ])->all();

        $this->referees = $teacher->referees->map(fn ($referee) => [
            'name' => $referee->name ?? '',
            'contact' => $referee->contact ?? '',
            'placeOfWork' => $referee->place_of_work ?? '',
            'position' => $referee->position ?? '',
        ])->all();

        $this->showFormModal = true;
    }

    public function save(): void
    {
        $teacher = $this->editingId ? Teacher::findOrFail($this->editingId) : null;
        if ($teacher) {
            $this->ensureSchoolRecord($teacher);
        }
        $this->authorize($teacher ? 'update' : 'create', $teacher ?? Teacher::class);
        $schoolId = $this->ensureSchoolConfigured();
        try {
            $matchingAccountId = $teacher?->user_id
                ?? User::query()->whereRaw('LOWER(email) = ?', [strtolower(trim($this->email))])->value('id');
            $data = $this->validate([
                'employeeId' => ['required', 'string', 'max:50', Rule::unique('teachers', 'employee_id')->ignore($teacher?->id)],
                'firstName' => ['required', 'string', 'max:100'],
                'middleName' => ['nullable', 'string', 'max:100'],
                'lastName' => ['required', 'string', 'max:100'],
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($matchingAccountId)],
                'password' => [$matchingAccountId ? 'nullable' : 'required', 'string', 'min:10'],
                'employmentDate' => ['nullable', 'date'],
                'status' => ['required', 'in:active,inactive,retired'],
                'gender' => ['nullable', 'in:male,female,other'],
                'dateOfBirth' => ['nullable', 'date', 'before:today'],
                'nationality' => ['nullable', 'string', 'max:100'],
                'postalAddress' => ['nullable', 'string', 'max:255'],
                'residentialAddress' => ['nullable', 'string', 'max:255'],
                'gpsAddress' => ['nullable', 'string', 'max:255'],
                'maritalStatus' => ['nullable', 'in:single,married,divorced,widowed'],
                'religion' => ['nullable', 'string', 'max:100'],
                'emergencyContactName' => ['nullable', 'string', 'max:150'],
                'emergencyContactPhone' => ['nullable', 'string', 'max:30'],
                'ssnitNumber' => ['nullable', 'string', 'max:50'],
                'ghanaCardNumber' => ['nullable', 'string', 'max:50'],
                'dependants' => ['array'],
                'dependants.*.relation' => ['nullable', 'string', 'max:100'],
                'dependants.*.name' => ['nullable', 'string', 'max:150'],
                'dependants.*.dateOfBirth' => ['nullable', 'date'],
                'dependants.*.isNextOfKin' => ['boolean'],
                'qualifications' => ['array'],
                'qualifications.*.qualification' => ['nullable', 'string', 'max:150'],
                'qualifications.*.institution' => ['nullable', 'string', 'max:150'],
                'qualifications.*.programOfStudy' => ['nullable', 'string', 'max:150'],
                'qualifications.*.yearOfGraduation' => ['nullable', 'string', 'max:10'],
                'workExperiences' => ['array'],
                'workExperiences.*.institution' => ['nullable', 'string', 'max:150'],
                'workExperiences.*.country' => ['nullable', 'string', 'max:100'],
                'workExperiences.*.position' => ['nullable', 'string', 'max:150'],
                'workExperiences.*.address' => ['nullable', 'string', 'max:255'],
                'workExperiences.*.startDate' => ['nullable', 'date'],
                'workExperiences.*.endDate' => ['nullable', 'date', 'after_or_equal:workExperiences.*.startDate'],
                'referees' => ['array'],
                'referees.*.name' => ['nullable', 'string', 'max:150'],
                'referees.*.contact' => ['nullable', 'string', 'max:100'],
                'referees.*.placeOfWork' => ['nullable', 'string', 'max:150'],
                'referees.*.position' => ['nullable', 'string', 'max:150'],
            ]);

            DB::transaction(function () use ($teacher, $schoolId, $data): void {
                $record = Teacher::updateOrCreate(
                    ['id' => $teacher?->id],
                    [
                        'school_id' => $schoolId,
                        'employee_id' => $data['employeeId'],
                        'first_name' => $data['firstName'],
                        'middle_name' => $data['middleName'] ?: null,
                        'last_name' => $data['lastName'],
                        'phone' => $data['phone'] ?: null,
                        'email' => strtolower(trim($data['email'])),
                        'employment_date' => $data['employmentDate'] ?: null,
                        'status' => $data['status'],
                        'gender' => $data['gender'] ?: null,
                        'date_of_birth' => $data['dateOfBirth'] ?: null,
                        'nationality' => $data['nationality'] ?: null,
                        'postal_address' => $data['postalAddress'] ?: null,
                        'residential_address' => $data['residentialAddress'] ?: null,
                        'gps_address' => $data['gpsAddress'] ?: null,
                        'marital_status' => $data['maritalStatus'] ?: null,
                        'religion' => $data['religion'] ?: null,
                        'emergency_contact_name' => $data['emergencyContactName'] ?: null,
                        'emergency_contact_phone' => $data['emergencyContactPhone'] ?: null,
                        'ssnit_number' => $data['ssnitNumber'] ?: null,
                        'ghana_card_number' => $data['ghanaCardNumber'] ?: null,
                    ],
                );

                app(UserProfileService::class)->synchronizeAccount(
                    $record,
                    'teacher',
                    trim(implode(' ', array_filter([$data['firstName'], $data['middleName'], $data['lastName']]))),
                    $data['email'],
                    $data['password'] ?: null,
                );

                $record->dependants()->delete();
                foreach ($data['dependants'] as $dependant) {
                    if (blank($dependant['name'] ?? null) && blank($dependant['relation'] ?? null)) {
                        continue;
                    }
                    $record->dependants()->create([
                        'relation' => $dependant['relation'] ?: null,
                        'name' => $dependant['name'] ?: null,
                        'date_of_birth' => $dependant['dateOfBirth'] ?: null,
                        'is_next_of_kin' => (bool) ($dependant['isNextOfKin'] ?? false),
                    ]);
                }

                $record->qualifications()->delete();
                foreach ($data['qualifications'] as $qualification) {
                    if (blank($qualification['qualification'] ?? null) && blank($qualification['institution'] ?? null)) {
                        continue;
                    }
                    $record->qualifications()->create([
                        'qualification' => $qualification['qualification'] ?: null,
                        'institution' => $qualification['institution'] ?: null,
                        'program_of_study' => $qualification['programOfStudy'] ?: null,
                        'year_of_graduation' => $qualification['yearOfGraduation'] ?: null,
                    ]);
                }

                $record->workExperiences()->delete();
                foreach ($data['workExperiences'] as $experience) {
                    if (blank($experience['institution'] ?? null)) {
                        continue;
                    }
                    $record->workExperiences()->create([
                        'institution' => $experience['institution'] ?: null,
                        'country' => $experience['country'] ?: null,
                        'position' => $experience['position'] ?: null,
                        'address' => $experience['address'] ?: null,
                        'start_date' => $experience['startDate'] ?: null,
                        'end_date' => $experience['endDate'] ?: null,
                    ]);
                }

                $record->referees()->delete();
                foreach ($data['referees'] as $referee) {
                    if (blank($referee['name'] ?? null)) {
                        continue;
                    }
                    $record->referees()->create([
                        'name' => $referee['name'] ?: null,
                        'contact' => $referee['contact'] ?: null,
                        'place_of_work' => $referee['placeOfWork'] ?: null,
                        'position' => $referee['position'] ?: null,
                    ]);
                }
            });
            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title($teacher ? 'Teacher updated' : 'Teacher added')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $e) {
            LivewireAlert::title('Check the form')->error()->asToast()->position('top-end')->show();
            throw $e;
        } catch (Throwable $e) {
            report($e);
            LivewireAlert::title('Unable to save teacher')->error()->asToast()->position('top-end')->show();
        }
    }

    public function importTeachers(): void
    {
        $this->authorize('create', Teacher::class);
        $schoolId = $this->ensureSchoolConfigured();
        $this->importErrors = [];

        try {
            $data = $this->validate([
                'importFile' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
            ]);

            /** @var UploadedFile $file */
            $file = $data['importFile'];
            $rows = $this->readImportRows($file);
            [$imports, $errors] = $this->prepareImportRows($rows);

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
                    $record = Teacher::create($import['teacher'] + ['school_id' => $schoolId]);

                    app(UserProfileService::class)->synchronizeAccount(
                        $record,
                        'teacher',
                        trim(implode(' ', array_filter([$import['teacher']['first_name'], $import['teacher']['middle_name'], $import['teacher']['last_name']]))),
                        $import['account']['email'],
                        $import['account']['password'],
                    );
                }
            });

            $imported = count($imports);
            $this->showImportForm = false;
            $this->resetImportForm();
            $this->resetPage();
            LivewireAlert::title("{$imported} ".str('teacher')->plural($imported).' imported')
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
            LivewireAlert::title('Unable to import teachers')
                ->text('No teacher records were added. Check the file and try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        $this->authorize('create', Teacher::class);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, self::IMPORT_HEADERS);
            fputcsv($output, [
                'EMP-001', 'Kwame', '', 'Asante', 'kwame.asante@example.com', 'ChangeMe123!',
                '0240000000', now()->toDateString(), 'active', 'male', '1990-04-12', 'Ghanaian',
                'PO Box 001', 'Kumasi', 'AK-000-0000', 'married', 'Christian',
                'Adwoa Asante', '0241111111', 'SSN-000000', 'GHA-000000000-0',
            ]);
            fclose($output);
        }, 'teacher-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportTeachers(string $format = 'csv'): StreamedResponse|BinaryFileResponse
    {
        $this->authorize('viewAny', Teacher::class);
        $schoolId = $this->ensureSchoolConfigured();
        $format = $format === 'xlsx' ? 'xlsx' : 'csv';

        $teachers = $this->filteredTeachersQuery($schoolId)->orderBy('last_name')->orderBy('first_name')->get();

        $rows = $teachers->map(fn (Teacher $teacher) => [
            $teacher->employee_id,
            $teacher->first_name,
            $teacher->middle_name,
            $teacher->last_name,
            $teacher->user?->email ?? $teacher->email ?? '',
            '',
            $teacher->phone,
            $teacher->employment_date?->toDateString() ?? '',
            $teacher->status,
            $teacher->gender,
            $teacher->date_of_birth?->toDateString() ?? '',
            $teacher->nationality,
            $teacher->postal_address,
            $teacher->residential_address,
            $teacher->gps_address,
            $teacher->marital_status,
            $teacher->religion,
            $teacher->emergency_contact_name,
            $teacher->emergency_contact_phone,
            $teacher->ssnit_number,
            $teacher->ghana_card_number,
        ]);

        $filename = 'teachers-export-'.now()->format('Y-m-d').'.'.$format;

        return $format === 'xlsx'
            ? SpreadsheetExporter::xlsx($filename, self::IMPORT_HEADERS, $rows)
            : SpreadsheetExporter::csv($filename, self::IMPORT_HEADERS, $rows);
    }

    public function confirmDelete(Teacher $teacher): void
    {
        $this->ensureSchoolRecord($teacher);
        $this->authorize('delete', $teacher);
        $this->deletingId = $teacher->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $teacher = Teacher::findOrFail($this->deletingId);
        $this->ensureSchoolRecord($teacher);
        $this->authorize('delete', $teacher);
        if ($teacher->classSubjects()->exists() || $teacher->classes()->exists()) {
            $this->addError('delete', 'Teachers with class assignments must be marked inactive instead.');
            LivewireAlert::title('Teacher cannot be deleted')->warning()->asToast()->position('top-end')->show();

            return;
        }
        try {
            $teacher->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Teacher archived')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $e) {
            report($e);
            LivewireAlert::title('Unable to archive teacher')->error()->asToast()->position('top-end')->show();
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
            'editingId', 'deletingId', 'employeeId', 'firstName', 'middleName', 'lastName', 'phone', 'email',
            'password', 'employmentDate', 'status', 'gender', 'dateOfBirth', 'nationality', 'postalAddress',
            'residentialAddress', 'gpsAddress', 'maritalStatus', 'religion', 'emergencyContactName',
            'emergencyContactPhone', 'ssnitNumber', 'ghanaCardNumber', 'dependants', 'qualifications',
            'workExperiences', 'referees',
        ]);
        $this->status = 'active';
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
        abort_unless($schoolId, 422, 'Configure a school before managing teachers.');

        return $schoolId;
    }

    private function ensureSchoolRecord(Teacher $teacher): void
    {
        abort_unless($teacher->school_id === $this->schoolId(), 404);
    }

    /**
     * @param  array<int, array<int, string|int|float|null>>  $rows
     * @return array{0: array<int, array{teacher: array<string, mixed>, account: array{email: string, password: string}}>, 1: array<int, string>}
     */
    private function prepareImportRows(array $rows): array
    {
        throw_if($rows === [], ValidationException::withMessages([
            'importFile' => 'The import file is empty.',
        ]));

        $headers = array_map(fn ($header) => $this->normaliseImportHeader((string) $header), array_shift($rows));
        $requiredHeaders = ['employee_id', 'first_name', 'last_name', 'email', 'temporary_password'];
        $missingHeaders = array_diff($requiredHeaders, $headers);

        throw_if($missingHeaders !== [], ValidationException::withMessages([
            'importFile' => 'Missing required column(s): '.implode(', ', $missingHeaders).'.',
        ]));
        throw_if(count($headers) !== count(array_unique($headers)), ValidationException::withMessages([
            'importFile' => 'Column headings must be unique.',
        ]));
        throw_if($rows === [], ValidationException::withMessages([
            'importFile' => 'Add at least one teacher row below the column headings.',
        ]));
        throw_if(count($rows) > 500, ValidationException::withMessages([
            'importFile' => 'Import a maximum of 500 teacher rows at a time.',
        ]));

        $imports = [];
        $errors = [];
        $employeeIds = [];
        $emails = [];

        foreach ($rows as $offset => $row) {
            $line = $offset + 2;
            $values = [];
            foreach ($headers as $index => $header) {
                $values[$header] = trim((string) ($row[$index] ?? ''));
            }

            $teacherData = [
                'employee_id' => $values['employee_id'] ?? '',
                'first_name' => $values['first_name'] ?? '',
                'middle_name' => filled($values['middle_name'] ?? '') ? $values['middle_name'] : null,
                'last_name' => $values['last_name'] ?? '',
                'phone' => filled($values['phone'] ?? '') ? $values['phone'] : null,
                'email' => strtolower($values['email'] ?? ''),
                'employment_date' => $this->normaliseImportDate($values['employment_date'] ?? ''),
                'status' => strtolower($values['status'] ?? 'active') ?: 'active',
                'gender' => filled($values['gender'] ?? '') ? strtolower($values['gender']) : null,
                'date_of_birth' => $this->normaliseImportDate($values['date_of_birth'] ?? ''),
                'nationality' => filled($values['nationality'] ?? '') ? $values['nationality'] : null,
                'postal_address' => filled($values['postal_address'] ?? '') ? $values['postal_address'] : null,
                'residential_address' => filled($values['residential_address'] ?? '') ? $values['residential_address'] : null,
                'gps_address' => filled($values['gps_address'] ?? '') ? $values['gps_address'] : null,
                'marital_status' => filled($values['marital_status'] ?? '') ? strtolower($values['marital_status']) : null,
                'religion' => filled($values['religion'] ?? '') ? $values['religion'] : null,
                'emergency_contact_name' => filled($values['emergency_contact_name'] ?? '') ? $values['emergency_contact_name'] : null,
                'emergency_contact_phone' => filled($values['emergency_contact_phone'] ?? '') ? $values['emergency_contact_phone'] : null,
                'ssnit_number' => filled($values['ssnit_number'] ?? '') ? $values['ssnit_number'] : null,
                'ghana_card_number' => filled($values['ghana_card_number'] ?? '') ? $values['ghana_card_number'] : null,
            ];
            $accountData = [
                'email' => $teacherData['email'],
                'password' => $values['temporary_password'] ?? '',
            ];

            $validator = Validator::make($teacherData + ['temporary_password' => $accountData['password']], [
                'employee_id' => ['required', 'string', 'max:50'],
                'first_name' => ['required', 'string', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['required', 'email', 'max:255'],
                'temporary_password' => ['required', 'string', 'min:10'],
                'employment_date' => ['nullable', 'date'],
                'status' => ['required', 'in:active,inactive,retired'],
                'gender' => ['nullable', 'in:male,female,other'],
                'date_of_birth' => ['nullable', 'date', 'before:today'],
                'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$line}: ".implode(' ', $validator->errors()->all());

                continue;
            }

            $employeeKey = strtolower($teacherData['employee_id']);
            $emailKey = strtolower($accountData['email']);
            if (isset($employeeIds[$employeeKey])) {
                $errors[] = "Row {$line}: Employee ID '{$teacherData['employee_id']}' is repeated in this file.";

                continue;
            }
            if (isset($emails[$emailKey])) {
                $errors[] = "Row {$line}: Email '{$accountData['email']}' is repeated in this file.";

                continue;
            }
            if (Teacher::withTrashed()->where('employee_id', $teacherData['employee_id'])->exists()) {
                $errors[] = "Row {$line}: Employee ID '{$teacherData['employee_id']}' already exists.";

                continue;
            }
            if (User::query()->whereRaw('LOWER(email) = ?', [$emailKey])->exists()) {
                $errors[] = "Row {$line}: Email '{$accountData['email']}' already belongs to a user account.";

                continue;
            }

            $employeeIds[$employeeKey] = true;
            $emails[$emailKey] = true;

            $imports[] = [
                'teacher' => $teacherData,
                'account' => $accountData,
            ];
        }

        return [$imports, $errors];
    }

    public function render()
    {
        return view('livewire.lms.teachers.index', [
            'teachers' => $this->filteredTeachersQuery($this->schoolId())
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(15),
        ]);
    }

    private function filteredTeachersQuery(int $schoolId)
    {
        $search = trim($this->search);

        return Teacher::query()
            ->where('school_id', $schoolId)
            ->withCount(['classes', 'classSubjects'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($teachers) use ($search): void {
                    $teachers->where('employee_id', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(filled($this->filterStatus), fn ($query) => $query->where('status', $this->filterStatus))
            ->when($this->filterAssignment === 'assigned', fn ($query) => $query->where(function ($teachers): void {
                $teachers->whereHas('classes')->orWhereHas('classSubjects');
            }))
            ->when($this->filterAssignment === 'unassigned', fn ($query) => $query
                ->whereDoesntHave('classes')
                ->whereDoesntHave('classSubjects'));
    }
}
