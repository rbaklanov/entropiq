<?php

use App\Models\AiAdvice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('advice:stats command', function () {
    it('reports when there is no advice', function () {
        $this->artisan('advice:stats')
            ->assertSuccessful()
            ->expectsOutputToContain('No advice found.');
    });

    it('groups ratings by rule with helpful share and muted users', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        AiAdvice::factory()->for($user)->count(2)->create(['basis_data' => ['rule' => 'overspending'], 'rating' => AiAdvice::RATING_UNHELPFUL]);
        AiAdvice::factory()->for($otherUser)->create(['basis_data' => ['rule' => 'overspending'], 'rating' => AiAdvice::RATING_HELPFUL]);
        AiAdvice::factory()->for($otherUser)->create(['basis_data' => ['rule' => 'overspending'], 'rating' => null]);
        AiAdvice::factory()->for($user)->create(['basis_data' => ['rule' => 'category_spike'], 'rating' => AiAdvice::RATING_HELPFUL]);

        $this->artisan('advice:stats')
            ->assertSuccessful()
            ->expectsTable(
                ['Rule', 'Advices', 'Rated', 'Helpful', 'Unhelpful', 'Helpful share', 'Muted users'],
                [
                    ['category_spike', 1, 1, 1, 0, '100%', 0],
                    ['overspending', 4, 3, 1, 2, '33%', 1],
                ],
            )
            ->expectsOutputToContain('Rated 4 of 5 advice(s).');
    });

    it('limits the report to recent advice with --days', function () {
        Carbon::setTestNow('2026-04-15');

        $user = User::factory()->create();

        AiAdvice::factory()->for($user)->create(['basis_data' => ['rule' => 'overspending'], 'rating' => AiAdvice::RATING_HELPFUL, 'generated_at' => Carbon::today()]);
        AiAdvice::factory()->for($user)->create(['basis_data' => ['rule' => 'overspending'], 'rating' => AiAdvice::RATING_UNHELPFUL, 'generated_at' => Carbon::today()->subDays(40)]);

        $this->artisan('advice:stats --days=7')
            ->assertSuccessful()
            ->expectsOutputToContain('the last 7 day(s)')
            ->expectsOutputToContain('Rated 1 of 1 advice(s).');

        Carbon::setTestNow();
    });

    it('rejects a non-positive --days value', function () {
        $this->artisan('advice:stats --days=0')->assertExitCode(2);
    });
});
