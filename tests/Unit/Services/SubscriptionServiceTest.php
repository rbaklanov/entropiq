<?php

use App\Contracts\SubscriptionServiceInterface;
use App\Enums\GoalStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\AiAdvice;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(SubscriptionServiceInterface::class);
});

describe('isPremium', function () {
    it('returns false for free user', function () {
        $user = User::factory()->create();

        expect($this->service->isPremium($user))->toBeFalse();
    });

    it('returns true for premium user', function () {
        $user = User::factory()->premium()->create();

        expect($this->service->isPremium($user))->toBeTrue();
    });
});

describe('canAddTransaction', function () {
    it('allows free user under limit', function () {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->create();

        Transaction::factory()->count(10)->for($user)->create([
            'category_id' => $category->id,
            'date' => now(),
        ]);

        expect($this->service->canAddTransaction($user))->toBeTrue();
        expect($this->service->transactionsRemaining($user))->toBe(40);
    });

    it('blocks free user at limit', function () {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->create();

        Transaction::factory()->count(50)->for($user)->create([
            'category_id' => $category->id,
            'date' => now(),
        ]);

        expect($this->service->canAddTransaction($user))->toBeFalse();
        expect($this->service->transactionsRemaining($user))->toBe(0);
    });

    it('always allows premium user', function () {
        $user = User::factory()->premium()->create();
        $category = Category::factory()->expense()->create();

        Transaction::factory()->count(60)->for($user)->create([
            'category_id' => $category->id,
            'date' => now(),
        ]);

        expect($this->service->canAddTransaction($user))->toBeTrue();
        expect($this->service->transactionsRemaining($user))->toBeNull();
    });

    it('does not count transactions from other months', function () {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->create();

        Transaction::factory()->count(50)->for($user)->create([
            'category_id' => $category->id,
            'date' => now()->subMonth(),
        ]);

        expect($this->service->canAddTransaction($user))->toBeTrue();
        expect($this->service->transactionsRemaining($user))->toBe(50);
    });
});

describe('canCreateGoal', function () {
    it('allows free user with no active goals', function () {
        $user = User::factory()->create();

        expect($this->service->canCreateGoal($user))->toBeTrue();
        expect($this->service->goalsRemaining($user))->toBe(1);
    });

    it('blocks free user with one active goal', function () {
        $user = User::factory()->create();
        Goal::factory()->for($user)->create(['status' => GoalStatus::Active]);

        expect($this->service->canCreateGoal($user))->toBeFalse();
        expect($this->service->goalsRemaining($user))->toBe(0);
    });

    it('allows free user with only achieved goals', function () {
        $user = User::factory()->create();
        Goal::factory()->achieved()->for($user)->create();

        expect($this->service->canCreateGoal($user))->toBeTrue();
        expect($this->service->goalsRemaining($user))->toBe(1);
    });

    it('always allows premium user', function () {
        $user = User::factory()->premium()->create();
        Goal::factory()->count(5)->for($user)->create(['status' => GoalStatus::Active]);

        expect($this->service->canCreateGoal($user))->toBeTrue();
        expect($this->service->goalsRemaining($user))->toBeNull();
    });
});

describe('canViewAdvice', function () {
    it('allows free user first advice of the week', function () {
        $user = User::factory()->create();
        $advice = AiAdvice::factory()->for($user)->create();

        expect($this->service->canViewAdvice($user, $advice))->toBeTrue();
    });

    it('allows free user to re-view same advice', function () {
        $user = User::factory()->create();
        $advice = AiAdvice::factory()->for($user)->read()->create([
            'updated_at' => now(),
        ]);

        expect($this->service->canViewAdvice($user, $advice))->toBeTrue();
    });

    it('blocks free user second advice of the week', function () {
        $user = User::factory()->create();

        $first = AiAdvice::factory()->for($user)->read()->create([
            'updated_at' => now(),
        ]);

        $second = AiAdvice::factory()->for($user)->create();

        expect($this->service->canViewAdvice($user, $second))->toBeFalse();
    });

    it('resets limit at start of new week', function () {
        $user = User::factory()->create();

        Carbon::setTestNow(Carbon::now()->startOfWeek()->subDay());
        AiAdvice::factory()->for($user)->read()->create([
            'updated_at' => Carbon::now(),
        ]);

        Carbon::setTestNow(Carbon::now()->addDays(2));
        $second = AiAdvice::factory()->for($user)->create();

        expect($this->service->canViewAdvice($user, $second))->toBeTrue();

        Carbon::setTestNow();
    });

    it('always allows premium user', function () {
        $user = User::factory()->premium()->create();

        AiAdvice::factory()->for($user)->read()->create([
            'updated_at' => now(),
        ]);

        $second = AiAdvice::factory()->for($user)->create();

        expect($this->service->canViewAdvice($user, $second))->toBeTrue();
    });
});

describe('canViewPeriod', function () {
    it('allows free user current month', function () {
        $user = User::factory()->create();

        expect($this->service->canViewPeriod($user, now()->startOfMonth()))->toBeTrue();
    });

    it('allows free user previous month', function () {
        $user = User::factory()->create();

        expect($this->service->canViewPeriod($user, now()->subMonth()->startOfMonth()))->toBeTrue();
    });

    it('blocks free user two months ago', function () {
        $user = User::factory()->create();

        expect($this->service->canViewPeriod($user, now()->subMonths(2)->startOfMonth()))->toBeFalse();
    });

    it('always allows premium user any period', function () {
        $user = User::factory()->premium()->create();

        expect($this->service->canViewPeriod($user, Carbon::create(2020, 1, 1)))->toBeTrue();
    });
});

describe('subscribe', function () {
    it('creates subscription and updates user plan', function () {
        $user = User::factory()->create();

        $subscription = $this->service->subscribe($user, SubscriptionPlan::Monthly);

        $user->refresh();

        expect($user->subscription_plan)->toBe(SubscriptionPlan::Monthly);
        expect($subscription->plan)->toBe(SubscriptionPlan::Monthly);
        expect($subscription->status)->toBe(SubscriptionStatus::Active);
        expect($subscription->ends_at->isAfter(now()))->toBeTrue();
    });

    it('sets yearly end date for yearly plan', function () {
        $user = User::factory()->create();

        $subscription = $this->service->subscribe($user, SubscriptionPlan::Yearly);

        expect(now()->diffInMonths($subscription->ends_at))->toBeGreaterThanOrEqual(11);
    });

    it('cancels previous active subscription', function () {
        $user = User::factory()->create();

        $first = $this->service->subscribe($user, SubscriptionPlan::Monthly);
        $second = $this->service->subscribe($user, SubscriptionPlan::Yearly);

        expect($first->fresh()->status)->toBe(SubscriptionStatus::Cancelled);
        expect($second->status)->toBe(SubscriptionStatus::Active);
    });

    it('lifts limits after subscribing', function () {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->create();

        Transaction::factory()->count(50)->for($user)->create([
            'category_id' => $category->id,
            'date' => now(),
        ]);

        expect($this->service->canAddTransaction($user))->toBeFalse();

        $this->service->subscribe($user, SubscriptionPlan::Monthly);
        $user->refresh();

        expect($this->service->canAddTransaction($user))->toBeTrue();
    });
});

describe('cancel', function () {
    it('reverts user to free plan', function () {
        $user = User::factory()->premium()->create();
        $this->service->subscribe($user, SubscriptionPlan::Monthly);

        $this->service->cancel($user);
        $user->refresh();

        expect($user->subscription_plan)->toBe(SubscriptionPlan::Free);
    });

    it('marks active subscriptions as cancelled', function () {
        $user = User::factory()->create();
        $subscription = $this->service->subscribe($user, SubscriptionPlan::Monthly);

        $this->service->cancel($user);

        expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Cancelled);
    });

    it('restores limits after cancelling', function () {
        $user = User::factory()->create();
        Goal::factory()->for($user)->create(['status' => GoalStatus::Active]);

        $this->service->subscribe($user, SubscriptionPlan::Monthly);
        $user->refresh();
        expect($this->service->canCreateGoal($user))->toBeTrue();

        $this->service->cancel($user);
        $user->refresh();
        expect($this->service->canCreateGoal($user))->toBeFalse();
    });
});
