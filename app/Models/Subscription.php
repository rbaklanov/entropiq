<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property SubscriptionPlan $plan
 * @property SubscriptionStatus $status
 * @property Carbon $starts_at
 * @property ?Carbon $ends_at
 */
class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan',
        'status',
        'starts_at',
        'ends_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'plan' => SubscriptionPlan::class,
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', SubscriptionStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }
}
