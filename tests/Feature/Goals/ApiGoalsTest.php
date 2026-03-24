<?php

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function authenticatedGoalUser(): array
{
    $user = User::factory()->create(['phone_verified_at' => now()]);
    $token = $user->createToken('test')->plainTextToken;

    return [$user, $token];
}

function goalAuthHeaders(string $token): array
{
    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
}

describe('GET /api/v1/goals', function () {
    it('returns goals for authenticated user', function () {
        [$user, $token] = authenticatedGoalUser();

        Goal::factory()->count(3)->for($user)->create();

        $this->withHeaders(goalAuthHeaders($token))
            ->getJson('/api/v1/goals')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id', 'name', 'type', 'status', 'target_amount',
                    'current_amount', 'progress_percent', 'remaining_amount',
                    'monthly_payment', 'monthly_payment_with_inflation',
                ]],
            ]);
    });

    it('does not return other users goals', function () {
        [$user, $token] = authenticatedGoalUser();
        $otherUser = User::factory()->create(['phone_verified_at' => now()]);

        Goal::factory()->for($otherUser)->create();

        $this->withHeaders(goalAuthHeaders($token))
            ->getJson('/api/v1/goals')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/goals')
            ->assertUnauthorized();
    });
});

describe('POST /api/v1/goals', function () {
    it('creates a goal with initial amount', function () {
        [$user, $token] = authenticatedGoalUser();

        $this->withHeaders(goalAuthHeaders($token))
            ->postJson('/api/v1/goals', [
                'name' => 'Отпуск',
                'type' => 'travel',
                'target_amount' => 30000000,
                'initial_amount' => 500000,
                'target_date' => '2027-06-01',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Отпуск')
            ->assertJsonPath('data.type', 'travel')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.target_amount', 30000000)
            ->assertJsonPath('data.current_amount', 500000);

        $this->assertDatabaseHas('goals', [
            'user_id' => $user->id,
            'name' => 'Отпуск',
            'target_amount' => 30000000,
            'current_amount' => 500000,
        ]);

        $this->assertDatabaseHas('goal_contributions', [
            'amount' => 500000,
        ]);
    });

    it('creates a goal without initial amount', function () {
        [$user, $token] = authenticatedGoalUser();

        $this->withHeaders(goalAuthHeaders($token))
            ->postJson('/api/v1/goals', [
                'name' => 'Подушка безопасности',
                'type' => 'safety_net',
                'target_amount' => 50000000,
            ])
            ->assertCreated()
            ->assertJsonPath('data.current_amount', 0);

        $this->assertDatabaseCount('goal_contributions', 0);
    });

    it('validates required fields', function () {
        [$user, $token] = authenticatedGoalUser();

        $this->withHeaders(goalAuthHeaders($token))
            ->postJson('/api/v1/goals', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'type', 'target_amount']);
    });

    it('validates type enum', function () {
        [$user, $token] = authenticatedGoalUser();

        $this->withHeaders(goalAuthHeaders($token))
            ->postJson('/api/v1/goals', [
                'name' => 'Тест',
                'type' => 'invalid_type',
                'target_amount' => 100000,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    });

    it('validates target_date is after today', function () {
        [$user, $token] = authenticatedGoalUser();

        $this->withHeaders(goalAuthHeaders($token))
            ->postJson('/api/v1/goals', [
                'name' => 'Тест',
                'type' => 'travel',
                'target_amount' => 100000,
                'target_date' => '2020-01-01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('target_date');
    });
});

describe('GET /api/v1/goals/{id}', function () {
    it('returns goal with contributions', function () {
        [$user, $token] = authenticatedGoalUser();
        $goal = Goal::factory()->for($user)->create();
        $goal->contributions()->create([
            'amount' => 10000,
            'date' => now()->toDateString(),
        ]);

        $this->withHeaders(goalAuthHeaders($token))
            ->getJson("/api/v1/goals/{$goal->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $goal->id)
            ->assertJsonCount(1, 'data.contributions');
    });

    it('forbids accessing other users goal', function () {
        [$user, $token] = authenticatedGoalUser();
        $otherUser = User::factory()->create(['phone_verified_at' => now()]);
        $goal = Goal::factory()->for($otherUser)->create();

        $this->withHeaders(goalAuthHeaders($token))
            ->getJson("/api/v1/goals/{$goal->id}")
            ->assertForbidden();
    });
});

describe('PUT /api/v1/goals/{id}', function () {
    it('updates a goal', function () {
        [$user, $token] = authenticatedGoalUser();
        $goal = Goal::factory()->for($user)->create(['name' => 'Старое название']);

        $this->withHeaders(goalAuthHeaders($token))
            ->putJson("/api/v1/goals/{$goal->id}", [
                'name' => 'Новое название',
                'target_amount' => 50000000,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Новое название')
            ->assertJsonPath('data.target_amount', 50000000);
    });

    it('forbids updating other users goal', function () {
        [$user, $token] = authenticatedGoalUser();
        $otherUser = User::factory()->create(['phone_verified_at' => now()]);
        $goal = Goal::factory()->for($otherUser)->create();

        $this->withHeaders(goalAuthHeaders($token))
            ->putJson("/api/v1/goals/{$goal->id}", ['name' => 'Hack'])
            ->assertForbidden();
    });
});

describe('DELETE /api/v1/goals/{id}', function () {
    it('deletes a goal', function () {
        [$user, $token] = authenticatedGoalUser();
        $goal = Goal::factory()->for($user)->create();

        $this->withHeaders(goalAuthHeaders($token))
            ->deleteJson("/api/v1/goals/{$goal->id}")
            ->assertOk()
            ->assertJsonPath('message', __('goals.deleted'));

        $this->assertDatabaseMissing('goals', ['id' => $goal->id]);
    });

    it('forbids deleting other users goal', function () {
        [$user, $token] = authenticatedGoalUser();
        $otherUser = User::factory()->create(['phone_verified_at' => now()]);
        $goal = Goal::factory()->for($otherUser)->create();

        $this->withHeaders(goalAuthHeaders($token))
            ->deleteJson("/api/v1/goals/{$goal->id}")
            ->assertForbidden();
    });
});

describe('POST /api/v1/goals/{id}/contribute', function () {
    it('adds a contribution and updates current amount', function () {
        [$user, $token] = authenticatedGoalUser();
        $goal = Goal::factory()->for($user)->create([
            'target_amount' => 1000000,
            'current_amount' => 0,
        ]);

        $this->withHeaders(goalAuthHeaders($token))
            ->postJson("/api/v1/goals/{$goal->id}/contribute", [
                'amount' => 50000,
            ])
            ->assertCreated()
            ->assertJsonPath('data.amount', 50000)
            ->assertJsonPath('data.goal_id', $goal->id);

        $goal->refresh();
        expect($goal->current_amount)->toBe(50000);
    });

    it('achieves goal when target reached', function () {
        [$user, $token] = authenticatedGoalUser();
        $goal = Goal::factory()->for($user)->create([
            'target_amount' => 100000,
            'current_amount' => 90000,
        ]);

        $this->withHeaders(goalAuthHeaders($token))
            ->postJson("/api/v1/goals/{$goal->id}/contribute", [
                'amount' => 10000,
            ])
            ->assertCreated();

        $goal->refresh();
        expect($goal->current_amount)->toBe(100000);
        expect($goal->status)->toBe(GoalStatus::Achieved);
    });

    it('achieves goal when target exceeded', function () {
        [$user, $token] = authenticatedGoalUser();
        $goal = Goal::factory()->for($user)->create([
            'target_amount' => 100000,
            'current_amount' => 95000,
        ]);

        $this->withHeaders(goalAuthHeaders($token))
            ->postJson("/api/v1/goals/{$goal->id}/contribute", [
                'amount' => 10000,
            ])
            ->assertCreated();

        $goal->refresh();
        expect($goal->current_amount)->toBe(105000);
        expect($goal->status)->toBe(GoalStatus::Achieved);
    });

    it('rejects contribution to achieved goal', function () {
        [$user, $token] = authenticatedGoalUser();
        $goal = Goal::factory()->achieved()->for($user)->create();

        $this->withHeaders(goalAuthHeaders($token))
            ->postJson("/api/v1/goals/{$goal->id}/contribute", [
                'amount' => 10000,
            ])
            ->assertStatus(422);
    });

    it('validates amount is required and positive', function () {
        [$user, $token] = authenticatedGoalUser();
        $goal = Goal::factory()->for($user)->create();

        $this->withHeaders(goalAuthHeaders($token))
            ->postJson("/api/v1/goals/{$goal->id}/contribute", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->withHeaders(goalAuthHeaders($token))
            ->postJson("/api/v1/goals/{$goal->id}/contribute", ['amount' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
    });
});

describe('GET /api/v1/goals/{id}/scenarios', function () {
    it('returns three scenarios', function () {
        [$user, $token] = authenticatedGoalUser();
        $goal = Goal::factory()->for($user)->create([
            'target_amount' => 5000000,
            'current_amount' => 1000000,
            'target_date' => now()->addMonths(12),
        ]);

        $this->withHeaders(goalAuthHeaders($token))
            ->getJson("/api/v1/goals/{$goal->id}/scenarios")
            ->assertOk()
            ->assertJsonStructure([
                'optimistic' => ['inflation', 'monthly_payment', 'completion_date'],
                'baseline' => ['inflation', 'monthly_payment', 'completion_date'],
                'pessimistic' => ['inflation', 'monthly_payment', 'completion_date'],
            ]);
    });

    it('pessimistic payment is highest', function () {
        [$user, $token] = authenticatedGoalUser();
        $goal = Goal::factory()->for($user)->create([
            'target_amount' => 5000000,
            'current_amount' => 0,
            'target_date' => now()->addMonths(12),
        ]);

        $response = $this->withHeaders(goalAuthHeaders($token))
            ->getJson("/api/v1/goals/{$goal->id}/scenarios")
            ->assertOk()
            ->json();

        expect($response['pessimistic']['monthly_payment'])
            ->toBeGreaterThan($response['optimistic']['monthly_payment']);
    });
});

describe('GET /api/v1/goals/{id}/what-if', function () {
    it('returns what-if calculation', function () {
        [$user, $token] = authenticatedGoalUser();
        $goal = Goal::factory()->for($user)->create([
            'target_amount' => 5000000,
            'current_amount' => 1000000,
            'started_at' => now()->subMonths(3),
            'target_date' => now()->addMonths(12),
        ]);

        $this->withHeaders(goalAuthHeaders($token))
            ->getJson("/api/v1/goals/{$goal->id}/what-if?additional_monthly=50000")
            ->assertOk()
            ->assertJsonStructure([
                'current_monthly',
                'new_monthly',
                'current_completion',
                'new_completion',
                'months_saved',
            ]);
    });

    it('new monthly is higher than current', function () {
        [$user, $token] = authenticatedGoalUser();
        $goal = Goal::factory()->for($user)->create([
            'target_amount' => 5000000,
            'current_amount' => 1000000,
            'started_at' => now()->subMonths(3),
            'target_date' => now()->addMonths(12),
        ]);

        $response = $this->withHeaders(goalAuthHeaders($token))
            ->getJson("/api/v1/goals/{$goal->id}/what-if?additional_monthly=50000")
            ->assertOk()
            ->json();

        expect($response['new_monthly'])
            ->toBe($response['current_monthly'] + 50000);
    });
});
