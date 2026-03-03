<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property PaymentStatus $status
 * @property ?Carbon $paid_at
 * @property int $amount
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'amount',
        'currency_code',
        'provider',
        'provider_payment_id',
        'status',
        'paid_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @param Builder<self> $query */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', PaymentStatus::Completed);
    }

    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::Completed;
    }
}
