<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\SchoolClass;
use App\Models\User;
use App\Notifications\LmsNotification;
use Illuminate\Support\Collection;

final class LmsNotifier
{
    public static function classAudience(SchoolClass $class): Collection
    {
        $learners = $class->enrollments()
            ->with(['student.user', 'student.parents.user'])
            ->where('status', 'active')
            ->get()
            ->flatMap(function ($enrollment) {
                $student = $enrollment->student;

                return collect([$student?->user])->merge($student?->parents?->pluck('user') ?? collect());
            })
            ->filter()
            ->values();
        $teachers = $class->teachers()->with('user')->get()->pluck('user')
            ->merge($class->classSubjects()->with('teacher.user')->get()->pluck('teacher.user'));

        return $learners->merge($teachers)->filter()->unique('id')->values();
    }

    public static function send(iterable $users, string $title, string $message, ?string $url = null, string $kind = 'info'): void
    {
        $notification = new LmsNotification($title, $message, $url, $kind);

        foreach ($users as $user) {
            $user?->notify($notification);
        }
    }

    public static function announcementAudience(Announcement $announcement): Collection
    {
        if ($announcement->audience === 'class' && $announcement->school_class_id) {
            $class = SchoolClass::whereKey($announcement->school_class_id)->first();

            return $class ? self::classAudience($class) : collect();
        }

        if ($announcement->audience === 'subject' && $announcement->subject_id) {
            return SchoolClass::whereHas('classSubjects', fn ($query) => $query->where('subject_id', $announcement->subject_id))
                ->whereHas('academicYear', fn ($query) => $query->where('school_id', $announcement->school_id))
                ->get()->flatMap(fn (SchoolClass $class) => self::classAudience($class))->unique('id')->values();
        }

        if ($announcement->audience === 'teachers') {
            return User::query()
                ->whereHas('teacher', fn ($profile) => $profile->where('school_id', $announcement->school_id)->where('status', 'active'))
                ->get();
        }

        return User::query()->where(function ($query) use ($announcement) {
            $query->whereHas('student', fn ($profile) => $profile->where('school_id', $announcement->school_id))
                ->orWhereHas('teacher', fn ($profile) => $profile->where('school_id', $announcement->school_id))
                ->orWhereHas('parentGuardian', fn ($profile) => $profile->where('school_id', $announcement->school_id))
                ->orWhereHas('roles', fn ($roles) => $roles->whereIn('name', ['super_admin', 'school_admin']));
        })->get();
    }
}
