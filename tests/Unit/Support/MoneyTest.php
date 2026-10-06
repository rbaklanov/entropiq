<?php

use App\Dto\AdvicePayload;
use App\Support\Money;

describe('Money::amount', function () {
    it('converts kopecks to rubles with thousands separators', function (int $kopecks, string $expected) {
        expect(Money::amount($kopecks))->toBe($expected);
    })->with([
        'whole rubles' => [150000, '1 500'],
        'thousands' => [5000000, '50 000'],
        'kopecks kept' => [3653050, '36 530,50'],
        'one kopeck' => [1, '0,01'],
        'zero' => [0, '0'],
        'millions' => [1234567800, '12 345 678'],
        'negative' => [-250000, '-2 500'],
    ]);

    it('rounds fractional kopecks', function () {
        expect(Money::amount(166666.67))->toBe('1 666,67');
    });
});

describe('AdvicePayload::basisDataInRubles', function () {
    it('converts only money fields from kopecks to rubles', function () {
        $payload = new AdvicePayload('overspending', 't', 'b', [
            'rule' => 'overspending',
            'income' => 2191830,
            'expense' => 3653050,
            'overspend' => 1461220,
            'overspend_percent' => 67,
            'category_id' => 2,
        ]);

        expect($payload->basisDataInRubles())->toBe([
            'rule' => 'overspending',
            'income' => 21918.3,
            'expense' => 36530.5,
            'overspend' => 14612.2,
            'overspend_percent' => 67,
            'category_id' => 2,
        ]);
    });

    it('keeps whole rubles as integers', function () {
        $payload = new AdvicePayload('category_spike', 't', 'b', ['current_total' => 1500000, 'avg_monthly' => 1000000]);

        expect($payload->basisDataInRubles())->toBe(['current_total' => 15000, 'avg_monthly' => 10000]);
    });

    it('does not change the stored kopecks', function () {
        $payload = new AdvicePayload('x', 't', 'b', ['amount' => 500]);

        $payload->basisDataInRubles();

        expect($payload->basisData)->toBe(['amount' => 500]);
    });

    it('lists every money field the advice detail page shows', function () {
        expect(AdvicePayload::MONEY_KEYS)->toContain('current_total', 'income', 'monthly_saving', 'potential_saving');
    });
});
