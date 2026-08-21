<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DatabaseMaintenance
{
    private const TABLES = [
        'notifications', 'audit_logs', 'quiz_answers', 'quiz_attempts', 'question_options', 'quiz_questions', 'questions',
        'submission_attachments', 'assignment_submissions', 'assignment_attachments', 'assignments', 'lesson_progress',
        'lesson_resources', 'lessons', 'topics', 'timetable_entries', 'timetables', 'schedule_periods', 'attendance_records',
        'report_cards', 'subject_results', 'assessment_scores', 'assessments', 'assessment_components', 'grading_scales',
        'announcements', 'school_settings', 'parent_student', 'class_teachers', 'class_subjects', 'class_enrollments',
        'parents', 'teachers', 'students', 'school_classes', 'streams', 'subjects', 'terms', 'academic_years', 'examinations', 'schools',
    ];

    public function backup(): string
    {
        $payload = ['generated_at' => now()->toIso8601String(), 'connection' => config('database.default'), 'tables' => []];
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table)) $payload['tables'][$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
        }
        foreach (['users', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'] as $table) {
            if (Schema::hasTable($table)) $payload['tables'][$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
        }
        $path = 'backups/lms-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $path;
    }

    public function resetPreservingSuperAdmins(): int
    {
        $superAdminIds = User::role('super_admin')->pluck('id')->all();
        if ($superAdminIds === []) throw new RuntimeException('Reset aborted because no super administrator exists.');

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($superAdminIds): void {
                foreach (self::TABLES as $table) if (Schema::hasTable($table)) DB::table($table)->delete();
                foreach (['model_has_roles', 'model_has_permissions'] as $table) {
                    if (Schema::hasTable($table)) DB::table($table)->whereNotIn('model_id', $superAdminIds)->delete();
                }
                if (Schema::hasTable('notifications')) DB::table('notifications')->delete();
                DB::table('users')->whereNotIn('id', $superAdminIds)->delete();
                if (Schema::hasTable('password_reset_tokens')) DB::table('password_reset_tokens')->delete();
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return count($superAdminIds);
    }
}
