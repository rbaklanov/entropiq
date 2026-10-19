<?php

namespace App\Models;

use Database\Factories\AiAdviceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property ?array<string, mixed> $basis_data
 * @property Carbon $generated_at
 * @property ?int $rating
 */
class AiAdvice extends Model
{
    /** @use HasFactory<AiAdviceFactory> */
    use HasFactory;

    public const int RATING_HELPFUL = 1;

    public const int RATING_UNHELPFUL = -1;

    protected $table = 'ai_advices';

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

    /** @param Builder<self> $query */
    public function scopeForRule(Builder $query, string $ruleKey): void
    {
        $query->whereJsonContains('basis_data->rule', $ruleKey);
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }
}
