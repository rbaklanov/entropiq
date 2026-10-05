<?php

namespace App\Actions;

use App\Models\User;

class VerifyUserEmail
{
    public function execute(User $user, string $hash): bool
    {
        if (blank($user->email)) {
            return false;
        }

        if (! hash_equals(sha1($user->email), $hash)) {
            return false;
        }

        if ($user->email_verified_at === null) {
            $user->update(['email_verified_at' => now()]);
        }

        return true;
    }
}
