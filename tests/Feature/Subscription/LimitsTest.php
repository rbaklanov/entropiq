<?php

use App\Enums\GoalStatus;
use App\Models\AiAdvice;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function limitsUser(bool $premium = false): array
{
    $user = $premium
        ? User::factory()->premium()->create(['phone_verified_at' => now()])
        : User::factory()->create(['phone_verified_at' => now()]);

    $token = $user->createToken('test')->plainTextToken;

    return [$user, $token];
}

function limitsHeaders(string $token): array
{
    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
}

describe('Goal creation limits', function () {
    it('blocks free user from creating second goal via API', function () {
        [$user, $token] = limitsUser(false);
        Goal::factory()->for($user)->create(['status' => GoalStatus::Active]);

        $this->withHeaders(limitsHeaders($token))
            ->postJson('/api/v1/goals', [
                'name' => 'Second goal',
                'type' => 'large_purchase',
                'target_amount' => 500000,
            ])
            ->assertForbidden();
    });

    it('allows free user to create first goal via API', function () {
        [$user, $token] = limitsUser(false);

        $this->withHeaders(limitsHeaders($token))
            ->postJson('/api/v1/goals', [
                'name' => 'First goal',
                'type' => 'large_purchase',
                'target_amount' => 500000,
            ])
            ->assertCreated();
    });

    it('allows premium user to create multiple goals via API', function () {
        [$user, $token] = limitsUser(true);
        Goal::factory()->count(5)->for($user)->create(['status' => GoalStatus::Active]);

        $this->withHeaders(limitsHeaders($token))
            ->postJson('/api/v1/goals', [
                'name' => 'Another goal',
                'type' => 'large_purchase',
                'target_amount' => 500000,
            ])
            ->assertCreated();
    });
});

describe('Transaction creation limits', function () {
    it('blocks free user at 50 transactions per month via API', function () {
        [$user, $token] = limitsUser(false);
        $category = Category::factory()->expense()->create();

        Transaction::factory()->count(50)->for($user)->create([
            'category_id' => $category->id,
            'date' => now(),
        ]);

        $this->withHeaders(limitsHeaders($token))
            ->postJson('/api/v1/transactions', [
                'type' => 'expense',
                'amount' => 5000,
                'category_id' => $category->id,
                'date' => now()->toDateString(),
            ])
            ->assertForbidden();
    });

    it('allows free user under limit via API', function () {
        [$user, $token] = limitsUser(false);
        $category = Category::factory()->expense()->create();

        $this->withHeaders(limitsHeaders($token))
            ->postJson('/api/v1/transactions', [
                'type' => 'expense',
                'amount' => 5000,
                'category_id' => $category->id,
                'date' => now()->toDateString(),
            ])
            ->assertCreated();
    });

    it('allows premium user beyond limit via API', function () {
        [$user, $token] = limitsUser(true);
        $category = Category::factory()->expense()->create();

        Transaction::factory()->count(60)->for($user)->create([
            'category_id' => $category->id,
            'date' => now(),
        ]);

        $this->withHeaders(limitsHeaders($token))
            ->postJson('/api/v1/transactions', [
                'type' => 'expense',
                'amount' => 5000,
                'category_id' => $category->id,
                'date' => now()->toDateString(),
            ])
            ->assertCreated();
    });
});

describe('Analytics period limits', function () {
    it('blocks free user from viewing old periods', function () {
        [, $token] = limitsUser(false);

        $this->withHeaders(limitsHeaders($token))
            ->getJson('/api/v1/analytics/expenses-by-category?from=2025-01-01&to=2025-01-31')
            ->assertForbidden();
    });

    it('allows free user current month', function () {
        [, $token] = limitsUser(false);

        $from = now()->startOfMonth()->toDateString();
        $to = now()->endOfMonth()->toDateString();

        $this->withHeaders(limitsHeaders($token))
            ->getJson("/api/v1/analytics/expenses-by-category?from={$from}&to={$to}")
            ->assertOk();
    });

    it('allows premium user any period', function () {
        [, $token] = limitsUser(true);

        $this->withHeaders(limitsHeaders($token))
            ->getJson('/api/v1/analytics/expenses-by-category?from=2020-01-01&to=2020-12-31')
            ->assertOk();
    });
});

describe('Advice view limits', function () {
    it('allows free user first advice of the week', function () {
        [$user, $token] = limitsUser(false);
        $advice = AiAdvice::factory()->for($user)->create();

        $this->withHeaders(limitsHeaders($token))
            ->getJson("/api/v1/advice/{$advice->id}")
            ->assertOk();
    });

    it('blocks free user second advice of the week', function () {
        [$user, $token] = limitsUser(false);

        $first = AiAdvice::factory()->for($user)->read()->create([
            'updated_at' => now(),
        ]);

        $second = AiAdvice::factory()->for($user)->create();

        $this->withHeaders(limitsHeaders($token))
            ->getJson("/api/v1/advice/{$second->id}")
            ->assertForbidden()
            ->assertJsonStructure(['message', 'upgrade_url']);
    });

    it('allows premium user unlimited advice', function () {
        [$user, $token] = limitsUser(true);

        AiAdvice::factory()->for($user)->read()->create([
            'updated_at' => now(),
        ]);

        $second = AiAdvice::factory()->for($user)->create();

        $this->withHeaders(limitsHeaders($token))
            ->getJson("/api/v1/advice/{$second->id}")
            ->assertOk();
    });
});

describe('Subscription lifecycle', function () {
    it('lifts limits after upgrading to premium', function () {
        [$user, $token] = limitsUser(false);
        Goal::factory()->for($user)->create(['status' => GoalStatus::Active]);

        $this->withHeaders(limitsHeaders($token))
            ->postJson('/api/v1/goals', [
                'name' => 'Blocked goal',
                'type' => 'large_purchase',
                'target_amount' => 500000,
            ])
            ->assertForbidden();

        $subscriptionService = app(\App\Contracts\SubscriptionServiceInterface::class);
        $subscriptionService->subscribe($user, \App\Enums\SubscriptionPlan::Monthly);
        $user->refresh();
        app('auth')->forgetGuards();

        $this->withHeaders(limitsHeaders($token))
            ->postJson('/api/v1/goals', [
                'name' => 'Allowed goal',
                'type' => 'large_purchase',
                'target_amount' => 500000,
            ])
            ->assertCreated();
    });

    it('restores limits after cancelling subscription', function () {
        [$user, $token] = limitsUser(false);

        $subscriptionService = app(\App\Contracts\SubscriptionServiceInterface::class);
        $subscriptionService->subscribe($user, \App\Enums\SubscriptionPlan::Monthly);
        $user->refresh();

        Goal::factory()->for($user)->create(['status' => GoalStatus::Active]);

        $this->withHeaders(limitsHeaders($token))
            ->postJson('/api/v1/goals', [
                'name' => 'Allowed goal',
                'type' => 'large_purchase',
                'target_amount' => 500000,
            ])
            ->assertCreated();

        $subscriptionService->cancel($user);
        $user->refresh();
        app('auth')->forgetGuards();

        $this->withHeaders(limitsHeaders($token))
            ->postJson('/api/v1/goals', [
                'name' => 'Blocked goal',
                'type' => 'large_purchase',
                'target_amount' => 500000,
            ])
            ->assertForbidden();
    });
});
