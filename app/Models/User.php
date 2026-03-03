<?php

namespace App\Models;

use App\Enums\Locale;
use App\Enums\SubscriptionPlan;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property SubscriptionPlan $subscription_plan
 * @property ?Carbon $onboarding_completed_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'phone',
        'name',
        'locale',
        'currency_code',
        'subscription_plan',
        'onboarding_completed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'locale' => Locale::class,
            'subscription_plan' => SubscriptionPlan::class,
            'onboarding_completed_at' => 'datetime',
        ];
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** @return HasMany<Category, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /** @return HasMany<RecurringRule, $this> */
    public function recurringRules(): HasMany
    {
        return $this->hasMany(RecurringRule::class);
    }

    /** @return HasMany<Goal, $this> */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    public function isPremium(): bool
    {
        return $this->subscription_plan !== SubscriptionPlan::Free;
    }
}
