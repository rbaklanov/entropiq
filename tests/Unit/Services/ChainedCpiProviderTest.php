<?php

use App\Contracts\CpiProviderInterface;
use App\Dto\CpiRecord;
use App\Services\ChainedCpiProvider;
use App\Services\EmissCpiService;
use App\Services\RosstatCpiService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

function stubCpiProvider(Collection|Throwable $result): CpiProviderInterface
{
    return new class($result) implements CpiProviderInterface
    {
        public int $calls = 0;

        public function __construct(private readonly Collection|Throwable $result) {}

        public function fetch(Carbon $from, Carbon $to): Collection
        {
            $this->calls++;

            if ($this->result instanceof Throwable) {
                throw $this->result;
            }

            return $this->result;
        }
    };
}

function cpiRecords(): Collection
{
    return collect([new CpiRecord(Carbon::create(2026, 8, 1), 'TOTAL', 99.92)]);
}

describe('ChainedCpiProvider', function () {
    it('returns the first source result without asking the next one', function () {
        $first = stubCpiProvider(cpiRecords());
        $second = stubCpiProvider(cpiRecords());

        $result = (new ChainedCpiProvider([$first, $second]))->fetch(Carbon::now(), Carbon::now());

        expect($result)->toHaveCount(1)
            ->and($second->calls)->toBe(0);
    });

    it('falls back to the next source when the first one fails', function () {
        $second = stubCpiProvider(cpiRecords());

        $result = (new ChainedCpiProvider([stubCpiProvider(new RuntimeException('403')), $second]))->fetch(Carbon::now(), Carbon::now());

        expect($result)->toHaveCount(1)
            ->and($second->calls)->toBe(1);
    });

    it('falls back to the next source when the first one returns nothing', function () {
        $result = (new ChainedCpiProvider([stubCpiProvider(collect()), stubCpiProvider(cpiRecords())]))->fetch(Carbon::now(), Carbon::now());

        expect($result)->toHaveCount(1);
    });

    it('throws the last failure when every source fails', function () {
        (new ChainedCpiProvider([
            stubCpiProvider(new RuntimeException('first')),
            stubCpiProvider(new RuntimeException('second')),
        ]))->fetch(Carbon::now(), Carbon::now());
    })->throws(RuntimeException::class, 'second');

    it('returns nothing when every source is empty', function () {
        $result = (new ChainedCpiProvider([stubCpiProvider(collect()), stubCpiProvider(collect())]))->fetch(Carbon::now(), Carbon::now());

        expect($result)->toBeEmpty();
    });
});

describe('CPI provider selection', function () {
    it('uses EMISS only when configured', function () {
        config(['services.cpi.provider' => 'emiss']);

        expect(app(CpiProviderInterface::class))->toBeInstanceOf(EmissCpiService::class);
    });

    it('uses Rosstat only when configured', function () {
        config(['services.cpi.provider' => 'rosstat']);

        expect(app(CpiProviderInterface::class))->toBeInstanceOf(RosstatCpiService::class);
    });

    it('chains both sources by default', function () {
        config(['services.cpi.provider' => 'auto']);

        expect(app(CpiProviderInterface::class))->toBeInstanceOf(ChainedCpiProvider::class);
    });
});
