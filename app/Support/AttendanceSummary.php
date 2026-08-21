<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AttendanceSummary
{
    private const TTL_MINUTES = 5;

    public function forStudent(Student|int $student, ?string $from = null, ?string $to = null): array
    {
        $studentId = $student instanceof Student ? $student->id : (int) $student;
        $version = (int) Cache::get($this->studentVersionKey($studentId), 1);
        $key = "attendance.summary.student.{$studentId}.{$version}.".($from ?: 'all').'.'.($to ?: 'all');

        return Cache::remember($key, now()->addMinutes(self::TTL_MINUTES), function () use ($studentId, $from, $to): array {
            $records = AttendanceRecord::query()->where('student_id', $studentId)
                ->when($from, fn ($query) => $query->whereDate('attendance_date', '>=', $from))
                ->when($to, fn ($query) => $query->whereDate('attendance_date', '<=', $to))
                ->get(['status']);

            return $this->format($records);
        });
    }

    public function forStudents(Collection|array $studentIds): array
    {
        $ids = collect($studentIds)->map(fn ($id) => (int) ($id instanceof Student ? $id->id : $id))->filter()->unique()->sort()->values();
        if ($ids->isEmpty()) return $this->format(collect());

        $version = $ids->map(fn (int $id) => (int) Cache::get($this->studentVersionKey($id), 1))->implode('-');
        $key = 'attendance.summary.students.'.$ids->implode('-').'.'.$version;

        return Cache::remember($key, now()->addMinutes(self::TTL_MINUTES), fn () => $this->format(AttendanceRecord::whereIn('student_id', $ids)->get(['status'])));
    }

    public function forSchool(int $schoolId): array
    {
        $version = (int) Cache::get($this->schoolVersionKey($schoolId), 1);
        return Cache::remember("attendance.summary.school.{$schoolId}.{$version}", now()->addMinutes(self::TTL_MINUTES), function () use ($schoolId): array {
            return $this->format(AttendanceRecord::whereHas('schoolClass.academicYear', fn ($query) => $query->where('school_id', $schoolId))->get(['status']));
        });
    }

    public function invalidate(int $studentId, ?int $schoolId = null): void
    {
        $studentKey = $this->studentVersionKey($studentId);
        Cache::forever($studentKey, (int) Cache::get($studentKey, 1) + 1);
        if ($schoolId) {
            $schoolKey = $this->schoolVersionKey($schoolId);
            Cache::forever($schoolKey, (int) Cache::get($schoolKey, 1) + 1);
        }
    }

    private function format(Collection $records): array
    {
        $total = $records->count();
        $attended = $records->whereIn('status', ['present', 'late'])->count();
        return ['total' => $total, 'attended' => $attended, 'percentage' => $total ? round($attended / $total * 100, 1) : 0, 'summary' => $records->countBy('status')->all()];
    }

    private function studentVersionKey(int $studentId): string { return "attendance.version.student.{$studentId}"; }
    private function schoolVersionKey(int $schoolId): string { return "attendance.version.school.{$schoolId}"; }
}
