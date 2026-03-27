<?php

use App\Models\Category;
use App\Models\CpiCategory;
use App\Models\CpiValue;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InflationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(InflationService::class);
    $this->user = User::factory()->create();
});

function seedMonthlyTotalCpi(array $monthlyValues, int $startYear = 2025): void
{
    foreach ($monthlyValues as $i => $value) {
        CpiValue::create([
            'period' => Carbon::create($startYear, $i + 1, 1),
            'category_code' => 'TOTAL',
            'value' => $value,
            'source' => 'test',
        ]);
    }
}

function createCategory(): Category
{
    return Category::factory()->expense()->system()->create();
}

describe('getCurrentCpi', function () {
    it('returns default when no data exists', function () {
        $result = $this->service->getCurrentCpi();

        expect($result)->toBe(0.095);
    });

    it('calculates annual rate from 12 months of data', function () {
        seedMonthlyTotalCpi([
            100.50, 100.50, 100.50, 100.50, 100.50, 100.50,
            100.50, 100.50, 100.50, 100.50, 100.50, 100.50,
        ]);

        $result = $this->service->getCurrentCpi();

        // 1.005^12 - 1 ≈ 0.0617
        expect($result)->toBeGreaterThan(0.05);
        expect($result)->toBeLessThan(0.07);
    });

    it('uses trailing 12 months from most recent data', function () {
        seedMonthlyTotalCpi([
            100.80, 100.80, 100.80, 100.80, 100.80, 100.80,
            100.80, 100.80, 100.80, 100.80, 100.80, 100.80,
        ], 2024);

        seedMonthlyTotalCpi([
            101.00, 101.00, 101.00, 101.00, 101.00, 101.00,
            101.00, 101.00, 101.00, 101.00, 101.00, 101.00,
        ], 2025);

        $result = $this->service->getCurrentCpi();

        // Should use 2025 data: 1.01^12 - 1 ≈ 0.1268
        expect($result)->toBeGreaterThan(0.12);
        expect($result)->toBeLessThan(0.13);
    });
});

describe('getCpiForPeriod', function () {
    it('returns default when no data for period', function () {
        $result = $this->service->getCpiForPeriod(
            Carbon::create(2020, 1, 1),
            Carbon::create(2020, 12, 1),
        );

        expect($result)->toBe(0.095);
    });

    it('annualizes correctly for partial year', function () {
        // 6 months of 1% monthly inflation
        for ($i = 1; $i <= 6; $i++) {
            CpiValue::create([
                'period' => Carbon::create(2025, $i, 1),
                'category_code' => 'TOTAL',
                'value' => 101.00,
                'source' => 'test',
            ]);
        }

        $result = $this->service->getCpiForPeriod(
            Carbon::create(2025, 1, 1),
            Carbon::create(2025, 6, 1),
        );

        // 1.01^6 annualized to 12 months = 1.01^12 - 1 ≈ 0.1268
        expect($result)->toBeGreaterThan(0.12);
        expect($result)->toBeLessThan(0.13);
    });

    it('supports category-specific queries', function () {
        for ($i = 1; $i <= 12; $i++) {
            CpiValue::create([
                'period' => Carbon::create(2025, $i, 1),
                'category_code' => 'FOOD',
                'value' => 101.20,
                'source' => 'test',
            ]);
        }

        $result = $this->service->getCpiForPeriod(
            Carbon::create(2025, 1, 1),
            Carbon::create(2025, 12, 1),
            'FOOD',
        );

        // 1.012^12 - 1 ≈ 0.1539
        expect($result)->toBeGreaterThan(0.14);
        expect($result)->toBeLessThan(0.16);
    });
});

describe('getCpiByCategory', function () {
    it('returns null when no data', function () {
        $result = $this->service->getCpiByCategory('TOTAL', Carbon::create(2025, 1, 1));

        expect($result)->toBeNull();
    });

    it('returns exact monthly value', function () {
        CpiValue::create([
            'period' => Carbon::create(2025, 3, 1),
            'category_code' => 'FOOD',
            'value' => 100.84,
            'source' => 'test',
        ]);

        $result = $this->service->getCpiByCategory('FOOD', Carbon::create(2025, 3, 1));

        expect($result)->toBe(100.84);
    });
});

describe('calculateRealValue', function () {
    it('returns nominal when no CPI data', function () {
        $result = $this->service->calculateRealValue(50000000, Carbon::create(2025, 1, 1), Carbon::create(2025, 12, 1));

        expect($result)->toBe(50000000);
    });

    it('matches research.md example', function () {
        // research.md: 500_000 ₽, CPI start=100, end=109.5 (9.5% annual)
        // Real = 500_000 / 1.095 = 456_621 ₽
        // Using monthly: 12 months of ~100.76 each → compound ≈ 1.095
        for ($i = 1; $i <= 12; $i++) {
            CpiValue::create([
                'period' => Carbon::create(2025, $i, 1),
                'category_code' => 'TOTAL',
                'value' => 100.76,
                'source' => 'test',
            ]);
        }

        $result = $this->service->calculateRealValue(
            50000000, // 500_000 ₽ in kopecks
            Carbon::create(2025, 1, 1),
            Carbon::create(2025, 12, 1),
        );

        $realRubles = $result / 100;

        // 500_000 / 1.005^12 ≈ 456_000–458_000 range
        expect($realRubles)->toBeGreaterThan(450000);
        expect($realRubles)->toBeLessThan(460000);
    });

    it('deflation increases real value', function () {
        for ($i = 1; $i <= 6; $i++) {
            CpiValue::create([
                'period' => Carbon::create(2025, $i, 1),
                'category_code' => 'TOTAL',
                'value' => 99.50,
                'source' => 'test',
            ]);
        }

        $nominal = 10000000;
        $result = $this->service->calculateRealValue(
            $nominal,
            Carbon::create(2025, 1, 1),
            Carbon::create(2025, 6, 1),
        );

        expect($result)->toBeGreaterThan($nominal);
    });
});

describe('calculatePersonalInflation', function () {
    it('falls back to general CPI when no transactions', function () {
        seedMonthlyTotalCpi([
            100.50, 100.50, 100.50, 100.50, 100.50, 100.50,
            100.50, 100.50, 100.50, 100.50, 100.50, 100.50,
        ]);

        $result = $this->service->calculatePersonalInflation(
            $this->user->id,
            Carbon::create(2025, 1, 1),
            Carbon::create(2025, 12, 31),
        );

        expect($result)->toBeGreaterThan(0.05);
        expect($result)->toBeLessThan(0.07);
    });

    it('weights inflation by spending structure', function () {
        $foodCategory = Category::factory()->expense()->system()->create([
            'name' => ['ru' => 'Продукты', 'en' => 'Food'],
        ]);
        $transportCategory = Category::factory()->expense()->system()->create([
            'name' => ['ru' => 'Транспорт', 'en' => 'Transport'],
        ]);

        CpiCategory::create([
            'code' => 'FOOD',
            'name' => 'Продовольственные товары',
            'mapping_to_app_category_id' => $foodCategory->id,
        ]);
        CpiCategory::create([
            'code' => 'TRANSPORT',
            'name' => 'Транспорт',
            'mapping_to_app_category_id' => $transportCategory->id,
        ]);

        for ($i = 1; $i <= 12; $i++) {
            CpiValue::create([
                'period' => Carbon::create(2025, $i, 1),
                'category_code' => 'FOOD',
                'value' => 101.50,
                'source' => 'test',
            ]);
            CpiValue::create([
                'period' => Carbon::create(2025, $i, 1),
                'category_code' => 'TRANSPORT',
                'value' => 100.30,
                'source' => 'test',
            ]);
            CpiValue::create([
                'period' => Carbon::create(2025, $i, 1),
                'category_code' => 'TOTAL',
                'value' => 100.80,
                'source' => 'test',
            ]);
        }

        // 80% food, 20% transport
        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $foodCategory->id,
            'amount' => 8000000,
            'date' => Carbon::create(2025, 6, 15),
        ]);
        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $transportCategory->id,
            'amount' => 2000000,
            'date' => Carbon::create(2025, 6, 15),
        ]);

        $personal = $this->service->calculatePersonalInflation(
            $this->user->id,
            Carbon::create(2025, 1, 1),
            Carbon::create(2025, 12, 31),
        );

        $foodInflation = pow(1.015, 12) - 1;    // ~0.1956
        $transportInflation = pow(1.003, 12) - 1; // ~0.0366

        // Personal should be closer to food inflation since 80% spending is food
        expect($personal)->toBeGreaterThan($transportInflation);
        expect($personal)->toBeLessThan($foodInflation);
        expect($personal)->toBeGreaterThan(0.15);
    });
});

describe('calculateInflationLoss', function () {
    it('returns zero when no savings', function () {
        $category = createCategory();

        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $category->id,
            'amount' => 5000000,
            'date' => Carbon::create(2025, 6, 15),
        ]);

        $result = $this->service->calculateInflationLoss(
            $this->user->id,
            Carbon::create(2025, 1, 1),
            Carbon::create(2025, 12, 31),
        );

        expect($result)->toBe(0);
    });

    it('returns zero when no CPI data', function () {
        $category = createCategory();

        Transaction::factory()->for($this->user)->income()->create([
            'category_id' => $category->id,
            'amount' => 10000000,
            'date' => Carbon::create(2025, 6, 15),
        ]);

        $result = $this->service->calculateInflationLoss(
            $this->user->id,
            Carbon::create(2025, 1, 1),
            Carbon::create(2025, 12, 31),
        );

        expect($result)->toBe(0);
    });

    it('calculates loss proportional to savings and inflation', function () {
        $category = createCategory();

        seedMonthlyTotalCpi([
            100.80, 100.80, 100.80, 100.80, 100.80, 100.80,
            100.80, 100.80, 100.80, 100.80, 100.80, 100.80,
        ]);

        Transaction::factory()->for($this->user)->income()->create([
            'category_id' => $category->id,
            'amount' => 120000000,
            'date' => Carbon::create(2025, 6, 15),
        ]);
        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $category->id,
            'amount' => 100000000,
            'date' => Carbon::create(2025, 6, 15),
        ]);

        $result = $this->service->calculateInflationLoss(
            $this->user->id,
            Carbon::create(2025, 1, 1),
            Carbon::create(2025, 12, 31),
        );

        // savings = 200_000 ₽ = 20_000_000 kopecks
        // compound = 1.008^12 ≈ 1.1003
        // loss = 20_000_000 * (1 - 1/1.1003) ≈ 1_822_000 kopecks ≈ 18_220 ₽
        $lossRubles = $result / 100;

        expect($lossRubles)->toBeGreaterThan(15000);
        expect($lossRubles)->toBeLessThan(20000);
        expect($result)->toBeGreaterThan(0);
    });

    it('returns zero when expenses exceed income', function () {
        $category = createCategory();

        seedMonthlyTotalCpi([
            100.80, 100.80, 100.80, 100.80, 100.80, 100.80,
            100.80, 100.80, 100.80, 100.80, 100.80, 100.80,
        ]);

        Transaction::factory()->for($this->user)->income()->create([
            'category_id' => $category->id,
            'amount' => 5000000,
            'date' => Carbon::create(2025, 6, 15),
        ]);
        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $category->id,
            'amount' => 8000000,
            'date' => Carbon::create(2025, 6, 15),
        ]);

        $result = $this->service->calculateInflationLoss(
            $this->user->id,
            Carbon::create(2025, 1, 1),
            Carbon::create(2025, 12, 31),
        );

        expect($result)->toBe(0);
    });
});
