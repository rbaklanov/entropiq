<?php

namespace App\Http\Resources;

use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NotificationSetting */
class NotificationSettingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'email_weekly' => $this->email_weekly,
            'push_goals' => $this->push_goals,
            'push_ai_advice' => $this->push_ai_advice,
        ];
    }
}
