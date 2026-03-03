<?php

namespace App\Models;

use App\Enums\RecurringInterval;
use App\Enums\TransactionType;
use Database\Factories\RecurringRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property TransactionType $type
 * @property RecurringInterval $interval
 * @property Carbon $next_run_at
 */
class RecurringRule extends Model
{
    /** @use HasFactory<RecurringRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'category_id',
        'currency_code',
        'comment',
        'interval',
        'next_run_at',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'integer',
            'interval' => RecurringInterval::class,
            'next_run_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<self> $query */
    public function scopeDue(Builder $query): void
    {
        $query->active()->where('next_run_at', '<=', now());
    }

    public function calculateNextRunAt(): Carbon
    {
        return match ($this->interval) {
            RecurringInterval::Daily => $this->next_run_at->addDay(),
            RecurringInterval::Weekly => $this->next_run_at->addWeek(),
            RecurringInterval::Monthly => $this->next_run_at->addMonth(),
            RecurringInterval::Yearly => $this->next_run_at->addYear(),
        };
    }
}
