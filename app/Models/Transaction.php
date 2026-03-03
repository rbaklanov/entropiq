<?php

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property TransactionType $type
 * @property Carbon $date
 */
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'type',
        'amount',
        'currency_code',
        'date',
        'comment',
        'is_recurring',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'integer',
            'date' => 'date',
            'is_recurring' => 'boolean',
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
    public function scopeIncome(Builder $query): void
    {
        $query->where('type', TransactionType::Income);
    }

    /** @param Builder<self> $query */
    public function scopeExpense(Builder $query): void
    {
        $query->where('type', TransactionType::Expense);
    }

    /** @param Builder<self> $query */
    public function scopeForPeriod(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('date', [$from, $to]);
    }

    /** @param Builder<self> $query */
    public function scopeByCategory(Builder $query, int $categoryId): void
    {
        $query->where('category_id', $categoryId);
    }
}
