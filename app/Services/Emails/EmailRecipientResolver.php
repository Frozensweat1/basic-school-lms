<?php

namespace App\Services\Emails;

use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EmailRecipientResolver
{
    public const AUDIENCES = ['staff', 'parents', 'students'];

    /**
     * @return array{recipients: Collection<int, array<string, mixed>>, skipped: Collection<int, array<string, mixed>>, missing_count: int, duplicate_count: int}
     */
    public function resolveBulk(int $schoolId, array $audiences, ?int $schoolClassId = null): array
    {
        $audiences = collect($audiences)
            ->map(fn (mixed $audience): string => (string) $audience)
            ->filter(fn (string $audience): bool => in_array($audience, self::AUDIENCES, true))
            ->unique()
            ->values();

        $candidates = collect();

        foreach (self::AUDIENCES as $audience) {
            if (! $audiences->contains($audience)) {
                continue;
            }

            $candidates = $candidates->concat(match ($audience) {
                'staff' => $this->staffCandidates($schoolId, $schoolClassId),
                'parents' => $this->parentCandidates($schoolId, $schoolClassId),
                'students' => $this->studentCandidates($schoolId, $schoolClassId),
            });
        }

        return $this->partition($candidates);
    }

    /** @return array<string, mixed>|null */
    public function resolveSingle(int $schoolId, string $recipientKey): ?array
    {
        [$type, $id] = array_pad(explode(':', $recipientKey, 2), 2, null);
        if (! ctype_digit((string) $id)) {
            return null;
        }

        return match ($type) {
            'staff', 'teacher' => ($teacher = Teacher::query()
                ->with('user:id,name,email')
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->find((int) $id)) ? $this->teacherCandidate($teacher) : null,
            'parent' => ($parent = ParentGuardian::query()
                ->with('user:id,name,email')
                ->where('school_id', $schoolId)
                ->find((int) $id)) ? $this->parentCandidate($parent) : null,
            'student' => ($student = Student::query()
                ->with('user:id,name,email')
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->find((int) $id)) ? $this->studentCandidate($student) : null,
            default => null,
        };
    }

    /** @return Collection<int, array<string, mixed>> */
    public function searchCandidates(int $schoolId, string $search = '', int $perAudience = 15): Collection
    {
        $search = trim($search);
        $limit = max(5, min($perAudience, 25));

        $teachers = Teacher::query()
            ->with('user:id,name,email')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->when($search !== '', fn (Builder $query) => $this->searchTeacher($query, $search))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit($limit)
            ->get()
            ->map(fn (Teacher $teacher): array => $this->teacherCandidate($teacher));

        $parents = ParentGuardian::query()
            ->with('user:id,name,email')
            ->where('school_id', $schoolId)
            ->when($search !== '', fn (Builder $query) => $this->searchParent($query, $search))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit($limit)
            ->get()
            ->map(fn (ParentGuardian $parent): array => $this->parentCandidate($parent));

        $students = Student::query()
            ->with('user:id,name,email')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->when($search !== '', fn (Builder $query) => $this->searchStudent($query, $search))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit($limit)
            ->get()
            ->map(fn (Student $student): array => $this->studentCandidate($student));

        return $teachers
            ->concat($parents)
            ->concat($students)
            ->sortBy([['name', 'asc'], ['audience', 'asc']])
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function staffCandidates(int $schoolId, ?int $schoolClassId): Collection
    {
        return Teacher::query()
            ->with('user:id,name,email')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->when($schoolClassId, function (Builder $query, int $classId): void {
                $query->where(function (Builder $assignments) use ($classId): void {
                    $assignments
                        ->whereHas('classes', fn (Builder $classes) => $classes->whereKey($classId))
                        ->orWhereHas('classSubjects', fn (Builder $subjects) => $subjects->where('school_class_id', $classId));
                });
            })
            ->orderBy('id')
            ->get()
            ->map(fn (Teacher $teacher): array => $this->teacherCandidate($teacher));
    }

    /** @return Collection<int, array<string, mixed>> */
    private function parentCandidates(int $schoolId, ?int $schoolClassId): Collection
    {
        return ParentGuardian::query()
            ->with('user:id,name,email')
            ->where('school_id', $schoolId)
            ->when($schoolClassId, function (Builder $query, int $classId): void {
                $query->whereHas('students', function (Builder $students) use ($classId): void {
                    $students
                        ->where('students.status', 'active')
                        ->whereHas('enrollments', fn (Builder $enrollments) => $enrollments
                            ->where('school_class_id', $classId)
                            ->where('status', 'active'));
                });
            })
            ->orderBy('id')
            ->get()
            ->map(fn (ParentGuardian $parent): array => $this->parentCandidate($parent));
    }

    /** @return Collection<int, array<string, mixed>> */
    private function studentCandidates(int $schoolId, ?int $schoolClassId): Collection
    {
        return Student::query()
            ->with('user:id,name,email')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->when($schoolClassId, fn (Builder $query, int $classId) => $query
                ->whereHas('enrollments', fn (Builder $enrollments) => $enrollments
                    ->where('school_class_id', $classId)
                    ->where('status', 'active')))
            ->orderBy('id')
            ->get()
            ->map(fn (Student $student): array => $this->studentCandidate($student));
    }

    /** @param Collection<int, array<string, mixed>> $candidates */
    private function partition(Collection $candidates): array
    {
        $recipients = collect();
        $skipped = collect();
        $seen = [];
        $missingCount = 0;
        $duplicateCount = 0;

        foreach ($candidates as $candidate) {
            if (! $candidate['sendable']) {
                $missingCount++;
                $skipped->push([...$candidate, 'skip_reason' => 'No valid email address']);

                continue;
            }

            $normalizedEmail = $candidate['normalized_email'];
            if (isset($seen[$normalizedEmail])) {
                $duplicateCount++;
                $skipped->push([
                    ...$candidate,
                    'normalized_email' => null,
                    'skip_reason' => 'Duplicate email address',
                ]);

                continue;
            }

            $seen[$normalizedEmail] = true;
            $recipients->push($candidate);
        }

        return [
            'recipients' => $recipients,
            'skipped' => $skipped,
            'missing_count' => $missingCount,
            'duplicate_count' => $duplicateCount,
        ];
    }

    /** @return array<string, mixed> */
    private function teacherCandidate(Teacher $teacher): array
    {
        return $this->candidate(
            audience: 'staff',
            type: Teacher::class,
            id: $teacher->id,
            userId: $teacher->user_id,
            key: 'staff:'.$teacher->id,
            name: $this->fullName($teacher->first_name, $teacher->middle_name, $teacher->last_name),
            email: $this->firstEmail($teacher->email, $teacher->user?->email),
        );
    }

    /** @return array<string, mixed> */
    private function parentCandidate(ParentGuardian $parent): array
    {
        return $this->candidate(
            audience: 'parents',
            type: ParentGuardian::class,
            id: $parent->id,
            userId: $parent->user_id,
            key: 'parent:'.$parent->id,
            name: $this->fullName($parent->first_name, null, $parent->last_name),
            email: $this->firstEmail($parent->email, $parent->user?->email),
        );
    }

    /** @return array<string, mixed> */
    private function studentCandidate(Student $student): array
    {
        return $this->candidate(
            audience: 'students',
            type: Student::class,
            id: $student->id,
            userId: $student->user_id,
            key: 'student:'.$student->id,
            name: $this->fullName($student->first_name, $student->middle_name, $student->last_name),
            email: $this->firstEmail($student->user?->email),
        );
    }

    /** @return array<string, mixed> */
    private function candidate(string $audience, string $type, int $id, ?int $userId, string $key, string $name, ?string $email): array
    {
        $email = $email ? trim($email) : null;
        $normalizedEmail = $email ? Str::lower($email) : null;
        $sendable = $normalizedEmail !== null && filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) !== false;

        return [
            'audience' => $audience,
            'recipient_type' => $type,
            'recipient_id' => $id,
            'user_id' => $userId,
            'key' => $key,
            'name' => $name,
            'email' => $email,
            'normalized_email' => $sendable ? $normalizedEmail : null,
            'sendable' => $sendable,
        ];
    }

    private function firstEmail(?string ...$emails): ?string
    {
        $firstNonBlank = null;

        foreach ($emails as $email) {
            $email = trim((string) $email);
            if ($email === '') {
                continue;
            }

            $firstNonBlank ??= $email;
            if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                return $email;
            }
        }

        return $firstNonBlank;
    }

    private function fullName(?string ...$parts): string
    {
        return collect($parts)->filter(fn (?string $part): bool => filled($part))->implode(' ');
    }

    private function searchTeacher(Builder $query, string $search): void
    {
        $like = '%'.$search.'%';
        $query->where(function (Builder $matches) use ($like): void {
            $matches->where('first_name', 'like', $like)
                ->orWhere('middle_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('employee_id', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhereHas('user', fn (Builder $user) => $user->where('email', 'like', $like));
        });
    }

    private function searchParent(Builder $query, string $search): void
    {
        $like = '%'.$search.'%';
        $query->where(function (Builder $matches) use ($like): void {
            $matches->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhereHas('user', fn (Builder $user) => $user->where('email', 'like', $like));
        });
    }

    private function searchStudent(Builder $query, string $search): void
    {
        $like = '%'.$search.'%';
        $query->where(function (Builder $matches) use ($like): void {
            $matches->where('first_name', 'like', $like)
                ->orWhere('middle_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('student_id', 'like', $like)
                ->orWhere('admission_number', 'like', $like)
                ->orWhereHas('user', fn (Builder $user) => $user->where('email', 'like', $like));
        });
    }
}
