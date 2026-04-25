<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'name' => $this->name,
            'locale' => $this->locale,
            'currency_code' => $this->currency_code,
            'subscription_plan' => $this->subscription_plan,
            'phone_verified_at' => $this->phone_verified_at?->toIso8601String(),
            'onboarding_completed_at' => $this->onboarding_completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
