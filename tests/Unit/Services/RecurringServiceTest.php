<?php

use App\Enums\RecurringInterval;
use App\Models\Category;
use App\Models\RecurringRule;
use App\Models\User;
use App\Services\RecurringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(RecurringService::class);
    $this->user = User::factory()->create();
    $this->category = Category::factory()->expense()->create();
});

describe('processDueRules', function () {
    it('creates transaction for due daily rule', function () {
        RecurringRule::factory()->for($this->user)->create([
            'category_id' => $this->category->id,
            'interval' => RecurringInterval::Daily,
            'amount' => 500,
            'next_run_at' => Carbon::parse('2026-02-08 00:00:00'),
        ]);

        $created = $this->service->processDueRules(Carbon::parse('2026-02-08 12:00:00'));

        expect($created)->toHaveCount(1);
        expect($created->first()->amount)->toBe(500);
        expect($created->first()->is_recurring)->toBeTrue();
    });

    it('creates multiple transactions for missed periods', function () {
        RecurringRule::factory()->for($this->user)->create([
            'category_id' => $this->category->id,
            'interval' => RecurringInterval::Daily,
            'amount' => 100,
            'next_run_at' => Carbon::parse('2026-02-05 00:00:00'),
        ]);

        $created = $this->service->processDueRules(Carbon::parse('2026-02-08 00:00:00'));

        expect($created)->toHaveCount(4);
    });

    it('advances next_run_at after processing', function () {
        $rule = RecurringRule::factory()->for($this->user)->create([
            'category_id' => $this->category->id,
            'interval' => RecurringInterval::Monthly,
            'next_run_at' => Carbon::parse('2026-02-01 00:00:00'),
        ]);

        $this->service->processDueRules(Carbon::parse('2026-02-15 00:00:00'));

        $rule->refresh();
        expect($rule->next_run_at->toDateString())->toBe('2026-03-01');
    });

    it('skips inactive rules', function () {
        RecurringRule::factory()->inactive()->for($this->user)->create([
            'category_id' => $this->category->id,
            'next_run_at' => Carbon::parse('2026-02-01 00:00:00'),
        ]);

        $created = $this->service->processDueRules(Carbon::parse('2026-02-15 00:00:00'));

        expect($created)->toHaveCount(0);
    });

    it('skips rules not yet due', function () {
        RecurringRule::factory()->for($this->user)->create([
            'category_id' => $this->category->id,
            'next_run_at' => Carbon::parse('2026-03-01 00:00:00'),
        ]);

        $created = $this->service->processDueRules(Carbon::parse('2026-02-15 00:00:00'));

        expect($created)->toHaveCount(0);
    });

    it('handles weekly interval correctly', function () {
        $rule = RecurringRule::factory()->for($this->user)->create([
            'category_id' => $this->category->id,
            'interval' => RecurringInterval::Weekly,
            'next_run_at' => Carbon::parse('2026-02-01 00:00:00'),
        ]);

        $created = $this->service->processDueRules(Carbon::parse('2026-02-15 00:00:00'));

        expect($created)->toHaveCount(3);
        $rule->refresh();
        expect($rule->next_run_at->toDateString())->toBe('2026-02-22');
    });

    it('handles yearly interval correctly', function () {
        $rule = RecurringRule::factory()->for($this->user)->create([
            'category_id' => $this->category->id,
            'interval' => RecurringInterval::Yearly,
            'next_run_at' => Carbon::parse('2026-01-01 00:00:00'),
        ]);

        $created = $this->service->processDueRules(Carbon::parse('2026-02-15 00:00:00'));

        expect($created)->toHaveCount(1);
        $rule->refresh();
        expect($rule->next_run_at->toDateString())->toBe('2027-01-01');
    });
});

describe('createRule', function () {
    it('creates a rule with correct attributes', function () {
        $rule = $this->service->createRule($this->user->id, [
            'type' => 'expense',
            'amount' => 10000,
            'category_id' => $this->category->id,
            'interval' => 'monthly',
            'start_date' => '2026-03-01',
            'comment' => 'ЖКХ',
        ]);

        expect($rule->user_id)->toBe($this->user->id);
        expect($rule->amount)->toBe(10000);
        expect($rule->interval)->toBe(RecurringInterval::Monthly);
        expect($rule->next_run_at->toDateString())->toBe('2026-03-01');
        expect($rule->is_active)->toBeTrue();
        expect($rule->comment)->toBe('ЖКХ');
    });

    it('defaults currency to RUB', function () {
        $rule = $this->service->createRule($this->user->id, [
            'type' => 'income',
            'amount' => 50000,
            'category_id' => $this->category->id,
            'interval' => 'monthly',
            'start_date' => '2026-03-01',
        ]);

        expect($rule->currency_code)->toBe('RUB');
    });
});

describe('activate and deactivate', function () {
    it('deactivates a rule', function () {
        $rule = RecurringRule::factory()->for($this->user)->create([
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $this->service->deactivate($rule);

        expect($rule->fresh()->is_active)->toBeFalse();
    });

    it('activates a rule', function () {
        $rule = RecurringRule::factory()->inactive()->for($this->user)->create([
            'category_id' => $this->category->id,
        ]);

        $this->service->activate($rule);

        expect($rule->fresh()->is_active)->toBeTrue();
    });
});
