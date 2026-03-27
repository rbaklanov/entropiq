<?php

use App\Models\Goal;
use App\Models\User;
use App\Services\GoalCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(GoalCalculationService::class);
    $this->user = User::factory()->create();
});

describe('requiredMonthlyPayment', function () {
    it('calculates simple division', function () {
        $result = $this->service->requiredMonthlyPayment(1200000, 0, 12);

        expect($result)->toBe(100000);
    });

    it('accounts for already saved amount', function () {
        $result = $this->service->requiredMonthlyPayment(1200000, 600000, 12);

        expect($result)->toBe(50000);
    });

    it('returns zero when goal already achieved', function () {
        $result = $this->service->requiredMonthlyPayment(1000000, 1000000, 12);

        expect($result)->toBe(0);
    });

    it('returns zero when over-saved', function () {
        $result = $this->service->requiredMonthlyPayment(1000000, 1500000, 12);

        expect($result)->toBe(0);
    });

    it('returns zero when no months left', function () {
        $result = $this->service->requiredMonthlyPayment(1000000, 0, 0);

        expect($result)->toBe(0);
    });

    it('rounds up to nearest integer', function () {
        $result = $this->service->requiredMonthlyPayment(1000000, 0, 7);

        expect($result)->toBe(142858);
    });
});

describe('requiredMonthlyPaymentWithInflation', function () {
    it('returns higher payment than without inflation', function () {
        $withoutInflation = $this->service->requiredMonthlyPayment(2000000, 0, 12);
        $withInflation = $this->service->requiredMonthlyPaymentWithInflation(2000000, 0, 12, 0.095);

        expect($withInflation)->toBeGreaterThan($withoutInflation);
    });

    it('returns zero when goal achieved', function () {
        $result = $this->service->requiredMonthlyPaymentWithInflation(1000000, 1000000, 12);

        expect($result)->toBe(0);
    });

    it('returns zero when no months left', function () {
        $result = $this->service->requiredMonthlyPaymentWithInflation(1000000, 0, 0);

        expect($result)->toBe(0);
    });

    it('higher inflation means higher payment', function () {
        $low = $this->service->requiredMonthlyPaymentWithInflation(2000000, 0, 12, 0.05);
        $high = $this->service->requiredMonthlyPaymentWithInflation(2000000, 0, 12, 0.15);

        expect($high)->toBeGreaterThan($low);
    });

    it('matches research.md example approximately', function () {
        // research.md: 200_000 ₽ goal, 12 months, inflation 9.5%
        // GOAL_nominal = 200_000 * (1.095)^1 = 219_000
        // PMT = 219_000 / 12 ≈ 18_250
        $result = $this->service->requiredMonthlyPaymentWithInflation(20000000, 0, 12, 0.095);

        // 200_000 ₽ = 20_000_000 kopecks
        // GOAL_nominal = 20_000_000 * 1.095 = 21_900_000
        // PMT = 21_900_000 / 12 = 1_825_000
        expect($result)->toBe(1825000);
    });
});

describe('predictCompletionMonths', function () {
    it('returns null when payment is zero', function () {
        $result = $this->service->predictCompletionMonths(1000000, 0, 0);

        expect($result)->toBeNull();
    });

    it('returns zero when goal already achieved', function () {
        $result = $this->service->predictCompletionMonths(1000000, 1000000, 50000);

        expect($result)->toBe(0);
    });

    it('calculates months for simple case', function () {
        // 100_000 remaining, 10_000/month, with inflation it takes slightly more
        $result = $this->service->predictCompletionMonths(100000, 0, 10000, 0.0);

        expect($result)->toBe(10);
    });

    it('inflation increases months needed', function () {
        $withoutInflation = $this->service->predictCompletionMonths(1000000, 0, 100000, 0.0);
        $withInflation = $this->service->predictCompletionMonths(1000000, 0, 100000, 0.095);

        expect($withInflation)->toBeGreaterThan($withoutInflation);
    });

    it('larger payment means fewer months', function () {
        $small = $this->service->predictCompletionMonths(1000000, 0, 50000);
        $large = $this->service->predictCompletionMonths(1000000, 0, 100000);

        expect($large)->toBeLessThan($small);
    });
});

describe('predictCompletionDate', function () {
    it('returns null when payment is zero', function () {
        $result = $this->service->predictCompletionDate(1000000, 0, 0);

        expect($result)->toBeNull();
    });

    it('returns a future date', function () {
        $result = $this->service->predictCompletionDate(1000000, 0, 100000);

        expect($result)->not->toBeNull();
        expect($result->isFuture())->toBeTrue();
    });
});

describe('buildScenarios', function () {
    it('returns three scenarios with correct inflation values relative to current CPI', function () {
        $goal = Goal::factory()->for($this->user)->create([
            'target_amount' => 5000000,
            'current_amount' => 0,
            'target_date' => now()->addMonths(12),
        ]);

        $result = $this->service->buildScenarios($goal);
        $baseline = $this->service->getCurrentAnnualInflation();

        expect($result)->toHaveKeys(['optimistic', 'baseline', 'pessimistic']);
        expect($result['baseline']['inflation'])->toBe($baseline);
        expect($result['optimistic']['inflation'])->toBe(max(0.01, $baseline - 0.04));
        expect($result['pessimistic']['inflation'])->toBe($baseline + 0.05);
    });

    it('pessimistic requires higher monthly payment', function () {
        $goal = Goal::factory()->for($this->user)->create([
            'target_amount' => 5000000,
            'current_amount' => 0,
            'target_date' => now()->addMonths(12),
        ]);

        $result = $this->service->buildScenarios($goal);

        expect($result['pessimistic']['monthly_payment'])
            ->toBeGreaterThan($result['optimistic']['monthly_payment']);
    });

    it('all scenarios have completion dates', function () {
        $goal = Goal::factory()->for($this->user)->create([
            'target_amount' => 5000000,
            'current_amount' => 1000000,
            'started_at' => now()->subMonths(3),
            'target_date' => now()->addMonths(12),
        ]);

        $result = $this->service->buildScenarios($goal);

        expect($result['optimistic']['completion_date'])->not->toBeNull();
        expect($result['baseline']['completion_date'])->not->toBeNull();
        expect($result['pessimistic']['completion_date'])->not->toBeNull();
    });
});

describe('whatIf', function () {
    it('returns correct structure', function () {
        $goal = Goal::factory()->for($this->user)->create([
            'target_amount' => 5000000,
            'current_amount' => 1000000,
            'started_at' => now()->subMonths(3),
            'target_date' => now()->addMonths(12),
        ]);

        $result = $this->service->whatIf($goal, 50000);

        expect($result)->toHaveKeys([
            'current_monthly', 'new_monthly',
            'current_completion', 'new_completion',
            'days_saved',
        ]);
    });

    it('new monthly equals current plus additional', function () {
        $goal = Goal::factory()->for($this->user)->create([
            'target_amount' => 5000000,
            'current_amount' => 1000000,
            'started_at' => now()->subMonths(3),
            'target_date' => now()->addMonths(12),
        ]);

        $result = $this->service->whatIf($goal, 50000);

        expect($result['new_monthly'])->toBe($result['current_monthly'] + 50000);
    });

    it('days saved is positive with additional payment', function () {
        $goal = Goal::factory()->for($this->user)->create([
            'target_amount' => 3000000,
            'current_amount' => 1500000,
            'started_at' => now()->subMonths(3),
            'target_date' => now()->addMonths(12),
        ]);

        $result = $this->service->whatIf($goal, 500000);

        expect($result['days_saved'])->toBeGreaterThan(0);
    });

    it('new completion is earlier than current', function () {
        $goal = Goal::factory()->for($this->user)->create([
            'target_amount' => 3000000,
            'current_amount' => 1500000,
            'started_at' => now()->subMonths(3),
            'target_date' => now()->addMonths(12),
        ]);

        $result = $this->service->whatIf($goal, 500000);

        expect(Carbon::parse($result['new_completion'])->lt(Carbon::parse($result['current_completion'])))->toBeTrue();
    });
});

describe('getMonthsLeft', function () {
    it('returns 12 when no target date', function () {
        $goal = Goal::factory()->for($this->user)->create([
            'target_date' => null,
        ]);

        expect($this->service->getMonthsLeft($goal))->toBe(12);
    });

    it('returns at least 1', function () {
        $goal = Goal::factory()->for($this->user)->create([
            'target_date' => now()->subDay(),
        ]);

        expect($this->service->getMonthsLeft($goal))->toBe(1);
    });

    it('returns correct months for future date', function () {
        $goal = Goal::factory()->for($this->user)->create([
            'target_date' => now()->addMonths(6),
        ]);

        $months = $this->service->getMonthsLeft($goal);

        expect($months)->toBeGreaterThanOrEqual(5);
        expect($months)->toBeLessThanOrEqual(7);
    });
});

describe('estimateCurrentMonthlyPayment', function () {
    it('uses contribution history when available', function () {
        $goal = Goal::factory()->for($this->user)->create([
            'target_amount' => 1200000,
            'current_amount' => 300000,
            'started_at' => now()->subMonths(3),
            'target_date' => now()->addMonths(9),
        ]);

        $result = $this->service->estimateCurrentMonthlyPayment($goal);

        expect($result)->toBe(100000);
    });

    it('falls back to equal division when no savings', function () {
        $goal = Goal::factory()->for($this->user)->create([
            'target_amount' => 1200000,
            'current_amount' => 0,
            'started_at' => now()->subMonth(),
            'target_date' => null,
        ]);

        $result = $this->service->estimateCurrentMonthlyPayment($goal);

        expect($result)->toBe(100000);
    });
});
