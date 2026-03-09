<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\SendVerificationCode;
use App\Actions\VerifyCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendCodeRequest;
use App\Http\Requests\VerifyCodeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Random\RandomException;

class AuthController extends Controller
{
    /**
     * @throws ValidationException
     * @throws RandomException
     */
    public function sendCode(SendCodeRequest $request, SendVerificationCode $action): JsonResponse
    {
        $phone = $request->validated('phone');

        $action->execute($phone);

        return response()->json([
            'message' => __('auth.code_sent', ['phone' => $phone]),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function verifyCode(VerifyCodeRequest $request, VerifyCode $action): JsonResponse
    {
        $user = $action->execute(
            $request->validated('phone'),
            $request->validated('code'),
        );

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'name' => $user->name,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => __('auth.logout'),
        ]);
    }
}
