<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $generated_at
 * @property ?int $rating
 */
class AiAdvice extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'body',
        'basis_data',
        'rating',
        'is_read',
        'generated_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'basis_data' => 'json',
            'rating' => 'integer',
            'is_read' => 'boolean',
            'generated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<self> $query */
    public function scopeUnread(Builder $query): void
    {
        $query->where('is_read', false);
    }

    /** @param Builder<self> $query */
    public function scopeRated(Builder $query): void
    {
        $query->whereNotNull('rating');
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }
}
