<?php

namespace App\Models;

use App\Enums\ConsentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consent extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'version',
        'ip_address',
        'user_agent',
        'accepted_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ConsentType::class,
            'accepted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
