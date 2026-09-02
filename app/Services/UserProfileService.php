<?php

namespace App\Services;

use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserProfileService
{
    /** @var array<string, class-string<Model>> */
    private const PROFILE_MODELS = [
        'teacher' => Teacher::class,
        'student' => Student::class,
        'parent' => ParentGuardian::class,
    ];

    /**
     * Create or update the login account belonging to a person profile.
     *
     * The caller may already be inside a larger transaction (for example while
     * updating a class enrollment). Laravel safely nests these transactions.
     */
    public function synchronizeAccount(
        Model $profile,
        string $role,
        string $name,
        string $email,
        ?string $password = null,
    ): User {
        $this->assertSupportedProfile($profile, $role);

        return DB::transaction(function () use ($profile, $role, $name, $email, $password): User {
            $email = Str::lower(trim($email));
            $linkedUser = filled($profile->getAttribute('user_id'))
                ? User::query()->lockForUpdate()->find($profile->getAttribute('user_id'))
                : null;
            $emailUser = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->first();

            if ($linkedUser && $emailUser && ! $linkedUser->is($emailUser)) {
                throw ValidationException::withMessages([
                    'email' => 'This email address belongs to another user account.',
                ]);
            }

            $user = $linkedUser ?? $emailUser;

            if ($user) {
                $this->assertAccountCanBeLinked($user, $profile, $role);
            } else {
                if (blank($password)) {
                    throw ValidationException::withMessages([
                        'password' => 'Set an initial password for the new login account.',
                    ]);
                }

                $user = new User;
            }

            $user->name = trim($name);
            $user->email = $email;

            if (filled($password)) {
                $user->password = $password;
            }

            $user->save();
            Role::findOrCreate($role, config('auth.defaults.guard', 'web'));
            $user->syncRoles([$role]);

            if ((int) $profile->getAttribute('user_id') !== $user->id) {
                $profile->setAttribute('user_id', $user->id);
                $profile->save();
            }

            return $user->refresh();
        });
    }

    /**
     * Ensure a user created in the Users module has the matching domain row.
     * Existing unlinked rows are reused by their stable business identifier.
     */
    public function synchronizeProfile(User $user, int $schoolId, string $role, array $attributes): ?Model
    {
        return DB::transaction(function () use ($user, $schoolId, $role, $attributes): ?Model {
            $this->assertRoleChangeAllowed($user, $role);

            if (! array_key_exists($role, self::PROFILE_MODELS)) {
                return null;
            }

            $profile = $this->profileForUser($user, $role)
                ?? $this->unlinkedProfileForAttributes($role, $schoolId, $user, $attributes)
                ?? new (self::PROFILE_MODELS[$role]);

            if ($profile->exists && (int) $profile->getAttribute('school_id') !== $schoolId) {
                throw ValidationException::withMessages([
                    'role' => 'The matching profile belongs to a different school.',
                ]);
            }

            if ($profile->exists && filled($profile->getAttribute('user_id'))
                && (int) $profile->getAttribute('user_id') !== $user->id) {
                throw ValidationException::withMessages([
                    'role' => 'The matching profile is already linked to another user account.',
                ]);
            }

            if (method_exists($profile, 'trashed') && $profile->trashed()) {
                $profile->restore();
            }

            $profile->fill($attributes);
            $profile->setAttribute('school_id', $schoolId);
            $profile->setAttribute('user_id', $user->id);
            $profile->save();

            return $profile->refresh();
        });
    }

    public function assertRoleChangeAllowed(User $user, string $role): void
    {
        foreach (self::PROFILE_MODELS as $profileRole => $modelClass) {
            $profile = $modelClass::withTrashed()->where('user_id', $user->id)->first();

            if ($profile && $profileRole !== $role) {
                throw ValidationException::withMessages([
                    'role' => "This account is linked to a {$profileRole} profile. Manage that profile before changing its role.",
                ]);
            }
        }
    }

    /** @return list<string> */
    public static function profileRoles(): array
    {
        return array_keys(self::PROFILE_MODELS);
    }

    private function assertSupportedProfile(Model $profile, string $role): void
    {
        $expected = self::PROFILE_MODELS[$role] ?? null;

        if (! $expected || ! $profile instanceof $expected || ! $profile->exists) {
            throw new \InvalidArgumentException('A saved profile matching the requested role is required.');
        }
    }

    private function assertAccountCanBeLinked(User $user, Model $profile, string $role): void
    {
        $unexpectedRoles = $user->roles()
            ->where('name', '!=', $role)
            ->pluck('name');

        if ($unexpectedRoles->isNotEmpty()) {
            throw ValidationException::withMessages([
                'email' => 'This email address belongs to an account with a different role.',
            ]);
        }

        foreach (self::PROFILE_MODELS as $modelClass) {
            $linkedProfile = $modelClass::withTrashed()->where('user_id', $user->id)->first();

            if ($linkedProfile && (get_class($linkedProfile) !== get_class($profile) || ! $linkedProfile->is($profile))) {
                throw ValidationException::withMessages([
                    'email' => 'This email address is already linked to another person profile.',
                ]);
            }
        }
    }

    private function profileForUser(User $user, string $role): ?Model
    {
        $modelClass = self::PROFILE_MODELS[$role];

        return $modelClass::withTrashed()->where('user_id', $user->id)->first();
    }

    private function unlinkedProfileForAttributes(
        string $role,
        int $schoolId,
        User $user,
        array $attributes,
    ): ?Model {
        $query = self::PROFILE_MODELS[$role]::withTrashed()
            ->where('school_id', $schoolId);

        if ($role === 'teacher') {
            $query->where('employee_id', $attributes['employee_id'] ?? '');
        } elseif ($role === 'student') {
            $studentId = $attributes['student_id'] ?? '';
            $admissionNumber = $attributes['admission_number'] ?? '';
            $query->where(function ($students) use ($studentId, $admissionNumber): void {
                $students->where('student_id', $studentId)
                    ->orWhere('admission_number', $admissionNumber);
            });
        } else {
            $query->whereRaw('LOWER(email) = ?', [Str::lower($user->email)]);
        }

        $matches = $query->limit(2)->get();

        if ($matches->count() > 1) {
            throw ValidationException::withMessages([
                'role' => 'More than one existing profile matches this user. Resolve the duplicate profiles first.',
            ]);
        }

        return $matches->first();
    }
}
