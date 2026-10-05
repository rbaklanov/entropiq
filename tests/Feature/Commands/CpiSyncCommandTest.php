<?php

use App\Contracts\CpiProviderInterface;
use App\Dto\CpiRecord;
use App\Models\CpiCategory;
use App\Models\CpiValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

function fakeCpiProvider(Collection|Throwable $result): void
{
    app()->bind(CpiProviderInterface::class, fn () => new class($result) implements CpiProviderInterface
    {
        public function __construct(private readonly Collection|Throwable $result) {}

        public function fetch(Carbon $from, Carbon $to): Collection
        {
            if ($this->result instanceof Throwable) {
                throw $this->result;
            }

            return $this->result;
        }
    });
}

beforeEach(function () {
    CpiCategory::factory()->create(['code' => 'TOTAL']);
    CpiCategory::factory()->create(['code' => 'FOOD']);
});

describe('cpi:sync', function () {
    it('stores fetched values with the emiss source', function () {
        fakeCpiProvider(collect([
            new CpiRecord(Carbon::create(2026, 8, 1), 'TOTAL', 100.4),
            new CpiRecord(Carbon::create(2026, 8, 1), 'FOOD', 100.9),
        ]));

        $this->artisan('cpi:sync')->assertSuccessful();

        $total = CpiValue::where('category_code', 'TOTAL')->first();

        expect(CpiValue::count())->toBe(2)
            ->and($total->source)->toBe('emiss')
            ->and((float) $total->value)->toBe(100.4)
            ->and($total->period->toDateString())->toBe('2026-08-01');
    });

    it('overwrites existing values for the same month and category', function () {
        CpiValue::create(['period' => '2026-08-01', 'category_code' => 'TOTAL', 'value' => 100.0, 'source' => 'rosstat']);

        fakeCpiProvider(collect([new CpiRecord(Carbon::create(2026, 8, 1), 'TOTAL', 100.4)]));

        $this->artisan('cpi:sync')->assertSuccessful();

        $values = CpiValue::where('category_code', 'TOTAL')->get();

        expect($values)->toHaveCount(1)
            ->and((float) $values->first()->value)->toBe(100.4)
            ->and($values->first()->source)->toBe('emiss');
    });

    it('is idempotent', function () {
        fakeCpiProvider(collect([new CpiRecord(Carbon::create(2026, 8, 1), 'TOTAL', 100.4)]));

        $this->artisan('cpi:sync')->assertSuccessful();
        $this->artisan('cpi:sync')->assertSuccessful();

        expect(CpiValue::count())->toBe(1);
    });

    it('skips values outside the plausible range', function () {
        fakeCpiProvider(collect([
            new CpiRecord(Carbon::create(2026, 8, 1), 'TOTAL', 100.4),
            new CpiRecord(Carbon::create(2026, 8, 1), 'FOOD', 0.0),
        ]));

        $this->artisan('cpi:sync')->assertSuccessful();

        expect(CpiValue::count())->toBe(1)
            ->and(CpiValue::where('category_code', 'FOOD')->exists())->toBeFalse();
    });

    it('keeps real seasonal drops below 90', function () {
        fakeCpiProvider(collect([new CpiRecord(Carbon::create(2025, 8, 1), 'FOOD', 89.98)]));

        $this->artisan('cpi:sync')->assertSuccessful();

        expect((float) CpiValue::where('category_code', 'FOOD')->value('value'))->toBe(89.98);
    });

    it('skips categories that are not loaded yet', function () {
        fakeCpiProvider(collect([
            new CpiRecord(Carbon::create(2026, 8, 1), 'TOTAL', 100.4),
            new CpiRecord(Carbon::create(2026, 8, 1), 'SERVICES', 100.2),
        ]));

        $this->artisan('cpi:sync')
            ->expectsOutputToContain('cpi:import --categories')
            ->assertSuccessful();

        expect(CpiValue::where('category_code', 'SERVICES')->exists())->toBeFalse();
    });

    it('fails and keeps stored data when EMISS is unavailable', function () {
        CpiValue::create(['period' => '2026-07-01', 'category_code' => 'TOTAL', 'value' => 100.2, 'source' => 'emiss']);

        fakeCpiProvider(new RuntimeException('connection timed out'));

        $this->artisan('cpi:sync')
            ->expectsOutputToContain('connection timed out')
            ->assertFailed();

        expect(CpiValue::count())->toBe(1);
    });

    it('fails when EMISS returns nothing for the period', function () {
        fakeCpiProvider(collect());

        $this->artisan('cpi:sync')
            ->expectsOutputToContain('returned no data')
            ->assertFailed();
    });

    it('passes the requested period to the provider', function () {
        $provider = Mockery::mock(CpiProviderInterface::class);
        $provider->shouldReceive('fetch')
            ->once()
            ->withArgs(fn (Carbon $from, Carbon $to) => $from->toDateString() === '2023-01-01' && $to->toDateString() === '2023-12-31')
            ->andReturn(collect([new CpiRecord(Carbon::create(2023, 3, 1), 'TOTAL', 100.4)]));
        app()->instance(CpiProviderInterface::class, $provider);

        $this->artisan('cpi:sync --from=2023-01-01 --to=2023-12-31')->assertSuccessful();
    });
});
