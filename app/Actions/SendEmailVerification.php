<?php

namespace App\Actions;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

class SendEmailVerification
{
    public function execute(User $user): bool
    {
        if (blank($user->email)) {
            return false;
        }

        $url = URL::temporarySignedRoute('email.verify', now()->addHours(24), [
            'user' => $user->id,
            'hash' => sha1($user->email),
        ]);

        try {
            Mail::to($user->email)
                ->locale($user->locale->value)
                ->send(new VerifyEmailMail($url));
        } catch (Throwable $exception) {
            Log::error('Unable to send email verification', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
