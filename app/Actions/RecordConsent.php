<?php

namespace App\Actions;

use App\Enums\ConsentType;
use App\Models\Consent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class RecordConsent
{
    public function execute(
        User $user,
        ConsentType $type,
        ?string $ipAddress,
        ?string $userAgent,
        ?Carbon $acceptedAt = null,
    ): Consent {
        return Consent::firstOrCreate(
            [
                'user_id' => $user->id,
                'type' => $type,
                'version' => $type->currentVersion(),
            ],
            [
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent === null ? null : Str::limit($userAgent, 509),
                'accepted_at' => $acceptedAt ?? now(),
            ],
        );
    }
}
