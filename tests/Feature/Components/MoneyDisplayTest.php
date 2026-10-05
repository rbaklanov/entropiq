<?php

use Illuminate\Support\Facades\Blade;

describe('money-display', function () {
    it('shows minus for a negative balance with signs enabled', function () {
        $html = Blade::render('<x-money-display :amount="-150000" :showSign="true" />');

        expect($html)->toContain('−1 500');
    });

    it('shows plus for a positive balance with signs enabled', function () {
        $html = Blade::render('<x-money-display :amount="150000" :showSign="true" />');

        expect($html)->toContain('+1 500');
    });

    it('shows no sign for a zero balance', function () {
        $html = Blade::render('<x-money-display :amount="0" :showSign="true" />');

        expect($html)->not->toContain('+0')->not->toContain('−0');
    });

    it('keeps the type based sign for income and expense', function () {
        expect(Blade::render('<x-money-display :amount="50000" type="expense" :showSign="true" />'))->toContain('−500')
            ->and(Blade::render('<x-money-display :amount="50000" type="income" :showSign="true" />'))->toContain('+500');
    });
});

describe('premium price copy', function () {
    it('matches the subscription price in landing and terms', function () {
        foreach (['ru', 'en'] as $locale) {
            expect(__('landing.pricing_premium_price', [], $locale))->toContain('99')
                ->and(__('legal.terms_plans_premium', [], $locale))->toContain('99')->not->toContain('299');
        }
    });
});
