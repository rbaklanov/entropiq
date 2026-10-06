<?php

use App\Models\AiAdvice;
use App\Models\Category;
use App\Models\Goal;
use App\Models\NotificationSetting;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
