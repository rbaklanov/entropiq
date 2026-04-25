<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationSettingsRequest;
use App\Http\Resources\NotificationSettingResource;
use App\Models\NotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $settings = $this->resolveSettings($request);

        return (new NotificationSettingResource($settings))
            ->response()
            ->setStatusCode(200);
    }

    public function update(UpdateNotificationSettingsRequest $request): NotificationSettingResource
    {
        $settings = $this->resolveSettings($request);

        $settings->update($request->validated());

        return new NotificationSettingResource($settings->fresh());
    }

    private function resolveSettings(Request $request): NotificationSetting
    {
        return $request->user()
            ->notificationSetting()
            ->firstOrCreate([], NotificationSetting::defaultValues());
    }
}
