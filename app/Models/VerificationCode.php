<?php

namespace App\Models;

use Database\Factories\VerificationCodeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $phone
 * @property string $code
 * @property Carbon $expires_at
 * @property int $attempts
 * @property ?Carbon $verified_at
 */
class VerificationCode extends Model
{
    /** @use HasFactory<VerificationCodeFactory> */
    use HasFactory;

    public const MAX_ATTEMPTS = 3;

    public const EXPIRATION_MINUTES = 5;

    public const RESEND_COOLDOWN_SECONDS = 60;

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
            ->where('attempts', '<', self::MAX_ATTEMPTS);
    }

    /** @param Builder<self> $query */
    public function scopeForPhone(Builder $query, string $phone): void
    {
        $query->where('phone', $phone);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }
}
