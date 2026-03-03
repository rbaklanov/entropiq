<?php

namespace App\Models;

use Database\Factories\CpiValueFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $period
 */
class CpiValue extends Model
{
    /** @use HasFactory<CpiValueFactory> */
    use HasFactory;

    protected $fillable = [
        'period',
        'category_code',
        'value',
        'source',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'period' => 'date',
            'value' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<CpiCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CpiCategory::class, 'category_code', 'code');
    }

    /** @param Builder<self> $query */
    public function scopeForPeriod(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('period', [$from, $to]);
    }

    /** @param Builder<self> $query */
    public function scopeForCategory(Builder $query, string $categoryCode): void
    {
        $query->where('category_code', $categoryCode);
    }
}
