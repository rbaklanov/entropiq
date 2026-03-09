<?php

namespace App\Http\Controllers;

use App\Actions\SendVerificationCode;
use App\Actions\VerifyCode;
use App\Http\Requests\SendCodeRequest;
use App\Http\Requests\VerifyCodeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Random\RandomException;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('pages.auth.login');
    }

    /**
     * @throws ValidationException
     * @throws RandomException
     */
    public function sendCode(SendCodeRequest $request, SendVerificationCode $action): RedirectResponse
    {
        $phone = $request->validated('phone');

        $action->execute($phone);

        return redirect()
            ->route('auth.verify')
            ->with('phone', $phone)
            ->with('success', __('auth.code_sent', ['phone' => $phone]));
    }

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
