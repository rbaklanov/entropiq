<?php

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<string, string> $name
 * @property TransactionType $type
 * @property bool $is_savings
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'icon',
        'color',
        'is_system',
        'is_savings',
        'user_id',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'name' => 'json',
            'type' => TransactionType::class,
            'is_system' => 'boolean',
            'is_savings' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function localizedName(): string
    {
        $locale = app()->getLocale();

        return $this->name[$locale] ?? $this->name['ru'] ?? '';
    }

    /** @param Builder<self> $query */
    public function scopeSystem(Builder $query): void
    {
        $query->where('is_system', true);
    }

    /**
     * Money moved to savings is kept, not spent: such categories stay out of
     * personal inflation and do not reduce the balance that inflation erodes.
     *
     * @param  Builder<self>  $query
     */
    public function scopeSavings(Builder $query): void
    {
        $query->where('is_savings', true);
    }

    /** @param Builder<self> $query */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where(function (Builder $q) use ($userId) {
            $q->where('is_system', true)
                ->orWhere('user_id', $userId);
        });
    }
}
