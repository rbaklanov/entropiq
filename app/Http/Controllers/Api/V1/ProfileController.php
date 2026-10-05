<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ChangeUserEmail;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request, ChangeUserEmail $changeUserEmail): UserResource
    {
        $data = $request->validated();

        if (array_key_exists('email', $data)) {
            $changeUserEmail->execute($request->user(), $data['email']);
            unset($data['email']);
        }

        $request->user()->update($data);

        return new UserResource($request->user()->fresh());
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->tokens()->delete();

        $user->update([
            'phone' => "deleted_{$user->id}",
            'name' => null,
            'email' => null,
            'email_verified_at' => null,
        ]);

        $user->delete();

        return response()->json(['message' => __('profile.account_deleted')]);
    }
}
