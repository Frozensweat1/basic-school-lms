<?php

namespace App\Services\Sms;

use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SmsRecipientResolver
{
    public const AUDIENCES = ['staff', 'parents', 'students'];

    public function __construct(private readonly PhoneNumberNormalizer $normalizer) {}

    /** @return array{recipients: Collection, skipped: Collection, missing_count: int, duplicate_count: int} */
    public function resolveBulk(int $schoolId, array $audiences, ?int $schoolClassId = null): array
    {
        $audiences = collect($audiences)
            ->map(fn(mixed $audience): string => (string) $audience)
            ->filter(fn(string $audience): bool => in_array($audience, self::AUDIENCES, true))
            ->unique()->values();

        $candidates = collect();
        foreach (self::AUDIENCES as $audience) {
            if ($audiences->contains($audience)) {
                $candidates = $candidates->concat(match ($audience) {
                    'staff' => $this->staffCandidates($schoolId, $schoolClassId),
                    'parents' => $this->parentCandidates($schoolId, $schoolClassId),
                    'students' => $this->studentCandidates($schoolId, $schoolClassId),
                });
            }
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
            'staff', 'teacher' => ($record = Teacher::query()->with('user:id,name,email')
                ->where('school_id', $schoolId)->where('status', 'active')->find((int) $id))
                ? $this->teacherCandidate($record) : null,
            'parent' => ($record = ParentGuardian::query()->with('user:id,name,email')
                ->where('school_id', $schoolId)->find((int) $id))
                ? $this->parentCandidate($record) : null,
            'student' => ($record = Student::query()->with(['user:id,name,email', 'parents'])
                ->where('school_id', $schoolId)->where('status', 'active')->find((int) $id))
                ? $this->studentCandidate($record) : null,
            default => null,
        };
    }

    /** @return Collection<int, array<string, mixed>> */
    public function searchCandidates(int $schoolId, string $search = '', int $perAudience = 15): Collection
    {
        $search = trim($search);
        $limit = max(5, min($perAudience, 25));

        $teachers = Teacher::query()->with('user:id,name,email')->where('school_id', $schoolId)
            ->where('status', 'active')->when($search !== '', fn(Builder $q) => $this->searchTeacher($q, $search))
            ->orderBy('first_name')->orderBy('last_name')->limit($limit)->get()
            ->map(fn(Teacher $record): array => $this->teacherCandidate($record));
        $parents = ParentGuardian::query()->with('user:id,name,email')->where('school_id', $schoolId)
            ->when($search !== '', fn(Builder $q) => $this->searchParent($q, $search))
            ->orderBy('first_name')->orderBy('last_name')->limit($limit)->get()
            ->map(fn(ParentGuardian $record): array => $this->parentCandidate($record));
        $students = Student::query()->with(['user:id,name,email', 'parents'])->where('school_id', $schoolId)
            ->where('status', 'active')->when($search !== '', fn(Builder $q) => $this->searchStudent($q, $search))
            ->orderBy('first_name')->orderBy('last_name')->limit($limit)->get()
            ->map(fn(Student $record): array => $this->studentCandidate($record));

        return $teachers->concat($parents)->concat($students)
            ->sortBy([['name', 'asc'], ['audience', 'asc']])->values();
    }

    private function staffCandidates(int $schoolId, ?int $classId): Collection
    {
        return Teacher::query()->with('user:id,name,email')->where('school_id', $schoolId)->where('status', 'active')
            ->when($classId, fn(Builder $query, int $id) => $query->where(fn(Builder $assignments) => $assignments
                ->whereHas('classes', fn(Builder $classes) => $classes->whereKey($id))
                ->orWhereHas('classSubjects', fn(Builder $subjects) => $subjects->where('school_class_id', $id))))
            ->orderBy('id')->get()->map(fn(Teacher $record): array => $this->teacherCandidate($record));
    }

    private function parentCandidates(int $schoolId, ?int $classId): Collection
    {
        return ParentGuardian::query()->with('user:id,name,email')->where('school_id', $schoolId)
            ->when($classId, fn(Builder $query, int $id) => $query->whereHas('students', fn(Builder $students) => $students
                ->where('students.status', 'active')->whereHas('enrollments', fn(Builder $enrollments) => $enrollments
                    ->where('school_class_id', $id)->where('status', 'active'))))
            ->orderBy('id')->get()->map(fn(ParentGuardian $record): array => $this->parentCandidate($record));
    }

    private function studentCandidates(int $schoolId, ?int $classId): Collection
    {
        return Student::query()->with(['user:id,name,email', 'parents'])->where('school_id', $schoolId)->where('status', 'active')
            ->when($classId, fn(Builder $query, int $id) => $query->whereHas('enrollments', fn(Builder $enrollments) => $enrollments
                ->where('school_class_id', $id)->where('status', 'active')))
            ->orderBy('id')->get()->map(fn(Student $record): array => $this->studentCandidate($record));
    }

    /** @param Collection<int, array<string, mixed>> $candidates */
    private function partition(Collection $candidates): array
    {
        $recipients = collect();
        $skipped = collect();
        $seen = [];
        $missing = 0;
        $duplicates = 0;

        foreach ($candidates as $candidate) {
            if (! $candidate['sendable']) {
                $missing++;
                $skipped->push([...$candidate, 'skip_reason' => 'No valid phone number']);
                continue;
            }
            if (isset($seen[$candidate['normalized_phone']])) {
                $duplicates++;
                $skipped->push([...$candidate, 'normalized_phone' => null, 'skip_reason' => 'Duplicate phone number']);
                continue;
            }
            $seen[$candidate['normalized_phone']] = true;
            $recipients->push($candidate);
        }

        return ['recipients' => $recipients, 'skipped' => $skipped, 'missing_count' => $missing, 'duplicate_count' => $duplicates];
    }

    private function teacherCandidate(Teacher $teacher): array
    {
        return $this->candidate(
            'staff',
            Teacher::class,
            $teacher->id,
            $teacher->user_id,
            'staff:' . $teacher->id,
            $this->fullName($teacher->first_name, $teacher->middle_name, $teacher->last_name),
            $teacher->phone,
            'teacher profile'
        );
    }

    private function parentCandidate(ParentGuardian $parent): array
    {
        return $this->candidate(
            'parents',
            ParentGuardian::class,
            $parent->id,
            $parent->user_id,
            'parent:' . $parent->id,
            $this->fullName($parent->first_name, $parent->last_name),
            $parent->phone,
            'parent profile'
        );
    }

    private function studentCandidate(Student $student): array
    {
        $guardian = $student->parents->sortByDesc(fn(ParentGuardian $parent): int => (int) $parent->pivot->is_primary_contact)->first();
        $source = $guardian ? ((bool) $guardian->pivot->is_primary_contact ? 'primary guardian' : 'guardian') : null;

        return $this->candidate(
            'students',
            Student::class,
            $student->id,
            $student->user_id,
            'student:' . $student->id,
            $this->fullName($student->first_name, $student->middle_name, $student->last_name),
            $guardian?->phone,
            $source
        );
    }

    private function candidate(string $audience, string $type, int $id, ?int $userId, string $key, string $name, ?string $phone, ?string $source): array
    {
        $phone = filled($phone) ? trim((string) $phone) : null;
        $normalized = $this->normalizer->normalize($phone);

        return [
            'audience' => $audience,
            'recipient_type' => $type,
            'recipient_id' => $id,
            'user_id' => $userId,
            'key' => $key,
            'name' => $name,
            'phone' => $phone,
            'normalized_phone' => $normalized,
            'phone_source' => $source,
            'sendable' => $normalized !== null
        ];
    }

    private function fullName(?string ...$parts): string
    {
        return collect($parts)->filter(fn(?string $part): bool => filled($part))->implode(' ');
    }

    private function searchTeacher(Builder $query, string $search): void
    {
        $this->search($query, $search, ['first_name', 'middle_name', 'last_name', 'employee_id', 'phone']);
    }
    private function searchParent(Builder $query, string $search): void
    {
        $this->search($query, $search, ['first_name', 'last_name', 'email', 'phone']);
    }
    private function searchStudent(Builder $query, string $search): void
    {
        $this->search($query, $search, ['first_name', 'middle_name', 'last_name', 'student_id', 'admission_number']);
    }

    private function search(Builder $query, string $search, array $columns): void
    {
        $query->where(function (Builder $matches) use ($search, $columns): void {
            $like = '%' . $search . '%';
            foreach ($columns as $index => $column) {
                $index === 0 ? $matches->where($column, 'like', $like) : $matches->orWhere($column, 'like', $like);
            }
            $matches->orWhereHas('user', fn(Builder $user) => $user->where('email', 'like', $like));
        });
    }
}
