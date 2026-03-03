<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'email_weekly',
        'push_goals',
        'push_ai_advice',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_weekly' => 'boolean',
            'push_goals' => 'boolean',
            'push_ai_advice' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
