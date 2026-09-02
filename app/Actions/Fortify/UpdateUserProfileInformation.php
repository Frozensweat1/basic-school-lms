<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ])->validateWithBag('updateProfileInformation');

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $this->updateIdentity($user, $input['name'], $input['email']);
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $this->updateIdentity($user, $input['name'], $input['email'], true);

        $user->sendEmailVerificationNotification();
    }

    private function updateIdentity(User $user, string $name, string $email, bool $unverify = false): void
    {
        DB::transaction(function () use ($user, $name, $email, $unverify): void {
            $user->forceFill([
                'name' => $name,
                'email' => strtolower(trim($email)),
            ] + ($unverify ? ['email_verified_at' => null] : []))->save();

            $user->teacher?->update(['email' => $user->email]);
            $user->parentGuardian?->update(['email' => $user->email]);
        });
    }
}
