<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPlan;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property PaymentStatus $status
 * @property ?Carbon $paid_at
 * @property ?Carbon $refunded_at
 * @property int $amount
 * @property int $refunded_amount
 * @property ?SubscriptionPlan $plan
 * @property bool $is_recurring
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'plan',
        'is_recurring',
        'parent_payment_id',
        'amount',
        'refunded_amount',
        'currency_code',
        'provider',
        'provider_payment_id',
        'refund_request_id',
        'status',
        'paid_at',
        'refunded_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'refunded_amount' => 'integer',
            'plan' => SubscriptionPlan::class,
            'is_recurring' => 'boolean',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
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

    /** @return BelongsTo<Payment, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'parent_payment_id');
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
