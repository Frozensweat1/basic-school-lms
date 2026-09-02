<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\ParentGuardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentAdmissionService
{
    private const STUDENT_FIELDS = [
        'student_id',
        'admission_number',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'home_town',
        'region',
        'nationality',
        'denomination',
        'health_insurance_id',
        'admission_date',
        'previous_school_name',
        'previous_school_city',
        'previous_school_country',
        'previous_school_gps_address',
        'previous_school_phone',
        'previous_school_last_class',
        'has_allergies',
        'allergy_details',
        'status',
        'photo_path',
    ];

    private const GUARDIAN_FIELDS = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'gps_address',
        'city',
        'workplace',
        'ghana_card_number',
    ];

    public function __construct(private readonly UserProfileService $userProfiles) {}

    /**
     * Save an admission and every dependent record as one atomic operation.
     *
     * @param  array<string, mixed>  $studentAttributes
     * @param  array{name?: string, email?: string, password?: string|null}  $studentAccount
     * @param  array<string, mixed>  $guardianAttributes
     */
    public function admit(
        ?Student $student,
        int $schoolId,
        array $studentAttributes,
        array $studentAccount,
        ?int $schoolClassId,
        string $enrollmentType,
        array $guardianAttributes,
    ): Student {
        return DB::transaction(function () use (
            $student,
            $schoolId,
            $studentAttributes,
            $studentAccount,
            $schoolClassId,
            $enrollmentType,
            $guardianAttributes,
        ): Student {
            $enrollmentType = Str::lower(trim($enrollmentType));

            if (! in_array($enrollmentType, ClassEnrollment::ENROLLMENT_TYPES, true)) {
                throw ValidationException::withMessages([
                    'enrollmentType' => 'Choose either day or boarding enrollment.',
                ]);
            }

            $schoolClass = $this->resolveSchoolClass($schoolId, $schoolClassId);
            $student = $this->saveStudent($student, $schoolId, $studentAttributes);
            $this->synchronizeStudentAccount($student, $studentAccount);

            $canRemainEnrolled = in_array($student->status, ['active', 'suspended'], true);

            if ($schoolClass && $canRemainEnrolled) {
                $this->synchronizeEnrollment(
                    $student,
                    $schoolClass,
                    $enrollmentType,
                    $studentAttributes['admission_date'] ?? null,
                );
            } else {
                $this->completeActiveEnrollments($student);
            }

            $guardian = $this->resolveGuardian($schoolId, $guardianAttributes);
            $this->makePrimaryGuardian($student, $guardian, $guardianAttributes);

            return $student->refresh();
        });
    }

    /**
     * Convert common human-readable phone formatting into a stable value used
     * for guardian matching. A leading international "+" (or "00") is kept
     * meaningful while punctuation and spacing are discarded.
     */
    public static function normalizePhone(?string $phone): string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return '';
        }

        $hasInternationalPrefix = str_starts_with($phone, '+');
        $digits = (string) preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return '';
        }

        if (! $hasInternationalPrefix && str_starts_with($digits, '00')) {
            $hasInternationalPrefix = true;
            $digits = substr($digits, 2);
        }

        return ($hasInternationalPrefix ? '+' : '').$digits;
    }

    private function saveStudent(
        ?Student $student,
        int $schoolId,
        array $attributes,
    ): Student {
        $student ??= new Student;

        if ($student->exists && (int) $student->school_id !== $schoolId) {
            throw ValidationException::withMessages([
                'studentId' => 'The selected student belongs to another school.',
            ]);
        }

        if ($student->exists && method_exists($student, 'trashed') && $student->trashed()) {
            $student->restore();
        }

        $student->fill(Arr::only($attributes, self::STUDENT_FIELDS));
        $student->school_id = $schoolId;
        $student->save();

        return $student;
    }

    /** @param array{name?: string, email?: string, password?: string|null} $account */
    private function synchronizeStudentAccount(Student $student, array $account): void
    {
        $email = Str::lower(trim((string) ($account['email'] ?? '')));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages([
                'email' => 'Enter a valid email address for the student login.',
            ]);
        }

        $name = trim((string) ($account['name'] ?? ''));
        $name = $name !== '' ? $name : $this->studentName($student);

        $this->userProfiles->synchronizeAccount(
            $student,
            'student',
            $name,
            $email,
            filled($account['password'] ?? null) ? (string) $account['password'] : null,
        );
    }

    private function resolveSchoolClass(int $schoolId, ?int $schoolClassId): ?SchoolClass
    {
        if (! $schoolClassId) {
            return null;
        }

        $schoolClass = SchoolClass::query()
            ->whereKey($schoolClassId)
            ->whereHas('academicYear', fn ($years) => $years->where('school_id', $schoolId))
            ->lockForUpdate()
            ->first();

        if (! $schoolClass) {
            throw ValidationException::withMessages([
                'schoolClassId' => 'Choose a class that belongs to this school.',
            ]);
        }

        return $schoolClass;
    }

    private function synchronizeEnrollment(
        Student $student,
        SchoolClass $schoolClass,
        string $enrollmentType,
        mixed $admissionDate,
    ): void {
        $this->completeActiveEnrollments($student, $schoolClass->id);

        $enrollment = ClassEnrollment::query()->firstOrNew([
            'school_class_id' => $schoolClass->id,
            'student_id' => $student->id,
        ]);

        if (! $enrollment->exists) {
            $enrollment->enrolled_at = filled($admissionDate)
                ? $admissionDate
                : now()->toDateString();
        }

        $enrollment->status = ClassEnrollment::STATUS_ACTIVE;
        $enrollment->left_at = null;
        $enrollment->enrollment_type = $enrollmentType;
        $enrollment->save();
    }

    private function completeActiveEnrollments(Student $student, ?int $exceptClassId = null): void
    {
        ClassEnrollment::query()
            ->where('student_id', $student->id)
            ->where('status', ClassEnrollment::STATUS_ACTIVE)
            ->when($exceptClassId, fn ($query) => $query->where('school_class_id', '!=', $exceptClassId))
            ->lockForUpdate()
            ->get()
            ->each(function (ClassEnrollment $enrollment): void {
                $leftAt = now()->startOfDay();
                if ($enrollment->enrolled_at->gt($leftAt)) {
                    $leftAt = $enrollment->enrolled_at;
                }

                $enrollment->update([
                    'status' => ClassEnrollment::STATUS_COMPLETED,
                    'left_at' => $leftAt,
                ]);
            });
    }

    /** @param array<string, mixed> $attributes */
    private function resolveGuardian(int $schoolId, array $attributes): ParentGuardian
    {
        $email = Str::lower(trim((string) ($attributes['email'] ?? '')));
        $phone = self::normalizePhone($attributes['phone'] ?? null);

        $this->validateGuardianIdentity($attributes, $email, $phone);

        $guardians = ParentGuardian::withTrashed()
            ->where('school_id', $schoolId)
            ->lockForUpdate()
            ->get();

        $emailMatches = $guardians
            ->filter(fn (ParentGuardian $guardian): bool => Str::lower(trim((string) $guardian->email)) === $email)
            ->values();
        $phoneMatches = $guardians
            ->filter(fn (ParentGuardian $guardian): bool => self::normalizePhone($guardian->phone) === $phone)
            ->values();

        if ($emailMatches->count() > 1) {
            throw ValidationException::withMessages([
                'guardianEmail' => 'More than one guardian in this school uses this email address. Resolve the duplicate records first.',
            ]);
        }

        if ($phoneMatches->count() > 1) {
            throw ValidationException::withMessages([
                'guardianPhone' => 'More than one guardian in this school uses this phone number. Resolve the duplicate records first.',
            ]);
        }

        $emailGuardian = $emailMatches->first();
        $phoneGuardian = $phoneMatches->first();

        if ($emailGuardian && $phoneGuardian && ! $emailGuardian->is($phoneGuardian)) {
            throw ValidationException::withMessages([
                'guardianEmail' => 'The guardian email and phone number belong to different guardian records.',
                'guardianPhone' => 'The guardian email and phone number belong to different guardian records.',
            ]);
        }

        /** @var ParentGuardian $guardian */
        $guardian = $emailGuardian ?? $phoneGuardian ?? new ParentGuardian;

        if ($guardian->exists && $guardian->trashed()) {
            $guardian->restore();
        }

        $profileAttributes = Arr::only($attributes, self::GUARDIAN_FIELDS);
        $profileAttributes['email'] = $email;
        $profileAttributes['phone'] = $phone;

        $guardian->fill($profileAttributes);
        $guardian->school_id = $schoolId;
        $guardian->save();

        $this->synchronizeGuardianAccount($guardian, $email, $phone);

        return $guardian;
    }

    /** @param array<string, mixed> $attributes */
    private function validateGuardianIdentity(array $attributes, string $email, string $phone): void
    {
        $messages = [];

        if (blank($attributes['first_name'] ?? null)) {
            $messages['guardianFirstName'] = 'Enter the parent or guardian first name.';
        }

        if (blank($attributes['last_name'] ?? null)) {
            $messages['guardianLastName'] = 'Enter the parent or guardian last name.';
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $messages['guardianEmail'] = 'Enter a valid parent or guardian email address.';
        }

        if ($phone === '') {
            $messages['guardianPhone'] = 'Enter a parent or guardian phone number.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    private function synchronizeGuardianAccount(
        ParentGuardian $guardian,
        string $email,
        string $phone,
    ): void {
        $existingAccount = filled($guardian->user_id)
            ? User::query()->lockForUpdate()->find($guardian->user_id)
            : User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->first();

        try {
            $this->userProfiles->synchronizeAccount(
                $guardian,
                'parent',
                trim($guardian->first_name.' '.$guardian->last_name),
                $email,
                $existingAccount ? null : $phone,
            );
        } catch (ValidationException $exception) {
            $this->throwGuardianValidationException($exception);
        }
    }

    /** @param array<string, mixed> $attributes */
    private function makePrimaryGuardian(
        Student $student,
        ParentGuardian $guardian,
        array $attributes,
    ): void {
        DB::table('parent_student')
            ->where('student_id', $student->id)
            ->where('parent_id', '!=', $guardian->id)
            ->where('is_primary_contact', true)
            ->update([
                'is_primary_contact' => false,
                'updated_at' => now(),
            ]);

        $student->parents()->syncWithoutDetaching([
            $guardian->id => [
                'relationship' => filled($attributes['relationship'] ?? null)
                    ? trim((string) $attributes['relationship'])
                    : null,
                'is_primary_contact' => true,
                'information_date' => $attributes['information_date'] ?? null,
            ],
        ]);
    }

    private function throwGuardianValidationException(ValidationException $exception): never
    {
        $messages = [];

        foreach ($exception->errors() as $key => $errors) {
            $mappedKey = match ($key) {
                'email', 'role' => 'guardianEmail',
                'password' => 'guardianPhone',
                default => $key,
            };

            $messages[$mappedKey] = array_merge($messages[$mappedKey] ?? [], $errors);
        }

        throw ValidationException::withMessages($messages);
    }

    private function studentName(Student $student): string
    {
        return collect([$student->first_name, $student->middle_name, $student->last_name])
            ->filter(fn ($part): bool => filled($part))
            ->implode(' ');
    }
}
