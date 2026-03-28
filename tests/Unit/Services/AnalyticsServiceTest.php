<?php

use App\Models\Category;
use App\Models\CpiCategory;
use App\Models\CpiValue;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(AnalyticsService::class);
    $this->user = User::factory()->create();
});

function createExpenseCategory(array $attrs = []): Category
{
    return Category::factory()->expense()->create($attrs);
}

function seedTotalCpi(array $monthlyValues, int $year = 2026): void
{
    foreach ($monthlyValues as $i => $value) {
        CpiValue::create([
            'period' => Carbon::create($year, $i + 1, 1),
            'category_code' => 'TOTAL',
            'value' => $value,
            'source' => 'test',
        ]);
    }
}

describe('getExpensesByCategory', function () {
    it('returns empty array when no transactions', function () {
        $result = $this->service->getExpensesByCategory(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result)->toBe([]);
    });

    it('groups expenses by category with shares', function () {
        $food = createExpenseCategory(['name' => ['ru' => 'Продукты', 'en' => 'Food']]);
        $transport = createExpenseCategory(['name' => ['ru' => 'Транспорт', 'en' => 'Transport']]);

        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $food->id,
            'amount' => 80000,
            'date' => '2026-03-10',
        ]);
        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $transport->id,
            'amount' => 20000,
            'date' => '2026-03-15',
        ]);

        $result = $this->service->getExpensesByCategory(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result)->toHaveCount(2);
        expect($result[0]['category_id'])->toBe($food->id);
        expect($result[0]['total'])->toBe(80000);
        expect($result[0]['share'])->toBe(0.8);
        expect($result[0]['count'])->toBe(1);
        expect($result[1]['category_id'])->toBe($transport->id);
        expect($result[1]['total'])->toBe(20000);
        expect($result[1]['share'])->toBe(0.2);
    });

    it('includes category metadata', function () {
        $food = createExpenseCategory([
            'name' => ['ru' => 'Продукты', 'en' => 'Food'],
            'icon' => 'cart',
            'color' => '#ff0000',
        ]);

        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $food->id,
            'amount' => 10000,
            'date' => '2026-03-10',
        ]);

        $result = $this->service->getExpensesByCategory(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result[0]['category_name'])->toBe(['ru' => 'Продукты', 'en' => 'Food']);
        expect($result[0]['category_icon'])->toBe('cart');
        expect($result[0]['category_color'])->toBe('#ff0000');
    });

    it('excludes income transactions', function () {
        $category = createExpenseCategory();

        Transaction::factory()->income()->for($this->user)->create([
            'category_id' => $category->id,
            'amount' => 100000,
            'date' => '2026-03-10',
        ]);

        $result = $this->service->getExpensesByCategory(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result)->toBe([]);
    });

    it('excludes transactions outside period', function () {
        $category = createExpenseCategory();

        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $category->id,
            'amount' => 10000,
            'date' => '2026-02-15',
        ]);

        $result = $this->service->getExpensesByCategory(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result)->toBe([]);
    });

    it('aggregates multiple transactions in same category', function () {
        $category = createExpenseCategory();

        Transaction::factory()->expense()->for($this->user)->count(3)->create([
            'category_id' => $category->id,
            'amount' => 10000,
            'date' => '2026-03-10',
        ]);

        $result = $this->service->getExpensesByCategory(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result)->toHaveCount(1);
        expect($result[0]['total'])->toBe(30000);
        expect($result[0]['count'])->toBe(3);
        expect($result[0]['share'])->toBe(1.0);
    });
});

describe('getBalanceDynamics', function () {
    it('returns empty array when no transactions', function () {
        $result = $this->service->getBalanceDynamics(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result)->toBe([]);
    });

    it('returns daily breakdown with cumulative balance', function () {
        $category = createExpenseCategory();

        Transaction::factory()->income()->for($this->user)->create([
            'category_id' => $category->id,
            'amount' => 100000,
            'date' => '2026-03-01',
        ]);
        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $category->id,
            'amount' => 30000,
            'date' => '2026-03-05',
        ]);
        Transaction::factory()->income()->for($this->user)->create([
            'category_id' => $category->id,
            'amount' => 50000,
            'date' => '2026-03-10',
        ]);

        $result = $this->service->getBalanceDynamics(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result)->toHaveCount(3);

        expect($result[0]['date'])->toBe('2026-03-01');
        expect($result[0]['income'])->toBe(100000);
        expect($result[0]['expense'])->toBe(0);
        expect($result[0]['balance'])->toBe(100000);
        expect($result[0]['cumulative_balance'])->toBe(100000);

        expect($result[1]['date'])->toBe('2026-03-05');
        expect($result[1]['income'])->toBe(0);
        expect($result[1]['expense'])->toBe(30000);
        expect($result[1]['balance'])->toBe(-30000);
        expect($result[1]['cumulative_balance'])->toBe(70000);

        expect($result[2]['date'])->toBe('2026-03-10');
        expect($result[2]['cumulative_balance'])->toBe(120000);
    });

    it('calculates real cumulative balance adjusted for inflation', function () {
        $category = createExpenseCategory();

        seedTotalCpi([
            100.80, 100.80, 100.80, 100.80, 100.80, 100.80,
            100.80, 100.80, 100.80, 100.80, 100.80, 100.80,
        ]);

        Transaction::factory()->income()->for($this->user)->create([
            'category_id' => $category->id,
            'amount' => 10000000,
            'date' => '2026-01-15',
        ]);

        $result = $this->service->getBalanceDynamics(
            $this->user->id,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31'),
        );

        expect($result)->toHaveCount(1);
        expect($result[0]['cumulative_balance'])->toBe(10000000);
        expect($result[0]['real_cumulative_balance'])->toBeLessThan(10000000);
    });
});

describe('getPersonalInflationBreakdown', function () {
    it('returns rates with empty breakdown when no transactions', function () {
        seedTotalCpi([
            100.50, 100.50, 100.50, 100.50, 100.50, 100.50,
            100.50, 100.50, 100.50, 100.50, 100.50, 100.50,
        ]);

        $result = $this->service->getPersonalInflationBreakdown(
            $this->user->id,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31'),
        );

        expect($result)->toHaveKeys(['personal_rate', 'official_rate', 'breakdown']);
        expect($result['breakdown'])->toBe([]);
        expect($result['personal_rate'])->toBeFloat();
        expect($result['official_rate'])->toBeFloat();
    });

    it('returns breakdown with category shares and CPI', function () {
        $food = createExpenseCategory(['name' => ['ru' => 'Продукты', 'en' => 'Food']]);
        $transport = createExpenseCategory(['name' => ['ru' => 'Транспорт', 'en' => 'Transport']]);

        CpiCategory::create([
            'code' => 'FOOD',
            'name' => 'Продовольственные товары',
            'mapping_to_app_category_id' => $food->id,
        ]);

        for ($i = 1; $i <= 12; $i++) {
            CpiValue::create([
                'period' => Carbon::create(2026, $i, 1),
                'category_code' => 'FOOD',
                'value' => 101.50,
                'source' => 'test',
            ]);
            CpiValue::create([
                'period' => Carbon::create(2026, $i, 1),
                'category_code' => 'TOTAL',
                'value' => 100.80,
                'source' => 'test',
            ]);
        }

        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $food->id,
            'amount' => 60000,
            'date' => '2026-06-15',
        ]);
        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $transport->id,
            'amount' => 40000,
            'date' => '2026-06-15',
        ]);

        $result = $this->service->getPersonalInflationBreakdown(
            $this->user->id,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31'),
        );

        expect($result['breakdown'])->toHaveCount(2);

        $foodBreakdown = collect($result['breakdown'])->firstWhere('category_id', $food->id);
        $transportBreakdown = collect($result['breakdown'])->firstWhere('category_id', $transport->id);

        expect($foodBreakdown['share'])->toBe(0.6);
        expect($transportBreakdown['share'])->toBe(0.4);
        expect($foodBreakdown['category_cpi'])->toBeGreaterThan(0);
        expect($foodBreakdown['contribution'])->toBeGreaterThan(0);
    });

    it('uses official CPI for unmapped categories', function () {
        $category = createExpenseCategory();

        seedTotalCpi([
            100.80, 100.80, 100.80, 100.80, 100.80, 100.80,
            100.80, 100.80, 100.80, 100.80, 100.80, 100.80,
        ]);

        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $category->id,
            'amount' => 50000,
            'date' => '2026-06-15',
        ]);

        $result = $this->service->getPersonalInflationBreakdown(
            $this->user->id,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31'),
        );

        expect($result['breakdown'])->toHaveCount(1);
        expect($result['breakdown'][0]['share'])->toBe(1.0);
        expect($result['breakdown'][0]['category_cpi'])->toBe(round($result['official_rate'], 4));
    });
});

describe('getTrends', function () {
    it('returns empty array when no transactions in either period', function () {
        $result = $this->service->getTrends(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result)->toBe([]);
    });

    it('detects upward trend', function () {
        $category = createExpenseCategory();

        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $category->id,
            'amount' => 10000,
            'date' => '2026-02-15',
        ]);
        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $category->id,
            'amount' => 15000,
            'date' => '2026-03-15',
        ]);

        $result = $this->service->getTrends(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result)->toHaveCount(1);
        expect($result[0]['current_total'])->toBe(15000);
        expect($result[0]['previous_total'])->toBe(10000);
        expect($result[0]['direction'])->toBe('up');
        expect($result[0]['change_percent'])->toBe(50.0);
    });

    it('detects downward trend', function () {
        $category = createExpenseCategory();

        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $category->id,
            'amount' => 20000,
            'date' => '2026-02-15',
        ]);
        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $category->id,
            'amount' => 10000,
            'date' => '2026-03-15',
        ]);

        $result = $this->service->getTrends(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result[0]['direction'])->toBe('down');
        expect($result[0]['change_percent'])->toBe(-50.0);
    });

    it('marks new category with no previous data', function () {
        $category = createExpenseCategory();

        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $category->id,
            'amount' => 10000,
            'date' => '2026-03-15',
        ]);

        $result = $this->service->getTrends(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result[0]['direction'])->toBe('new');
        expect($result[0]['change_percent'])->toBeNull();
        expect($result[0]['previous_total'])->toBe(0);
    });

    it('marks stable when amounts are equal', function () {
        $category = createExpenseCategory();

        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $category->id,
            'amount' => 10000,
            'date' => '2026-02-15',
        ]);
        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $category->id,
            'amount' => 10000,
            'date' => '2026-03-15',
        ]);

        $result = $this->service->getTrends(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result[0]['direction'])->toBe('stable');
        expect($result[0]['change_percent'])->toBe(0.0);
    });

    it('sorts by current total descending', function () {
        $cat1 = createExpenseCategory();
        $cat2 = createExpenseCategory();

        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $cat1->id,
            'amount' => 5000,
            'date' => '2026-03-10',
        ]);
        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $cat2->id,
            'amount' => 25000,
            'date' => '2026-03-10',
        ]);

        $result = $this->service->getTrends(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result[0]['current_total'])->toBe(25000);
        expect($result[1]['current_total'])->toBe(5000);
    });

    it('includes categories only present in previous period', function () {
        $oldCategory = createExpenseCategory();
        $newCategory = createExpenseCategory();

        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $oldCategory->id,
            'amount' => 15000,
            'date' => '2026-02-15',
        ]);
        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $newCategory->id,
            'amount' => 20000,
            'date' => '2026-03-15',
        ]);

        $result = $this->service->getTrends(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($result)->toHaveCount(2);

        $oldTrend = collect($result)->firstWhere('category_id', $oldCategory->id);
        expect($oldTrend['current_total'])->toBe(0);
        expect($oldTrend['previous_total'])->toBe(15000);
        expect($oldTrend['direction'])->toBe('down');
    });
});
