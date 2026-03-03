<?php

namespace App\Models;

use App\Enums\GoalStatus;
use App\Enums\GoalType;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property GoalType $type
 * @property GoalStatus $status
 * @property int $target_amount
 * @property int $current_amount
 * @property Carbon $started_at
 * @property ?Carbon $target_date
 */
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'status',
        'icon',
        'target_amount',
        'current_amount',
        'currency_code',
        'started_at',
        'target_date',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => GoalType::class,
            'status' => GoalStatus::class,
            'target_amount' => 'integer',
            'current_amount' => 'integer',
            'started_at' => 'date',
            'target_date' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<GoalContribution, $this> */
    public function contributions(): HasMany
    {
        return $this->hasMany(GoalContribution::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', GoalStatus::Active);
    }

    public function progressPercent(): float
    {
        if ($this->target_amount === 0) {
            return 0;
        }

        return min(100, round($this->current_amount / $this->target_amount * 100, 1));
    }

    public function isAchieved(): bool
    {
        return $this->status === GoalStatus::Achieved;
    }

    public function remainingAmount(): int
    {
        return max(0, $this->target_amount - $this->current_amount);
    }
}
