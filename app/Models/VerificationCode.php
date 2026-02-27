<?php

namespace App\Models;

use Database\Factories\VerificationCodeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $expires_at
 * @property ?Carbon $verified_at
 */
class VerificationCode extends Model
{
    /** @use HasFactory<VerificationCodeFactory> */
    use HasFactory;

    protected $fillable = [
        'phone',
        'code',
        'expires_at',
        'attempts',
        'verified_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', 3);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= 3;
    }

    public function isUsable(): bool
    {
        return ! $this->isExpired()
            && ! $this->hasExceededAttempts()
            && $this->verified_at === null;
    }
}
