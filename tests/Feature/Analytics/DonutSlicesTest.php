<?php

use App\Livewire\Analytics;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function userWithExpenseCategories(int $count): User
{
    $user = User::factory()->onboarded()->premium()->create(['phone_verified_at' => now()]);

    for ($i = 0; $i < $count; $i++) {
        Transaction::factory()->for($user)->expense()->create([
            'category_id' => Category::factory()->expense()->create(['name' => ['ru' => "Категория {$i}", 'en' => "Category {$i}"]])->id,
            'amount' => (10 - $i) * 100000,
            'date' => now(),
        ]);
    }

    return $user;
}

describe('expense donut', function () {
    it('colours the five largest categories and greys the rest as other', function () {
        Livewire::actingAs(userWithExpenseCategories(8))
            ->test(Analytics::class)
            ->assertViewHas('chartColors', ['gray-900', 'primary-600', 'success-600', 'premium-500', 'primary-300', 'gray-300'])
            ->assertViewHas('chartData', [1000000, 900000, 800000, 700000, 600000, 500000 + 400000 + 300000])
            ->assertViewHas('chartLabels', fn (array $labels) => count($labels) === 6 && end($labels) === __('analytics.other_categories', ['count' => 3]));
    });

    it('keeps a single leftover category under its own name', function () {
        Livewire::actingAs(userWithExpenseCategories(6))
            ->test(Analytics::class)
            ->assertViewHas('chartColors', fn (array $colors) => count($colors) === 6 && end($colors) === 'gray-300')
            ->assertViewHas('chartLabels', fn (array $labels) => end($labels) === 'Категория 5');
    });

    it('shows every category in colour when there are five or fewer', function () {
        Livewire::actingAs(userWithExpenseCategories(3))
            ->test(Analytics::class)
            ->assertViewHas('chartColors', ['gray-900', 'primary-600', 'success-600']);
    });
});
