<?php

namespace App\Http\Controllers;

use App\Actions\VerifyUserEmail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class EmailVerificationController extends Controller
{
    public function __invoke(User $user, string $hash, VerifyUserEmail $action): RedirectResponse
    {
        abort_unless($action->execute($user, $hash), 403);

        session()->flash('success', __('profile.email_verified'));

        return redirect()->route('settings.profile');
    }
}
