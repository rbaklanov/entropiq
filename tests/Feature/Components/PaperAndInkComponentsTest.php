<?php

use App\Livewire\Advice\AdviceDetail;
use App\Models\AiAdvice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('balance-card', function () {
    it('offers the nominal and real toggle when inflation took something', function () {
        $html = Blade::render('<x-balance-card :nominalBalance="3755000" :realBalance="3726000" :inflationLoss="29000" />');

        expect($html)
            ->toContain('moneyToggle({ nominal: 3755000, real: 3726000 })')
            ->toContain(__('dashboard.show_nominal'))
            ->toContain(__('dashboard.show_real'))
            ->toContain('+37 550')
            ->toContain('−290');
    });

    it('shows a plain balance without the toggle when there is no loss', function () {
        $html = Blade::render('<x-balance-card :nominalBalance="0" :realBalance="0" :inflationLoss="0" />');

        expect($html)
            ->not->toContain('moneyToggle')
            ->toContain(__('dashboard.balance_period'));
    });
});

describe('advice basis', function () {
    beforeEach(function () {
        $this->user = User::factory()->onboarded()->premium()->create(['phone_verified_at' => now()]);
    });

    it('explains a category spike as this month against the three month average', function () {
        $advice = AiAdvice::factory()->for($this->user)->create([
            'basis_data' => [
                'rule' => 'category_spike',
                'category_id' => 1,
                'category_name' => 'Продукты',
                'current_total' => 2430000,
                'avg_monthly' => 2170000,
                'growth_percent' => 12,
            ],
        ]);

        Livewire::actingAs($this->user)->test(AdviceDetail::class, ['advice' => $advice])
            ->assertSee(__('advice.spike_basis_title', ['percent' => 12]))
            ->assertSee('«Продукты»', false)
            ->assertSee('24 300 ₽')
            ->assertSee('21 700 ₽')
            ->assertSee('+12%')
            ->assertDontSee(__('advice.basis'));
    });

    it('falls back to the list of figures for other rules', function () {
        $advice = AiAdvice::factory()->for($this->user)->create([
            'basis_data' => ['rule' => 'overspending', 'income' => 5000000, 'expense' => 5400000],
        ]);

        Livewire::actingAs($this->user)->test(AdviceDetail::class, ['advice' => $advice])
            ->assertSee(__('advice.basis'))
            ->assertSee('50 000 ₽')
            ->assertDontSee(__('advice.spike_basis_title', ['percent' => '']));
    });
});
