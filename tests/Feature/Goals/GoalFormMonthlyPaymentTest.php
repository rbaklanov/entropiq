<?php

use App\Livewire\Goals\GoalForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('divides the remaining amount by all payments left before the target date', function () {
    $this->travelTo(Carbon::parse('2026-11-21 16:30:00'));

    $component = Livewire::actingAs(User::factory()->create())
        ->test(GoalForm::class)
        ->set('targetAmount', 20_000_000)
        ->set('initialAmount', 6_000_000)
        ->set('targetDate', '2027-03-21');

    expect($component->instance()->getMonthlyPaymentProperty())->toBe(3_500_000);
});
