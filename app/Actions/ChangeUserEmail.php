<?php

namespace App\Actions;

use App\Models\User;

class ChangeUserEmail
{
    public function __construct(
        private readonly SendEmailVerification $sendEmailVerification,
    ) {}

    public function execute(User $user, ?string $email): void
    {
        $email = filled($email) ? mb_strtolower(trim($email)) : null;

        if ($email === $user->email) {
            return;
        }

        $user->update([
            'email' => $email,
            'email_verified_at' => null,
        ]);

        if ($email !== null) {
            $this->sendEmailVerification->execute($user);
        }
    }
}
