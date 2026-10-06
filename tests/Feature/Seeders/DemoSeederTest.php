<?php

use App\Contracts\AiAdviceServiceInterface;
use App\Enums\TransactionType;
use App\Models\AiAdvice;
use App\Models\Category;
use App\Models\Goal;
use App\Models\NotificationSetting;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AiAdviceRuleEngine;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function demoUser(): ?User
{
    return User::where('phone', '+79990000001')->first();
}

describe('DemoSeeder', function () {
    it('creates a complete Premium demo account', function () {
        $this->seed(CategorySeeder::class);
        $this->seed(DemoSeeder::class);

        $user = demoUser();

        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('Алексей')
            ->and($user->subscription_plan->value)->not->toBe('free')
            ->and($user->transactions()->count())->toBeGreaterThan(100)
            ->and($user->recurringRules()->count())->toBeGreaterThan(0)
            ->and($user->goals()->count())->toBeGreaterThan(0)
            ->and($user->aiAdvices()->count())->toBeGreaterThan(0)
            ->and(Subscription::where('user_id', $user->id)->exists())->toBeTrue()
            ->and(NotificationSetting::where('user_id', $user->id)->exists())->toBeTrue();
    });

    it('spreads transactions over six months', function () {
        $this->seed(CategorySeeder::class);
        $this->seed(DemoSeeder::class);

        $months = demoUser()->transactions()->pluck('date')
            ->map(fn ($date) => $date->format('Y-m'))
            ->unique();

        expect($months->count())->toBeGreaterThanOrEqual(6);
    });

    it('can be run again without duplicating the demo account', function () {
        $this->seed(CategorySeeder::class);
        $this->seed(DemoSeeder::class);

        $goals = demoUser()->goals()->count();
        $advices = demoUser()->aiAdvices()->count();

        $this->seed(DemoSeeder::class);

        expect(User::where('phone', '+79990000001')->count())->toBe(1)
            ->and(demoUser()->goals()->count())->toBe($goals)
            ->and(demoUser()->aiAdvices()->count())->toBe($advices);
    });

    it('does not touch other users', function () {
        $this->seed(CategorySeeder::class);

        $other = User::factory()->create();
        $category = Category::factory()->expense()->create();
        Transaction::factory()->for($other)->expense()->count(3)->create(['category_id' => $category->id]);
        Goal::factory()->for($other)->create();
        AiAdvice::factory()->for($other)->create();

        $this->seed(DemoSeeder::class);

        expect($other->transactions()->count())->toBe(3)
            ->and($other->goals()->count())->toBe(1)
            ->and($other->aiAdvices()->count())->toBe(1);
    });

    it('leaves nothing behind when seeding fails halfway', function () {
        $this->seed(CategorySeeder::class);

        Goal::creating(fn () => throw new RuntimeException('boom'));

        try {
            $this->seed(DemoSeeder::class);
        } catch (RuntimeException) {
        }

        expect(demoUser())->toBeNull()
            ->and(Transaction::count())->toBe(0);
    });

    it('does not depend on Faker, which is not installed in production', function () {
        $offenders = collect(glob(database_path('seeders/*.php')))
            ->filter(fn (string $file) => str_contains(file_get_contents($file), 'fake(') || str_contains(file_get_contents($file), 'Faker'))
            ->map(fn (string $file) => basename($file))
            ->values()
            ->all();

        expect($offenders)->toBe([]);
    });
});

describe('DemoSeeder current month scenario', function () {
    $dates = [
        'first day of the month' => '2026-10-01 09:00:00',
        'early in the month' => '2026-10-06 14:00:00',
        'middle of the month' => '2026-10-15 12:00:00',
        'last day of the month' => '2026-10-31 23:00:00',
        'end of February' => '2027-02-28 10:00:00',
        'first minutes of the year' => '2027-01-01 00:30:00',
    ];

    beforeEach(function () {
        $this->seed(CategorySeeder::class);
        config(['services.llm.driver' => 'fake']);
    });

    it('adds current month transactions up to today and none in the future', function (string $date) {
        $this->travelTo(Carbon::parse($date));
        $this->seed(DemoSeeder::class);

        $currentMonth = demoUser()->transactions()->where('date', '>=', now()->startOfMonth())->get();

        expect($currentMonth)->not->toBeEmpty()
            ->and($currentMonth->max('date')->lte(now()->endOfDay()))->toBeTrue()
            ->and(demoUser()->transactions()->where('date', '>', now()->endOfDay())->count())->toBe(0);
    })->with($dates);

    it('makes the current month overspend', function (string $date) {
        $this->travelTo(Carbon::parse($date));
        $this->seed(DemoSeeder::class);

        $month = demoUser()->transactions()->where('date', '>=', now()->startOfMonth());

        $income = (int) (clone $month)->where('type', TransactionType::Income)->sum('amount');
        $expense = (int) (clone $month)->where('type', TransactionType::Expense)->sum('amount');

        expect($income)->toBeGreaterThan(0)
            ->and($expense)->toBeGreaterThan($income);
    })->with($dates);

    it('always triggers the spike, unusual transaction and overspending rules', function (string $date) {
        $this->travelTo(Carbon::parse($date));

        foreach (range(1, 5) as $attempt) {
            $this->seed(DemoSeeder::class);

            $rules = collect(app(AiAdviceRuleEngine::class)->evaluate(demoUser()))->pluck('ruleKey')->all();

            expect($rules)->toContain('category_spike', 'unusual_transaction', 'overspending');
        }
    })->with($dates);

    it('lets advice generation create advice for the demo account', function () {
        $this->seed(DemoSeeder::class);

        $advices = app(AiAdviceServiceInterface::class)->generateForUser(demoUser());
        $rules = $advices->map(fn (AiAdvice $advice) => $advice->basis_data['rule'] ?? null)->all();

        expect($advices->count())->toBeGreaterThanOrEqual(3)
            ->and($rules)->toContain('category_spike', 'unusual_transaction', 'overspending');
    });

    it('keeps the previous six months', function () {
        $this->seed(DemoSeeder::class);

        $months = demoUser()->transactions()
            ->where('date', '<', now()->startOfMonth())
            ->pluck('date')
            ->map(fn ($date) => $date->format('Y-m'))
            ->unique();

        expect($months->count())->toBe(6);
    });
});
