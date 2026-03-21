<?php

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(TransactionService::class);
    $this->user = User::factory()->create();
    $this->category = Category::factory()->expense()->create();
});

describe('getForPeriod', function () {
    it('returns transactions within period', function () {
        Transaction::factory()->for($this->user)->create([
            'category_id' => $this->category->id,
            'date' => '2026-02-15',
        ]);
        Transaction::factory()->for($this->user)->create([
            'category_id' => $this->category->id,
            'date' => '2026-03-15',
        ]);

        $result = $this->service->getForPeriod($this->user->id, [
            'from' => '2026-02-01',
            'to' => '2026-02-28',
        ]);

        expect($result)->toHaveCount(1);
    });

    it('filters by type', function () {
        Transaction::factory()->income()->for($this->user)->create([
            'category_id' => $this->category->id,
            'date' => '2026-02-10',
        ]);
        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $this->category->id,
            'date' => '2026-02-10',
        ]);

        $result = $this->service->getForPeriod($this->user->id, [
            'type' => 'income',
            'from' => '2026-02-01',
            'to' => '2026-02-28',
        ]);

        expect($result)->toHaveCount(1);
        expect($result->first()->type)->toBe(TransactionType::Income);
    });

    it('filters by category', function () {
        $otherCategory = Category::factory()->create();

        Transaction::factory()->for($this->user)->create([
            'category_id' => $this->category->id,
            'date' => '2026-02-10',
        ]);
        Transaction::factory()->for($this->user)->create([
            'category_id' => $otherCategory->id,
            'date' => '2026-02-10',
        ]);

        $result = $this->service->getForPeriod($this->user->id, [
            'category_id' => $this->category->id,
            'from' => '2026-02-01',
            'to' => '2026-02-28',
        ]);

        expect($result)->toHaveCount(1);
    });

    it('searches by comment', function () {
        Transaction::factory()->for($this->user)->create([
            'category_id' => $this->category->id,
            'date' => '2026-02-10',
            'comment' => 'Обед в кафе',
        ]);
        Transaction::factory()->for($this->user)->create([
            'category_id' => $this->category->id,
            'date' => '2026-02-10',
            'comment' => 'Зарплата',
        ]);

        $result = $this->service->getForPeriod($this->user->id, [
            'search' => 'кафе',
            'from' => '2026-02-01',
            'to' => '2026-02-28',
        ]);

        expect($result)->toHaveCount(1);
    });
});

describe('getSummary', function () {
    it('calculates income expense and balance', function () {
        Transaction::factory()->income()->for($this->user)->create([
            'category_id' => $this->category->id,
            'amount' => 50000,
            'date' => '2026-02-10',
        ]);
        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $this->category->id,
            'amount' => 20000,
            'date' => '2026-02-15',
        ]);

        $summary = $this->service->getSummary(
            $this->user->id,
            Carbon::parse('2026-02-01'),
            Carbon::parse('2026-02-28'),
        );

        expect($summary['income'])->toBe(50000);
        expect($summary['expense'])->toBe(20000);
        expect($summary['balance'])->toBe(30000);
    });

    it('returns zeros for empty period', function () {
        $summary = $this->service->getSummary(
            $this->user->id,
            Carbon::parse('2026-02-01'),
            Carbon::parse('2026-02-28'),
        );

        expect($summary)->toBe(['income' => 0, 'expense' => 0, 'balance' => 0]);
    });

    it('excludes transactions outside period', function () {
        Transaction::factory()->income()->for($this->user)->create([
            'category_id' => $this->category->id,
            'amount' => 100000,
            'date' => '2026-01-15',
        ]);

        $summary = $this->service->getSummary(
            $this->user->id,
            Carbon::parse('2026-02-01'),
            Carbon::parse('2026-02-28'),
        );

        expect($summary['income'])->toBe(0);
    });
});

describe('getByCategory', function () {
    it('groups transactions by category', function () {
        $cat1 = Category::factory()->expense()->create();
        $cat2 = Category::factory()->expense()->create();

        Transaction::factory()->expense()->for($this->user)->count(2)->create([
            'category_id' => $cat1->id,
            'amount' => 10000,
            'date' => '2026-02-10',
        ]);
        Transaction::factory()->expense()->for($this->user)->create([
            'category_id' => $cat2->id,
            'amount' => 5000,
            'date' => '2026-02-10',
        ]);

        $result = $this->service->getByCategory(
            $this->user->id,
            Carbon::parse('2026-02-01'),
            Carbon::parse('2026-02-28'),
            TransactionType::Expense,
        );

        expect($result)->toHaveCount(2);
        expect($result[0]['category_id'])->toBe($cat1->id);
        expect($result[0]['total'])->toBe(20000);
        expect($result[0]['count'])->toBe(2);
        expect($result[1]['total'])->toBe(5000);
    });

    it('returns empty array when no transactions', function () {
        $result = $this->service->getByCategory(
            $this->user->id,
            Carbon::parse('2026-02-01'),
            Carbon::parse('2026-02-28'),
            TransactionType::Expense,
        );

        expect($result)->toBe([]);
    });
});
