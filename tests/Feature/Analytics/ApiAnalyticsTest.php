<?php

use App\Models\Category;
use App\Models\CpiValue;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function analyticsUser(): array
{
    $user = User::factory()->premium()->create(['phone_verified_at' => now()]);
    $token = $user->createToken('test')->plainTextToken;

    return [$user, $token];
}

function analyticsHeaders(string $token): array
{
    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
}

function seedAnalyticsCpi(int $year = 2026): void
{
    for ($i = 1; $i <= 12; $i++) {
        CpiValue::create([
            'period' => Carbon::create($year, $i, 1),
            'category_code' => 'TOTAL',
            'value' => 100.80,
            'source' => 'test',
        ]);
    }
}

describe('GET /api/v1/analytics/expenses-by-category', function () {
    it('returns expenses grouped by category', function () {
        [$user, $token] = analyticsUser();
        $food = Category::factory()->expense()->create();
        $transport = Category::factory()->expense()->create();

        Transaction::factory()->expense()->for($user)->create([
            'category_id' => $food->id,
            'amount' => 30000,
            'date' => '2026-03-10',
        ]);
        Transaction::factory()->expense()->for($user)->create([
            'category_id' => $transport->id,
            'amount' => 20000,
            'date' => '2026-03-15',
        ]);

        $this->withHeaders(analyticsHeaders($token))
            ->getJson('/api/v1/analytics/expenses-by-category?from=2026-03-01&to=2026-03-31')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['category_id', 'category_name', 'category_icon', 'category_color', 'total', 'count', 'share']],
                'period' => ['from', 'to'],
            ]);
    });

    it('returns empty data when no expenses', function () {
        [$user, $token] = analyticsUser();

        $this->withHeaders(analyticsHeaders($token))
            ->getJson('/api/v1/analytics/expenses-by-category?from=2026-03-01&to=2026-03-31')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/analytics/expenses-by-category')
            ->assertUnauthorized();
    });
});

describe('GET /api/v1/analytics/balance-dynamics', function () {
    it('returns daily balance dynamics', function () {
        [$user, $token] = analyticsUser();
        $category = Category::factory()->expense()->create();

        Transaction::factory()->income()->for($user)->create([
            'category_id' => $category->id,
            'amount' => 100000,
            'date' => '2026-03-01',
        ]);
        Transaction::factory()->expense()->for($user)->create([
            'category_id' => $category->id,
            'amount' => 40000,
            'date' => '2026-03-10',
        ]);

        $this->withHeaders(analyticsHeaders($token))
            ->getJson('/api/v1/analytics/balance-dynamics?from=2026-03-01&to=2026-03-31')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['date', 'income', 'expense', 'balance', 'cumulative_balance', 'real_cumulative_balance']],
                'period',
            ]);
    });

    it('accepts granularity parameter', function () {
        [$user, $token] = analyticsUser();
        $category = Category::factory()->expense()->create();

        Transaction::factory()->income()->for($user)->create([
            'category_id' => $category->id,
            'amount' => 50000,
            'date' => '2026-03-05',
        ]);
        Transaction::factory()->income()->for($user)->create([
            'category_id' => $category->id,
            'amount' => 50000,
            'date' => '2026-03-20',
        ]);

        $this->withHeaders(analyticsHeaders($token))
            ->getJson('/api/v1/analytics/balance-dynamics?from=2026-03-01&to=2026-03-31&granularity=month')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('validates granularity value', function () {
        [$user, $token] = analyticsUser();

        $this->withHeaders(analyticsHeaders($token))
            ->getJson('/api/v1/analytics/balance-dynamics?granularity=week')
            ->assertUnprocessable();
    });

    it('returns empty data when no transactions', function () {
        [$user, $token] = analyticsUser();

        $this->withHeaders(analyticsHeaders($token))
            ->getJson('/api/v1/analytics/balance-dynamics?from=2026-03-01&to=2026-03-31')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/analytics/balance-dynamics')
            ->assertUnauthorized();
    });
});

describe('GET /api/v1/analytics/personal-inflation', function () {
    it('returns personal inflation breakdown', function () {
        [$user, $token] = analyticsUser();
        $category = Category::factory()->expense()->create();
        seedAnalyticsCpi();

        Transaction::factory()->expense()->for($user)->create([
            'category_id' => $category->id,
            'amount' => 50000,
            'date' => '2026-06-15',
        ]);

        $this->withHeaders(analyticsHeaders($token))
            ->getJson('/api/v1/analytics/personal-inflation?from=2026-01-01&to=2026-12-31')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'personal_rate',
                    'official_rate',
                    'breakdown' => [['category_id', 'category_name', 'category_icon', 'share', 'category_cpi', 'contribution']],
                ],
                'period',
            ]);
    });

    it('returns empty breakdown when no transactions', function () {
        [$user, $token] = analyticsUser();
        seedAnalyticsCpi();

        $this->withHeaders(analyticsHeaders($token))
            ->getJson('/api/v1/analytics/personal-inflation?from=2026-01-01&to=2026-12-31')
            ->assertOk()
            ->assertJsonPath('data.breakdown', [])
            ->assertJsonStructure([
                'data' => ['personal_rate', 'official_rate', 'breakdown'],
            ]);
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/analytics/personal-inflation')
            ->assertUnauthorized();
    });
});

describe('GET /api/v1/analytics/trends', function () {
    it('returns category trends', function () {
        [$user, $token] = analyticsUser();
        $category = Category::factory()->expense()->create();

        Transaction::factory()->expense()->for($user)->create([
            'category_id' => $category->id,
            'amount' => 10000,
            'date' => '2026-02-15',
        ]);
        Transaction::factory()->expense()->for($user)->create([
            'category_id' => $category->id,
            'amount' => 15000,
            'date' => '2026-03-15',
        ]);

        $this->withHeaders(analyticsHeaders($token))
            ->getJson('/api/v1/analytics/trends?from=2026-03-01&to=2026-03-31')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [['category_id', 'category_name', 'category_icon', 'current_total', 'previous_total', 'change_percent', 'direction']],
                'period',
            ])
            ->assertJsonPath('data.0.direction', 'up')
            ->assertJsonPath('data.0.change_percent', 50);
    });

    it('returns empty data when no transactions', function () {
        [$user, $token] = analyticsUser();

        $this->withHeaders(analyticsHeaders($token))
            ->getJson('/api/v1/analytics/trends?from=2026-03-01&to=2026-03-31')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/analytics/trends')
            ->assertUnauthorized();
    });
});

describe('GET /api/v1/analytics/summary', function () {
    it('returns combined analytics data', function () {
        [$user, $token] = analyticsUser();
        $category = Category::factory()->expense()->create();
        seedAnalyticsCpi();

        Transaction::factory()->expense()->for($user)->create([
            'category_id' => $category->id,
            'amount' => 25000,
            'date' => '2026-03-15',
        ]);

        $this->withHeaders(analyticsHeaders($token))
            ->getJson('/api/v1/analytics/summary?from=2026-03-01&to=2026-03-31')
            ->assertOk()
            ->assertJsonStructure([
                'expenses_by_category',
                'balance_dynamics',
                'personal_inflation' => ['personal_rate', 'official_rate', 'breakdown'],
                'trends',
                'period' => ['from', 'to'],
            ]);
    });

    it('defaults to current month when no period given', function () {
        [$user, $token] = analyticsUser();

        $response = $this->withHeaders(analyticsHeaders($token))
            ->getJson('/api/v1/analytics/summary')
            ->assertOk();

        $period = $response->json('period');
        expect($period['from'])->toBe(now()->startOfMonth()->toDateString());
        expect($period['to'])->toBe(now()->endOfMonth()->toDateString());
    });

    it('validates date format', function () {
        [$user, $token] = analyticsUser();

        $this->withHeaders(analyticsHeaders($token))
            ->getJson('/api/v1/analytics/summary?from=not-a-date')
            ->assertUnprocessable();
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/analytics/summary')
            ->assertUnauthorized();
    });
});
