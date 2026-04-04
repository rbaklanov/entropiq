<?php

use App\Enums\GoalStatus;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use App\Rules\Advice\CategorySpikeRule;
use App\Rules\Advice\GoalBehindScheduleRule;
use App\Rules\Advice\OverspendingRule;
use App\Rules\Advice\SavingsOptimizationRule;
use App\Rules\Advice\UnusualTransactionRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->expense()->system()->create();
});

describe('CategorySpikeRule', function () {
    it('returns null when no transactions', function () {
        $rule = new CategorySpikeRule;

        expect($rule->evaluate($this->user))->toBeNull();
    });

    it('returns null when growth is within threshold', function () {
        Carbon::setTestNow('2026-04-15');
        $rule = new CategorySpikeRule;

        foreach ([1, 2, 3] as $month) {
            Transaction::factory()->for($this->user)->expense()->create([
                'category_id' => $this->category->id,
                'amount' => 10000,
                'date' => Carbon::create(2026, $month, 15),
            ]);
        }

        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $this->category->id,
            'amount' => 11000,
            'date' => Carbon::create(2026, 4, 10),
        ]);

        expect($rule->evaluate($this->user))->toBeNull();

        Carbon::setTestNow();
    });

    it('triggers when category spending spikes over 20%', function () {
        Carbon::setTestNow('2026-04-15');
        $rule = new CategorySpikeRule;

        foreach ([1, 2, 3] as $month) {
            Transaction::factory()->for($this->user)->expense()->create([
                'category_id' => $this->category->id,
                'amount' => 10000,
                'date' => Carbon::create(2026, $month, 15),
            ]);
        }

        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $this->category->id,
            'amount' => 50000,
            'date' => Carbon::create(2026, 4, 10),
        ]);

        $result = $rule->evaluate($this->user);

        expect($result)->not->toBeNull();
        expect($result->ruleKey)->toBe('category_spike');
        expect($result->basisData['growth_percent'])->toBeGreaterThan(20);
        expect($result->basisData['category_id'])->toBe($this->category->id);

        Carbon::setTestNow();
    });

    it('picks the category with the biggest spike', function () {
        Carbon::setTestNow('2026-04-15');
        $rule = new CategorySpikeRule;
        $otherCategory = Category::factory()->expense()->system()->create();

        foreach ([1, 2, 3] as $month) {
            Transaction::factory()->for($this->user)->expense()->create([
                'category_id' => $this->category->id,
                'amount' => 10000,
                'date' => Carbon::create(2026, $month, 15),
            ]);
            Transaction::factory()->for($this->user)->expense()->create([
                'category_id' => $otherCategory->id,
                'amount' => 10000,
                'date' => Carbon::create(2026, $month, 15),
            ]);
        }

        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $this->category->id,
            'amount' => 20000,
            'date' => Carbon::create(2026, 4, 10),
        ]);
        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $otherCategory->id,
            'amount' => 80000,
            'date' => Carbon::create(2026, 4, 10),
        ]);

        $result = $rule->evaluate($this->user);

        expect($result)->not->toBeNull();
        expect($result->basisData['category_id'])->toBe($otherCategory->id);

        Carbon::setTestNow();
    });
});

describe('OverspendingRule', function () {
    it('returns null when no income', function () {
        $rule = app(OverspendingRule::class);

        expect($rule->evaluate($this->user))->toBeNull();
    });

    it('returns null when income covers expenses', function () {
        $rule = app(OverspendingRule::class);
        $incomeCategory = Category::factory()->income()->system()->create();

        Transaction::factory()->for($this->user)->income()->create([
            'category_id' => $incomeCategory->id,
            'amount' => 100000,
            'date' => now(),
        ]);
        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $this->category->id,
            'amount' => 80000,
            'date' => now(),
        ]);

        expect($rule->evaluate($this->user))->toBeNull();
    });

    it('triggers when expenses exceed income', function () {
        $rule = app(OverspendingRule::class);
        $incomeCategory = Category::factory()->income()->system()->create();

        Transaction::factory()->for($this->user)->income()->create([
            'category_id' => $incomeCategory->id,
            'amount' => 50000,
            'date' => now(),
        ]);
        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $this->category->id,
            'amount' => 80000,
            'date' => now(),
        ]);

        $result = $rule->evaluate($this->user);

        expect($result)->not->toBeNull();
        expect($result->ruleKey)->toBe('overspending');
        expect($result->basisData['overspend'])->toBe(30000);
        expect($result->basisData['income'])->toBe(50000);
        expect($result->basisData['expense'])->toBe(80000);
    });
});

describe('GoalBehindScheduleRule', function () {
    it('returns null when no active goals', function () {
        $rule = new GoalBehindScheduleRule;

        expect($rule->evaluate($this->user))->toBeNull();
    });

    it('returns null when goal is on track', function () {
        Carbon::setTestNow('2026-07-01');
        $rule = new GoalBehindScheduleRule;

        Goal::factory()->for($this->user)->create([
            'target_amount' => 100000,
            'current_amount' => 55000,
            'started_at' => Carbon::create(2026, 1, 1),
            'target_date' => Carbon::create(2027, 1, 1),
            'status' => GoalStatus::Active,
        ]);

        expect($rule->evaluate($this->user))->toBeNull();

        Carbon::setTestNow();
    });

    it('triggers when goal lags behind schedule by 10%+', function () {
        Carbon::setTestNow('2026-07-01');
        $rule = new GoalBehindScheduleRule;

        Goal::factory()->for($this->user)->create([
            'name' => 'Test Goal',
            'target_amount' => 100000,
            'current_amount' => 10000,
            'started_at' => Carbon::create(2026, 1, 1),
            'target_date' => Carbon::create(2027, 1, 1),
            'status' => GoalStatus::Active,
        ]);

        $result = $rule->evaluate($this->user);

        expect($result)->not->toBeNull();
        expect($result->ruleKey)->toBe('goal_behind_schedule');
        expect($result->basisData['goal_name'])->toBe('Test Goal');
        expect($result->basisData['lag_percent'])->toBeGreaterThanOrEqual(10);

        Carbon::setTestNow();
    });

    it('ignores achieved and cancelled goals', function () {
        Carbon::setTestNow('2026-07-01');
        $rule = new GoalBehindScheduleRule;

        Goal::factory()->for($this->user)->achieved()->create([
            'started_at' => Carbon::create(2026, 1, 1),
            'target_date' => Carbon::create(2027, 1, 1),
        ]);
        Goal::factory()->for($this->user)->cancelled()->create([
            'started_at' => Carbon::create(2026, 1, 1),
            'target_date' => Carbon::create(2027, 1, 1),
        ]);

        expect($rule->evaluate($this->user))->toBeNull();

        Carbon::setTestNow();
    });
});

describe('UnusualTransactionRule', function () {
    it('returns null when no recent transactions', function () {
        $rule = new UnusualTransactionRule;

        expect($rule->evaluate($this->user))->toBeNull();
    });

    it('returns null when fewer than 5 historical transactions in category', function () {
        Carbon::setTestNow('2026-04-15');
        $rule = new UnusualTransactionRule;

        Transaction::factory()->count(3)->for($this->user)->expense()->create([
            'category_id' => $this->category->id,
            'amount' => 1000,
            'date' => Carbon::create(2026, 2, 15),
        ]);

        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $this->category->id,
            'amount' => 100000,
            'date' => Carbon::create(2026, 4, 14),
        ]);

        expect($rule->evaluate($this->user))->toBeNull();

        Carbon::setTestNow();
    });

    it('triggers when transaction is significantly above category average', function () {
        Carbon::setTestNow('2026-04-15');
        $rule = new UnusualTransactionRule;

        foreach (range(1, 10) as $i) {
            Transaction::factory()->for($this->user)->expense()->create([
                'category_id' => $this->category->id,
                'amount' => 1000,
                'date' => Carbon::create(2026, 2, $i),
            ]);
        }

        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $this->category->id,
            'amount' => 50000,
            'date' => Carbon::create(2026, 4, 14),
        ]);

        $result = $rule->evaluate($this->user);

        expect($result)->not->toBeNull();
        expect($result->ruleKey)->toBe('unusual_transaction');
        expect($result->basisData['amount'])->toBe(50000);
        expect($result->basisData['multiplier'])->toBeGreaterThan(3.0);

        Carbon::setTestNow();
    });
});

describe('SavingsOptimizationRule', function () {
    it('returns null when no expenses', function () {
        $rule = app(SavingsOptimizationRule::class);

        expect($rule->evaluate($this->user))->toBeNull();
    });

    it('returns null when discretionary share is below 15%', function () {
        $rule = app(SavingsOptimizationRule::class);
        $essentialCategory = Category::factory()->expense()->system()->create([
            'name' => ['ru' => 'ЖКХ', 'en' => 'Utilities'],
        ]);

        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $essentialCategory->id,
            'amount' => 100000,
            'date' => now(),
        ]);

        expect($rule->evaluate($this->user))->toBeNull();
    });

    it('triggers when discretionary spending exceeds 15%', function () {
        $rule = app(SavingsOptimizationRule::class);
        $cafeCategory = Category::factory()->expense()->system()->create([
            'name' => ['ru' => 'Кафе и рестораны', 'en' => 'Cafes'],
        ]);
        $essentialCategory = Category::factory()->expense()->system()->create([
            'name' => ['ru' => 'ЖКХ', 'en' => 'Utilities'],
        ]);

        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $cafeCategory->id,
            'amount' => 50000,
            'date' => now(),
        ]);
        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $essentialCategory->id,
            'amount' => 100000,
            'date' => now(),
        ]);

        $result = $rule->evaluate($this->user);

        expect($result)->not->toBeNull();
        expect($result->ruleKey)->toBe('savings_optimization');
        expect($result->basisData['discretionary_share_percent'])->toBeGreaterThanOrEqual(15);

        Carbon::setTestNow();
    });
});
