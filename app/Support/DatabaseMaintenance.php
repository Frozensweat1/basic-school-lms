<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

class DatabaseMaintenance
{
    private const DATA_TABLES = [
        'announcement_attachments',
        'notifications',
        'audit_logs',
        'quiz_answers',
        'quiz_attempts',
        'quiz_questions',
        'quizzes',
        'question_options',
        'questions',
        'examination_scores',
        'examination_questions',
        'submission_attachments',
        'assignment_submissions',
        'assignment_attachments',
        'assignments',
        'lesson_progress',
        'lesson_resources',
        'lessons',
        'topics',
        'timetable_entries',
        'timetables',
        'schedule_periods',
        'attendance_records',
        'report_cards',
        'subject_results',
        'assessment_scores',
        'assessments',
        'assessment_components',
        'grading_scales',
        'announcements',
        'website_gallery_images',
        'website_gallery_albums',
        'website_news_posts',
        'website_events',
        'website_inquiries',
        'website_pages',
        'website_settings',
        'newsletter_subscriptions',
        'school_settings',
        'parent_student',
        'class_teachers',
        'class_subjects',
        'class_enrollments',
        'parents',
        'teachers',
        'students',
        'school_classes',
        'streams',
        'subjects',
        'terms',
        'academic_years',
        'examinations',
        'schools',
    ];

    private const RUNTIME_TABLES = [
        'jobs',
        'failed_jobs',
        'job_batches',
        'sessions',
        'password_reset_tokens',
        'cache_locks',
        'cache',
    ];

    public function backup(): string
    {
        $payload = ['generated_at' => now()->toIso8601String(), 'connection' => config('database.default'), 'tables' => []];
        foreach (self::DATA_TABLES as $table) {
            if (Schema::hasTable($table)) {
                $payload['tables'][$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
            }
        }
        foreach (['users', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'] as $table) {
            if (Schema::hasTable($table)) {
                $payload['tables'][$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
            }
        }
        $path = 'backups/lms-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    public function resetPreservingSuperAdmins(): int
    {
        $superAdminIds = User::role('super_admin')->pluck('id')->all();
        if ($superAdminIds === []) {
            throw new RuntimeException('Reset aborted because no super administrator exists.');
        }

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($superAdminIds): void {
                foreach ([...self::DATA_TABLES, ...self::RUNTIME_TABLES] as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }
                foreach (['model_has_roles', 'model_has_permissions'] as $table) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }

                    DB::table($table)
                        ->where(function ($query) use ($superAdminIds): void {
                            $query->where('model_type', '!=', User::class)
                                ->orWhereNotIn('model_id', $superAdminIds);
                        })
                        ->delete();
                }
                DB::table('users')->whereNotIn('id', $superAdminIds)->delete();
                DB::table('users')->whereIn('id', $superAdminIds)->update(['remember_token' => null]);
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        PublicWebsiteData::flushCache();

        return count($superAdminIds);
    }
}
