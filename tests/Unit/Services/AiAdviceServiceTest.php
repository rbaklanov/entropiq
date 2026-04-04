<?php

use App\Contracts\AiAdviceServiceInterface;
use App\Dto\AdvicePayload;
use App\Models\AiAdvice;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FakeLlmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->service = app(AiAdviceServiceInterface::class);
});

describe('generateForUser', function () {
    it('returns empty collection when no rules trigger', function () {
        $result = $this->service->generateForUser($this->user);

        expect($result)->toHaveCount(0);
    });

    it('creates AiAdvice records when rules trigger', function () {
        Carbon::setTestNow('2026-04-15');

        $category = Category::factory()->expense()->system()->create();
        $incomeCategory = Category::factory()->income()->system()->create();

        Transaction::factory()->for($this->user)->income()->create([
            'category_id' => $incomeCategory->id,
            'amount' => 50000,
            'date' => Carbon::create(2026, 4, 5),
        ]);
        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $category->id,
            'amount' => 80000,
            'date' => Carbon::create(2026, 4, 5),
        ]);

        $result = $this->service->generateForUser($this->user);

        expect($result)->toHaveCount(1);
        expect($result->first())->toBeInstanceOf(AiAdvice::class);
        expect($result->first()->user_id)->toBe($this->user->id);
        expect($result->first()->basis_data['rule'])->toBe('overspending');

        expect(AiAdvice::where('user_id', $this->user->id)->count())->toBe(1);

        Carbon::setTestNow();
    });

    it('does not duplicate advice for the same rule on the same day', function () {
        Carbon::setTestNow('2026-04-15');

        $category = Category::factory()->expense()->system()->create();
        $incomeCategory = Category::factory()->income()->system()->create();

        Transaction::factory()->for($this->user)->income()->create([
            'category_id' => $incomeCategory->id,
            'amount' => 50000,
            'date' => Carbon::create(2026, 4, 5),
        ]);
        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $category->id,
            'amount' => 80000,
            'date' => Carbon::create(2026, 4, 5),
        ]);

        $first = $this->service->generateForUser($this->user);
        $second = $this->service->generateForUser($this->user);

        expect($first)->toHaveCount(1);
        expect($second)->toHaveCount(0);
        expect(AiAdvice::where('user_id', $this->user->id)->count())->toBe(1);

        Carbon::setTestNow();
    });

    it('allows same rule on different days', function () {
        $category = Category::factory()->expense()->system()->create();
        $incomeCategory = Category::factory()->income()->system()->create();

        Carbon::setTestNow('2026-04-15');

        Transaction::factory()->for($this->user)->income()->create([
            'category_id' => $incomeCategory->id,
            'amount' => 50000,
            'date' => Carbon::create(2026, 4, 5),
        ]);
        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => $category->id,
            'amount' => 80000,
            'date' => Carbon::create(2026, 4, 5),
        ]);

        $this->service->generateForUser($this->user);

        Carbon::setTestNow('2026-04-16');
        $second = $this->service->generateForUser($this->user);

        expect($second)->toHaveCount(1);
        expect(AiAdvice::where('user_id', $this->user->id)->count())->toBe(2);

        Carbon::setTestNow();
    });
});

describe('FakeLlmService', function () {
    it('interpolates template variables for category_spike', function () {
        $llm = new FakeLlmService;

        $payload = new AdvicePayload(
            ruleKey: 'category_spike',
            title: 'raw',
            body: 'raw',
            basisData: [
                'rule' => 'category_spike',
                'category_name' => 'Продукты',
                'current_total' => 50000,
                'avg_monthly' => 30000,
                'growth_percent' => 67,
            ],
        );

        $result = $llm->generateAdviceText($payload);

        expect($result['title'])->toContain('Продукты');
        expect($result['body'])->toContain('50000');
        expect($result['body'])->toContain('67%');
    });

    it('falls back to raw text for unknown rule', function () {
        $llm = new FakeLlmService;

        $payload = new AdvicePayload(
            ruleKey: 'unknown_rule',
            title: 'Raw Title',
            body: 'Raw Body',
        );

        $result = $llm->generateAdviceText($payload);

        expect($result['title'])->toBe('Raw Title');
        expect($result['body'])->toBe('Raw Body');
    });
});
