<?php

namespace App\Models;

use App\Enums\Locale;
use App\Enums\SubscriptionPlan;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property Locale $locale
 * @property SubscriptionPlan $subscription_plan
 * @property ?Carbon $phone_verified_at
 * @property ?Carbon $onboarding_completed_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    use SoftDeletes;

    protected $fillable = [
        'phone',
        'name',
        'locale',
        'currency_code',
        'subscription_plan',
        'phone_verified_at',
        'onboarding_completed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'locale' => Locale::class,
            'subscription_plan' => SubscriptionPlan::class,
            'phone_verified_at' => 'datetime',
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

    /** @return HasMany<AiAdvice, $this> */
    public function aiAdvices(): HasMany
    {
        return $this->hasMany(AiAdvice::class);
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasOne<NotificationSetting, $this> */
    public function notificationSetting(): HasOne
    {
        return $this->hasOne(NotificationSetting::class);
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    public function isPremium(): bool
    {
        return $this->subscription_plan !== SubscriptionPlan::Free;
    }

    public static function canonicalPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            return '7'.substr($digits, 1);
        }

        return $digits;
    }

    public function isAdmin(): bool
    {
        $phone = self::canonicalPhone((string) $this->phone);

        if ($phone === '' || preg_match('/^7\d{10}$/', $phone) !== 1) {
            return false;
        }

        $demoPhone = config('services.sms.demo_phone');
        if (is_string($demoPhone) && $demoPhone !== '' && $phone === self::canonicalPhone($demoPhone)) {
            return false;
        }

        $adminPhones = config('services.admin.phones');
        if (! is_array($adminPhones) || $adminPhones === []) {
            return false;
        }

        foreach ($adminPhones as $adminPhone) {
            if (! is_string($adminPhone) || $adminPhone === '') {
                continue;
            }

            if ($phone === self::canonicalPhone($adminPhone)) {
                return true;
            }
        }

        return false;
    }
}
