<?php

use App\Livewire\Advice\AdviceDetail;
use App\Models\AiAdvice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('advice rating on the detail page', function () {
    beforeEach(function () {
        $this->user = User::factory()->onboarded()->premium()->create(['phone_verified_at' => now()]);
        $this->advice = AiAdvice::factory()->for($this->user)->create(['basis_data' => ['rule' => 'overspending']]);
    });

    it('saves a helpful or unhelpful rating', function (int $rating) {
        Livewire::actingAs($this->user)->test(AdviceDetail::class, ['advice' => $this->advice])
            ->call('rate', $rating);

        expect($this->advice->fresh()->rating)->toBe($rating);
    })->with([
        'helpful' => AiAdvice::RATING_HELPFUL,
        'unhelpful' => AiAdvice::RATING_UNHELPFUL,
    ]);

    it('ignores ratings outside the allowed values', function () {
        Livewire::actingAs($this->user)->test(AdviceDetail::class, ['advice' => $this->advice])
            ->call('rate', 5);

        expect($this->advice->fresh()->rating)->toBeNull();
    });
});
