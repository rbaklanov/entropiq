<?php

use App\Models\AiAdvice;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('advice:generate command', function () {
    it('runs successfully with no users', function () {
        $this->artisan('advice:generate')
            ->assertSuccessful()
            ->expectsOutputToContain('Processing 0 user(s)');
    });

    it('processes all users by default', function () {
        User::factory()->count(3)->create();

        $this->artisan('advice:generate')
            ->assertSuccessful()
            ->expectsOutputToContain('Processing 3 user(s)');
    });

    it('processes single user with --user option', function () {
        $user = User::factory()->create();
        User::factory()->count(2)->create();

        $this->artisan("advice:generate --user={$user->id}")
            ->assertSuccessful()
            ->expectsOutputToContain('Processing 1 user(s)');
    });

    it('generates and persists advice when rules trigger', function () {
        Carbon::setTestNow('2026-04-15');

        $user = User::factory()->create();
        $category = Category::factory()->expense()->system()->create();
        $incomeCategory = Category::factory()->income()->system()->create();

        Transaction::factory()->for($user)->income()->create([
            'category_id' => $incomeCategory->id,
            'amount' => 50000,
            'date' => Carbon::create(2026, 4, 5),
        ]);
        Transaction::factory()->for($user)->expense()->create([
            'category_id' => $category->id,
            'amount' => 80000,
            'date' => Carbon::create(2026, 4, 5),
        ]);

        $this->artisan("advice:generate --user={$user->id}")
            ->assertSuccessful()
            ->expectsOutputToContain('Generated 1 advice(s)');

        expect(AiAdvice::where('user_id', $user->id)->count())->toBe(1);

        Carbon::setTestNow();
    });

    it('outputs "no rules triggered" when user has insufficient data', function () {
        $user = User::factory()->create();

        $this->artisan("advice:generate --user={$user->id}")
            ->assertSuccessful()
            ->expectsOutputToContain('no rules triggered');
    });
});
