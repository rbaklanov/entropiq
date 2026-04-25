<?php

namespace App\Models;

use Database\Factories\NotificationSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    /** @use HasFactory<NotificationSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_weekly',
        'push_goals',
        'push_ai_advice',
    ];

    /** @return array{email_weekly: bool, push_goals: bool, push_ai_advice: bool} */
    public static function defaultValues(): array
    {
        return [
            'email_weekly' => true,
            'push_goals' => true,
            'push_ai_advice' => true,
        ];
    }

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
