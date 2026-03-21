<?php

use App\Models\Category;
use App\Models\RecurringRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function authUser(): array
{
    $user = User::factory()->create(['phone_verified_at' => now()]);
    $token = $user->createToken('test')->plainTextToken;

    return [$user, $token];
}

function bearerHeaders(string $token): array
{
    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
}

describe('GET /api/v1/recurring-rules', function () {
    it('returns paginated rules for authenticated user', function () {
        [$user, $token] = authUser();
        $category = Category::factory()->create();

        RecurringRule::factory()->count(3)->for($user)->create([
            'category_id' => $category->id,
        ]);

        $this->withHeaders(bearerHeaders($token))
            ->getJson('/api/v1/recurring-rules')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'type', 'amount', 'interval', 'is_active', 'category']],
            ]);
    });

    it('does not return other users rules', function () {
        [$user, $token] = authUser();
        $otherUser = User::factory()->create(['phone_verified_at' => now()]);
        $category = Category::factory()->create();

        RecurringRule::factory()->for($otherUser)->create([
            'category_id' => $category->id,
        ]);

        $this->withHeaders(bearerHeaders($token))
            ->getJson('/api/v1/recurring-rules')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });
});

describe('POST /api/v1/recurring-rules', function () {
    it('creates a recurring rule', function () {
        [$user, $token] = authUser();
        $category = Category::factory()->expense()->create();

        $this->withHeaders(bearerHeaders($token))
            ->postJson('/api/v1/recurring-rules', [
                'type' => 'expense',
                'amount' => 5000,
                'category_id' => $category->id,
                'interval' => 'monthly',
                'start_date' => '2026-03-01',
            ])
            ->assertCreated()
            ->assertJsonPath('data.amount', 5000)
            ->assertJsonPath('data.interval', 'monthly')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('recurring_rules', [
            'user_id' => $user->id,
            'amount' => 5000,
            'interval' => 'monthly',
        ]);
    });

    it('validates required fields', function () {
        [$user, $token] = authUser();

        $this->withHeaders(bearerHeaders($token))
            ->postJson('/api/v1/recurring-rules', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'amount', 'category_id', 'interval', 'start_date']);
    });

    it('validates interval enum', function () {
        [$user, $token] = authUser();
        $category = Category::factory()->create();

        $this->withHeaders(bearerHeaders($token))
            ->postJson('/api/v1/recurring-rules', [
                'type' => 'expense',
                'amount' => 1000,
                'category_id' => $category->id,
                'interval' => 'biweekly',
                'start_date' => '2026-03-01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('interval');
    });
});

describe('GET /api/v1/recurring-rules/{id}', function () {
    it('returns a single rule', function () {
        [$user, $token] = authUser();
        $category = Category::factory()->create();
        $rule = RecurringRule::factory()->for($user)->create([
            'category_id' => $category->id,
        ]);

        $this->withHeaders(bearerHeaders($token))
            ->getJson("/api/v1/recurring-rules/{$rule->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $rule->id);
    });

    it('forbids accessing other users rule', function () {
        [$user, $token] = authUser();
        $otherUser = User::factory()->create(['phone_verified_at' => now()]);
        $category = Category::factory()->create();
        $rule = RecurringRule::factory()->for($otherUser)->create([
            'category_id' => $category->id,
        ]);

        $this->withHeaders(bearerHeaders($token))
            ->getJson("/api/v1/recurring-rules/{$rule->id}")
            ->assertForbidden();
    });
});

describe('PUT /api/v1/recurring-rules/{id}', function () {
    it('updates a rule', function () {
        [$user, $token] = authUser();
        $category = Category::factory()->create();
        $rule = RecurringRule::factory()->for($user)->create([
            'category_id' => $category->id,
            'amount' => 5000,
        ]);

        $this->withHeaders(bearerHeaders($token))
            ->putJson("/api/v1/recurring-rules/{$rule->id}", [
                'amount' => 7500,
                'comment' => 'ЖКХ',
            ])
            ->assertOk()
            ->assertJsonPath('data.amount', 7500)
            ->assertJsonPath('data.comment', 'ЖКХ');
    });

    it('deactivates a rule', function () {
        [$user, $token] = authUser();
        $category = Category::factory()->create();
        $rule = RecurringRule::factory()->for($user)->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $this->withHeaders(bearerHeaders($token))
            ->putJson("/api/v1/recurring-rules/{$rule->id}", [
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    });

    it('forbids updating other users rule', function () {
        [$user, $token] = authUser();
        $otherUser = User::factory()->create(['phone_verified_at' => now()]);
        $category = Category::factory()->create();
        $rule = RecurringRule::factory()->for($otherUser)->create([
            'category_id' => $category->id,
        ]);

        $this->withHeaders(bearerHeaders($token))
            ->putJson("/api/v1/recurring-rules/{$rule->id}", ['amount' => 999])
            ->assertForbidden();
    });
});

describe('DELETE /api/v1/recurring-rules/{id}', function () {
    it('deletes a rule', function () {
        [$user, $token] = authUser();
        $category = Category::factory()->create();
        $rule = RecurringRule::factory()->for($user)->create([
            'category_id' => $category->id,
        ]);

        $this->withHeaders(bearerHeaders($token))
            ->deleteJson("/api/v1/recurring-rules/{$rule->id}")
            ->assertOk();

        $this->assertDatabaseMissing('recurring_rules', ['id' => $rule->id]);
    });

    it('forbids deleting other users rule', function () {
        [$user, $token] = authUser();
        $otherUser = User::factory()->create(['phone_verified_at' => now()]);
        $category = Category::factory()->create();
        $rule = RecurringRule::factory()->for($otherUser)->create([
            'category_id' => $category->id,
        ]);

        $this->withHeaders(bearerHeaders($token))
            ->deleteJson("/api/v1/recurring-rules/{$rule->id}")
            ->assertForbidden();
    });
});
