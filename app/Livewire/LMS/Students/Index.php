<?php

namespace App\Livewire\LMS\Students;

use App\Models\ClassEnrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use ZipArchive;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public bool $showImportForm = false;

    public ?int $editingId = null;
    public ?int $deletingId = null;

    public string $search = '';
    public string $filterStatus = '';
    public string $filterGender = '';
    public string $filterClassId = '';

    public string $studentId = '';
    public string $admissionNumber = '';
    public string $firstName = '';
    public string $middleName = '';
    public string $lastName = '';
    public string $dateOfBirth = '';
    public string $gender = '';
    public string $admissionDate = '';
    public string $schoolClassId = '';
    public string $status = 'active';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|UploadedFile|null */
    public $importFile = null;

    /** @var array<int, string> */
    public array $importErrors = [];

    private const STUDENT_STATUSES = ['active', 'graduated', 'transferred', 'withdrawn', 'suspended'];

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

    public function updatedImportFile(): void
    {
        $this->importErrors = [];
        $this->resetValidation('importFile');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterStatus', 'filterGender', 'filterClassId']);
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
        $this->dateOfBirth = $student->date_of_birth->toDateString();
        $this->gender = $student->gender;
        $this->admissionDate = $student->admission_date->toDateString();
        $this->schoolClassId = (string) $student->enrollments()
            ->where('status', 'active')
            ->value('school_class_id');
        $this->status = $student->status;
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
            $data = $this->validate([
                'studentId' => ['required', 'string', 'max:50', Rule::unique('students', 'student_id')->ignore($student?->id)],
                'admissionNumber' => ['required', 'string', 'max:50', Rule::unique('students', 'admission_number')->ignore($student?->id)],
                'firstName' => ['required', 'string', 'max:100'],
                'middleName' => ['nullable', 'string', 'max:100'],
                'lastName' => ['required', 'string', 'max:100'],
                'dateOfBirth' => ['required', 'date', 'before:today'],
                'gender' => ['required', 'in:male,female,other'],
                'admissionDate' => ['required', 'date'],
                'schoolClassId' => ['nullable', 'integer', 'exists:school_classes,id'],
                'status' => ['required', Rule::in(self::STUDENT_STATUSES)],
            ]);

            if (filled($data['schoolClassId'])) {
                $this->schoolClassesQuery($schoolId)->whereKey($data['schoolClassId'])->firstOrFail();
            }

            DB::transaction(function () use ($student, $schoolId, $data): void {
                $record = Student::updateOrCreate(
                    ['id' => $student?->id],
                    [
                        'school_id' => $schoolId,
                        'student_id' => $data['studentId'],
                        'admission_number' => $data['admissionNumber'],
                        'first_name' => $data['firstName'],
                        'middle_name' => filled($data['middleName']) ? $data['middleName'] : null,
                        'last_name' => $data['lastName'],
                        'date_of_birth' => $data['dateOfBirth'],
                        'gender' => $data['gender'],
                        'admission_date' => $data['admissionDate'],
                        'status' => $data['status'],
                    ],
                );

                if (filled($data['schoolClassId'])) {
                    ClassEnrollment::query()
                        ->where('student_id', $record->id)
                        ->where('status', 'active')
                        ->where('school_class_id', '!=', $data['schoolClassId'])
                        ->update(['status' => 'transferred', 'left_at' => now()->toDateString()]);

                    ClassEnrollment::updateOrCreate(
                        ['student_id' => $record->id, 'school_class_id' => $data['schoolClassId']],
                        ['enrolled_at' => $data['admissionDate'], 'status' => 'active', 'left_at' => null],
                    );
                }
            });

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
                    $student = Student::create([
                        'school_id' => $schoolId,
                        'student_id' => $import['student']['student_id'],
                        'admission_number' => $import['student']['admission_number'],
                        'first_name' => $import['student']['first_name'],
                        'middle_name' => $import['student']['middle_name'],
                        'last_name' => $import['student']['last_name'],
                        'date_of_birth' => $import['student']['date_of_birth'],
                        'gender' => $import['student']['gender'],
                        'admission_date' => $import['student']['admission_date'],
                        'status' => $import['student']['status'],
                    ]);

                    if ($import['school_class_id']) {
                        ClassEnrollment::create([
                            'student_id' => $student->id,
                            'school_class_id' => $import['school_class_id'],
                            'enrolled_at' => $import['student']['admission_date'],
                            'status' => 'active',
                        ]);
                    }
                }
            });

            $imported = count($imports);
            $this->showImportForm = false;
            $this->resetImportForm();
            $this->resetPage();
            LivewireAlert::title("{$imported} " . str('student')->plural($imported) . ' imported')
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
            fputcsv($output, [
                'student_id', 'admission_number', 'first_name', 'middle_name', 'last_name',
                'date_of_birth', 'gender', 'admission_date', 'status', 'class_name',
            ]);
            fputcsv($output, [
                'STU-001', 'ADM-001', 'Ama', '', 'Mensah',
                '2015-06-12', 'female', now()->toDateString(), 'active', 'Basic 1',
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
            'lastName', 'dateOfBirth', 'gender', 'admissionDate', 'schoolClassId', 'status',
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
        abort_unless($schoolId, 422, 'Configure a school before managing students.');

        return $schoolId;
    }

    private function ensureSchoolRecord(Student $student): void
    {
        abort_unless($student->school_id === $this->schoolId(), 404);
    }

    private function schoolClassesQuery(int $schoolId)
    {
        return SchoolClass::query()->whereHas('academicYear', fn ($years) => $years->where('school_id', $schoolId));
    }

    /** @return array<int, array<int, string|int|float|null>> */
    private function readImportRows(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        throw_unless($path, ValidationException::withMessages(['importFile' => 'The uploaded file could not be read.']));

        return strtolower($file->getClientOriginalExtension()) === 'xlsx'
            ? $this->readXlsxRows($path)
            : $this->readCsvRows($path);
    }

    /** @return array<int, array<int, string|null>> */
    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        throw_unless($handle, ValidationException::withMessages(['importFile' => 'The CSV file could not be opened.']));

        $rows = [];

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $rows[] = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /** @return array<int, array<int, string|int|float|null>> */
    private function readXlsxRows(string $path): array
    {
        throw_unless(class_exists(ZipArchive::class), ValidationException::withMessages([
            'importFile' => 'Excel imports require the PHP ZIP extension.',
        ]));

        $archive = new ZipArchive;
        throw_unless($archive->open($path) === true, ValidationException::withMessages([
            'importFile' => 'The Excel file could not be opened. Upload a valid .xlsx file.',
        ]));

        try {
            $sheet = $archive->getFromName('xl/worksheets/sheet1.xml');
            throw_unless($sheet !== false, ValidationException::withMessages([
                'importFile' => 'The Excel file does not contain a first worksheet.',
            ]));

            $sharedStrings = $this->xlsxSharedStrings($archive->getFromName('xl/sharedStrings.xml') ?: null);
            $xml = simplexml_load_string($sheet);
            throw_unless($xml !== false, ValidationException::withMessages([
                'importFile' => 'The first worksheet could not be read.',
            ]));

            $rows = [];
            foreach ($xml->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $row) {
                $values = [];

                foreach ($row->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                    $reference = (string) $cell['r'];
                    $column = $this->xlsxColumnIndex((string) preg_replace('/\d+/', '', $reference));
                    $type = (string) $cell['t'];
                    $valueNode = $cell->xpath('./*[local-name()="v"]')[0] ?? null;
                    $value = $valueNode === null ? null : (string) $valueNode;

                    if ($type === 's' && $value !== null) {
                        $value = $sharedStrings[(int) $value] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $value = implode('', array_map('strval', $cell->xpath('.//*[local-name()="t"]') ?: []));
                    }

                    $values[$column] = $value;
                }

                if ($values !== []) {
                    ksort($values);
                    $rows[] = $values;
                }
            }

            return $rows;
        } finally {
            $archive->close();
        }
    }

    /** @return array<int, string> */
    private function xlsxSharedStrings(?string $xml): array
    {
        if (! $xml) {
            return [];
        }

        $document = simplexml_load_string($xml);
        if ($document === false) {
            return [];
        }

        $strings = [];
        foreach ($document->xpath('//*[local-name()="si"]') ?: [] as $item) {
            $strings[] = implode('', array_map('strval', $item->xpath('.//*[local-name()="t"]') ?: []));
        }

        return $strings;
    }

    private function xlsxColumnIndex(string $column): int
    {
        $index = 0;
        foreach (str_split(strtoupper($column)) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }

    /**
     * @param array<int, array<int, string|int|float|null>> $rows
     * @return array{0: array<int, array{student: array<string, string|null>, school_class_id: int|null}>, 1: array<int, string>}
     */
    private function prepareImportRows(array $rows, int $schoolId): array
    {
        throw_if($rows === [], ValidationException::withMessages([
            'importFile' => 'The import file is empty.',
        ]));

        $headers = array_map(fn ($header) => $this->normaliseImportHeader((string) $header), array_shift($rows));
        $requiredHeaders = ['student_id', 'admission_number', 'first_name', 'last_name', 'date_of_birth', 'gender', 'admission_date'];
        $missingHeaders = array_diff($requiredHeaders, $headers);

        throw_if($missingHeaders !== [], ValidationException::withMessages([
            'importFile' => 'Missing required column(s): ' . implode(', ', $missingHeaders) . '.',
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
                'admission_date' => $this->normaliseImportDate($values['admission_date'] ?? ''),
                'status' => strtolower($values['status'] ?? 'active') ?: 'active',
            ];

            $validator = Validator::make($studentData, [
                'student_id' => ['required', 'string', 'max:50'],
                'admission_number' => ['required', 'string', 'max:50'],
                'first_name' => ['required', 'string', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'date_of_birth' => ['required', 'date', 'before:today'],
                'gender' => ['required', 'in:male,female,other'],
                'admission_date' => ['required', 'date'],
                'status' => ['required', Rule::in(self::STUDENT_STATUSES)],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$line}: " . implode(' ', $validator->errors()->all());
                continue;
            }

            $studentKey = strtolower($studentData['student_id']);
            $admissionKey = strtolower($studentData['admission_number']);
            if (isset($studentIds[$studentKey])) {
                $errors[] = "Row {$line}: Student ID '{$studentData['student_id']}' is repeated in this file.";
                continue;
            }
            if (isset($admissionNumbers[$admissionKey])) {
                $errors[] = "Row {$line}: Admission number '{$studentData['admission_number']}' is repeated in this file.";
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

            $studentIds[$studentKey] = true;
            $admissionNumbers[$admissionKey] = true;
            $schoolClassId = null;
            $className = trim($values['class_name'] ?? '');

            if ($className !== '') {
                $matchingClasses = $classesByName->get(strtolower($className), collect());
                if ($matchingClasses->count() !== 1) {
                    $errors[] = $matchingClasses->isEmpty()
                        ? "Row {$line}: '{$className}' is not an active class."
                        : "Row {$line}: '{$className}' matches multiple active classes; use a unique class name.";
                    continue;
                }

                if ($studentData['status'] !== 'active') {
                    $errors[] = "Row {$line}: Only active students can be assigned to a class during import.";
                    continue;
                }

                $schoolClassId = $matchingClasses->first()->id;
            }

            $imports[] = ['student' => $studentData, 'school_class_id' => $schoolClassId];
        }

        return [$imports, $errors];
    }

    private function normaliseImportHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', trim($header)) ?? '';

        return strtolower(str_replace([' ', '-'], '_', $header));
    }

    private function normaliseImportDate(string $value): ?string
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            if (is_numeric($value) && (float) $value > 20_000) {
                return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
            }

            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    public function render()
    {
        $schoolId = $this->schoolId();
        $search = trim($this->search);

        return view('livewire.lms.students.index', [
            'students' => Student::query()
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
                    ->where('school_class_id', $this->filterClassId)))
                ->latest()
                ->paginate(15),
            'classes' => $this->schoolClassesQuery($schoolId)
                ->with('stream')
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
