<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function authenticatedUser(): array
{
    $user = User::factory()->create(['phone_verified_at' => now()]);
    $token = $user->createToken('test')->plainTextToken;

    return [$user, $token];
}

function authHeaders(string $token): array
{
    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
}

describe('GET /api/v1/transactions', function () {
    it('returns paginated transactions for authenticated user', function () {
        [$user, $token] = authenticatedUser();
        $category = Category::factory()->expense()->create();

        Transaction::factory()->count(3)->for($user)->create([
            'category_id' => $category->id,
            'date' => now(),
        ]);

        $this->withHeaders(authHeaders($token))
            ->getJson('/api/v1/transactions')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'type', 'amount', 'date', 'category']],
                'meta',
                'links',
            ]);
    });

    it('does not return other users transactions', function () {
        [$user, $token] = authenticatedUser();
        $otherUser = User::factory()->create(['phone_verified_at' => now()]);
        $category = Category::factory()->create();

        Transaction::factory()->for($otherUser)->create([
            'category_id' => $category->id,
            'date' => now(),
        ]);

        $this->withHeaders(authHeaders($token))
            ->getJson('/api/v1/transactions')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('filters by type', function () {
        [$user, $token] = authenticatedUser();
        $category = Category::factory()->create();

        Transaction::factory()->income()->for($user)->create([
            'category_id' => $category->id,
            'date' => now(),
        ]);
        Transaction::factory()->expense()->for($user)->create([
            'category_id' => $category->id,
            'date' => now(),
        ]);

        $this->withHeaders(authHeaders($token))
            ->getJson('/api/v1/transactions?type=income')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'income');
    });

    it('filters by period', function () {
        [$user, $token] = authenticatedUser();
        $category = Category::factory()->create();

        Transaction::factory()->for($user)->create([
            'category_id' => $category->id,
            'date' => '2026-01-15',
        ]);
        Transaction::factory()->for($user)->create([
            'category_id' => $category->id,
            'date' => '2026-03-15',
        ]);

        $this->withHeaders(authHeaders($token))
            ->getJson('/api/v1/transactions?from=2026-01-01&to=2026-01-31')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('filters by category', function () {
        [$user, $token] = authenticatedUser();
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();

        Transaction::factory()->for($user)->create([
            'category_id' => $cat1->id,
            'date' => now(),
        ]);
        Transaction::factory()->for($user)->create([
            'category_id' => $cat2->id,
            'date' => now(),
        ]);

        $this->withHeaders(authHeaders($token))
            ->getJson("/api/v1/transactions?category_id={$cat1->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('searches by comment', function () {
        [$user, $token] = authenticatedUser();
        $category = Category::factory()->create();

        Transaction::factory()->for($user)->create([
            'category_id' => $category->id,
            'date' => now(),
            'comment' => 'Обед в ресторане',
        ]);
        Transaction::factory()->for($user)->create([
            'category_id' => $category->id,
            'date' => now(),
            'comment' => 'Зарплата',
        ]);

        $this->withHeaders(authHeaders($token))
            ->getJson('/api/v1/transactions?search=ресторан')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/transactions')
            ->assertUnauthorized();
    });
});

describe('POST /api/v1/transactions', function () {
    it('creates a transaction', function () {
        [$user, $token] = authenticatedUser();
        $category = Category::factory()->expense()->create();

        $this->withHeaders(authHeaders($token))
            ->postJson('/api/v1/transactions', [
                'type' => 'expense',
                'amount' => 15000,
                'category_id' => $category->id,
                'date' => '2026-02-08',
            ])
            ->assertCreated()
            ->assertJsonPath('data.amount', 15000)
            ->assertJsonPath('data.type', 'expense')
            ->assertJsonPath('data.category.id', $category->id);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => 15000,
            'type' => 'expense',
        ]);
    });

    it('validates required fields', function () {
        [$user, $token] = authenticatedUser();

        $this->withHeaders(authHeaders($token))
            ->postJson('/api/v1/transactions', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'amount', 'category_id', 'date']);
    });

    it('validates amount is positive', function () {
        [$user, $token] = authenticatedUser();
        $category = Category::factory()->create();

        $this->withHeaders(authHeaders($token))
            ->postJson('/api/v1/transactions', [
                'type' => 'expense',
                'amount' => 0,
                'category_id' => $category->id,
                'date' => '2026-02-08',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
    });

    it('validates category exists', function () {
        [$user, $token] = authenticatedUser();

        $this->withHeaders(authHeaders($token))
            ->postJson('/api/v1/transactions', [
                'type' => 'expense',
                'amount' => 1000,
                'category_id' => 99999,
                'date' => '2026-02-08',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    });

    it('validates type enum', function () {
        [$user, $token] = authenticatedUser();
        $category = Category::factory()->create();

        $this->withHeaders(authHeaders($token))
            ->postJson('/api/v1/transactions', [
                'type' => 'invalid',
                'amount' => 1000,
                'category_id' => $category->id,
                'date' => '2026-02-08',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    });
});

describe('GET /api/v1/transactions/{id}', function () {
    it('returns a single transaction', function () {
        [$user, $token] = authenticatedUser();
        $category = Category::factory()->create();
        $transaction = Transaction::factory()->for($user)->create([
            'category_id' => $category->id,
        ]);

        $this->withHeaders(authHeaders($token))
            ->getJson("/api/v1/transactions/{$transaction->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $transaction->id);
    });

    it('forbids accessing other users transaction', function () {
        [$user, $token] = authenticatedUser();
        $otherUser = User::factory()->create(['phone_verified_at' => now()]);
        $category = Category::factory()->create();
        $transaction = Transaction::factory()->for($otherUser)->create([
            'category_id' => $category->id,
        ]);

        $this->withHeaders(authHeaders($token))
            ->getJson("/api/v1/transactions/{$transaction->id}")
            ->assertForbidden();
    });
});

describe('PUT /api/v1/transactions/{id}', function () {
    it('updates a transaction', function () {
        [$user, $token] = authenticatedUser();
        $category = Category::factory()->create();
        $transaction = Transaction::factory()->for($user)->create([
            'category_id' => $category->id,
            'amount' => 1000,
        ]);

        $this->withHeaders(authHeaders($token))
            ->putJson("/api/v1/transactions/{$transaction->id}", [
                'amount' => 2500,
                'comment' => 'обед',
            ])
            ->assertOk()
            ->assertJsonPath('data.amount', 2500)
            ->assertJsonPath('data.comment', 'обед');
    });

    it('forbids updating other users transaction', function () {
        [$user, $token] = authenticatedUser();
        $otherUser = User::factory()->create(['phone_verified_at' => now()]);
        $category = Category::factory()->create();
        $transaction = Transaction::factory()->for($otherUser)->create([
            'category_id' => $category->id,
        ]);

        $this->withHeaders(authHeaders($token))
            ->putJson("/api/v1/transactions/{$transaction->id}", ['amount' => 999])
            ->assertForbidden();
    });
});

describe('DELETE /api/v1/transactions/{id}', function () {
    it('deletes a transaction', function () {
        [$user, $token] = authenticatedUser();
        $category = Category::factory()->create();
        $transaction = Transaction::factory()->for($user)->create([
            'category_id' => $category->id,
        ]);

        $this->withHeaders(authHeaders($token))
            ->deleteJson("/api/v1/transactions/{$transaction->id}")
            ->assertOk()
            ->assertJsonPath('message', __('transactions.deleted'));

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    });

    it('forbids deleting other users transaction', function () {
        [$user, $token] = authenticatedUser();
        $otherUser = User::factory()->create(['phone_verified_at' => now()]);
        $category = Category::factory()->create();
        $transaction = Transaction::factory()->for($otherUser)->create([
            'category_id' => $category->id,
        ]);

        $this->withHeaders(authHeaders($token))
            ->deleteJson("/api/v1/transactions/{$transaction->id}")
            ->assertForbidden();
    });
});

describe('GET /api/v1/transactions/summary', function () {
    it('returns income expense and balance', function () {
        [$user, $token] = authenticatedUser();
        $category = Category::factory()->create();

        Transaction::factory()->income()->for($user)->create([
            'category_id' => $category->id,
            'amount' => 100000,
            'date' => '2026-02-10',
        ]);
        Transaction::factory()->expense()->for($user)->create([
            'category_id' => $category->id,
            'amount' => 30000,
            'date' => '2026-02-15',
        ]);

        $this->withHeaders(authHeaders($token))
            ->getJson('/api/v1/transactions/summary?from=2026-02-01&to=2026-02-28')
            ->assertOk()
            ->assertJson([
                'income' => 100000,
                'expense' => 30000,
                'balance' => 70000,
            ]);
    });

    it('returns zeros when no transactions', function () {
        [$user, $token] = authenticatedUser();

        $this->withHeaders(authHeaders($token))
            ->getJson('/api/v1/transactions/summary?from=2026-01-01&to=2026-01-31')
            ->assertOk()
            ->assertJson([
                'income' => 0,
                'expense' => 0,
                'balance' => 0,
            ]);
    });
});
