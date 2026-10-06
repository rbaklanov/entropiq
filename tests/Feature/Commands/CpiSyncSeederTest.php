<?php

use App\Contracts\CpiProviderInterface;
use App\Dto\CpiRecord;
use App\Mail\CpiSyncFailedMail;
use App\Models\CpiValue;
use Database\Seeders\CpiSeeder;
use Database\Seeders\CpiSyncSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function bindCpiProvider(Collection|Throwable $result): void
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
    $this->seed(CpiSeeder::class);
});

describe('CpiSyncSeeder', function () {
    it('replaces approximate seed values with EMISS values', function () {
        $seeded = CpiValue::where('category_code', 'TOTAL')->orderBy('period')->first();

        bindCpiProvider(collect([new CpiRecord($seeded->period->copy(), 'TOTAL', 111.11)]));

        $this->seed(CpiSyncSeeder::class);

        $stored = CpiValue::where('category_code', 'TOTAL')->where('period', $seeded->period)->first();

        expect((float) $stored->value)->toBe(111.11)
            ->and($stored->source)->toBe('emiss');
    });

    it('keeps seed data and warns when EMISS is unavailable', function () {
        $before = CpiValue::count();

        bindCpiProvider(new RuntimeException('connection timed out'));

        $this->artisan('db:seed', ['--class' => CpiSyncSeeder::class])
            ->expectsOutputToContain('unavailable')
            ->assertSuccessful();

        expect(CpiValue::count())->toBe($before)
            ->and(CpiValue::where('source', 'emiss')->count())->toBe(0);
    });

    it('does not email the administrator about a seeding failure', function () {
        Mail::fake();
        config(['services.admin.email' => 'admin@example.com']);
        bindCpiProvider(new RuntimeException('connection timed out'));

        $this->seed(CpiSyncSeeder::class);

        Mail::assertNotSent(CpiSyncFailedMail::class);
    });
});
