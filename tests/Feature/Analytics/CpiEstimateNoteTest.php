<?php

use App\Livewire\Analytics;
use App\Livewire\InflationCalculator;
use App\Models\Category;
use App\Models\CpiValue;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function seedCpiUpTo(Carbon $latest): void
{
    for ($i = 0; $i < 12; $i++) {
        CpiValue::create([
            'period' => $latest->copy()->startOfMonth()->subMonths($i),
            'category_code' => 'TOTAL',
            'value' => 100.5,
            'source' => 'test',
        ]);
    }
}

function userWithTransactionThisMonth(): User
{
    $user = User::factory()->onboarded()->create(['phone_verified_at' => now()]);

    Transaction::factory()->for($user)->income()->create([
        'category_id' => Category::factory()->income()->create()->id,
        'amount' => 100000,
        'date' => now(),
    ]);

    return $user;
}

describe('CPI estimate note on the balance tab', function () {
    it('is shown when the current month has no published CPI', function () {
        seedCpiUpTo(now()->subMonths(2));

        Livewire::actingAs(userWithTransactionThisMonth())
            ->test(Analytics::class)
            ->set('tab', 'balance')
            ->assertSeeHtml('data-testid="cpi-estimate-note"');
    });

    it('is hidden when CPI is published up to the current month', function () {
        seedCpiUpTo(now());

        Livewire::actingAs(userWithTransactionThisMonth())
            ->test(Analytics::class)
            ->set('tab', 'balance')
            ->assertDontSeeHtml('data-testid="cpi-estimate-note"');
    });

    it('is hidden when no CPI exists at all', function () {
        Livewire::actingAs(userWithTransactionThisMonth())
            ->test(Analytics::class)
            ->set('tab', 'balance')
            ->assertDontSeeHtml('data-testid="cpi-estimate-note"');
    });
});

describe('CPI estimate note in the inflation calculator', function () {
    it('is shown when the current month has no published CPI', function () {
        seedCpiUpTo(now()->subMonths(2));

        Livewire::test(InflationCalculator::class)
            ->assertSeeHtml('data-testid="cpi-estimate-note"');
    });

    it('is hidden when CPI is published up to the current month', function () {
        seedCpiUpTo(now());

        Livewire::test(InflationCalculator::class)
            ->assertDontSeeHtml('data-testid="cpi-estimate-note"');
    });
});
