<?php

namespace App\Http\Controllers;

use App\Actions\VerifyCode;
use App\Http\Requests\VerifyCodeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function verifyCode(VerifyCodeRequest $request, VerifyCode $action): RedirectResponse
    {
        $user = $action->execute(
            $request->validated('phone'),
            $request->validated('code'),
        );

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
