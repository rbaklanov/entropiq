<?php

use App\Mail\CpiDataStaleMail;
use App\Models\CpiValue;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function publishedTotalCpi(string $period, string $category = 'TOTAL'): void
{
    CpiValue::create(['period' => $period, 'category_code' => $category, 'value' => 100.4, 'source' => 'emiss']);
}

beforeEach(function () {
    Mail::fake();
    config(['services.admin.email' => 'admin@example.com']);
    Carbon::setTestNow('2026-10-05 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

describe('cpi:check-freshness', function () {
    it('passes when the latest month is two months behind', function () {
        publishedTotalCpi('2026-08-01');

        $this->artisan('cpi:check-freshness')
            ->expectsOutputToContain('CPI is fresh')
            ->assertSuccessful();

        Mail::assertNothingSent();
    });

    it('passes when the data is newer than required', function () {
        publishedTotalCpi('2026-09-01');

        $this->artisan('cpi:check-freshness')->assertSuccessful();
    });

    it('fails and alerts when the latest month is three months behind', function () {
        publishedTotalCpi('2026-07-01');

        $this->artisan('cpi:check-freshness')
            ->expectsOutputToContain('CPI is stale')
            ->assertFailed();

        Mail::assertSent(CpiDataStaleMail::class, function (CpiDataStaleMail $mail) {
            return $mail->hasTo('admin@example.com')
                && $mail->latestPublished->format('Y-m') === '2026-07'
                && $mail->expectedAtLeast->format('Y-m') === '2026-08';
        });
    });

    it('treats a missing CPI table as stale', function () {
        $this->artisan('cpi:check-freshness')->assertFailed();

        Mail::assertSent(CpiDataStaleMail::class, fn (CpiDataStaleMail $mail) => $mail->latestPublished === null);
    });

    it('keeps the allowed lag through the end of the month', function () {
        publishedTotalCpi('2026-08-01');
        Carbon::setTestNow('2026-10-31 23:00:00');

        $this->artisan('cpi:check-freshness')->assertSuccessful();
    });

    it('becomes stale on the first day of the month after the allowed lag', function () {
        publishedTotalCpi('2026-08-01');
        Carbon::setTestNow('2026-11-01 00:30:00');

        $this->artisan('cpi:check-freshness')->assertFailed();

        Mail::assertSent(CpiDataStaleMail::class);
    });

    it('looks only at the TOTAL category', function () {
        publishedTotalCpi('2026-09-01', 'FOOD');
        publishedTotalCpi('2026-06-01');

        $this->artisan('cpi:check-freshness')->assertFailed();
    });

    it('sends at most one alert a day', function () {
        publishedTotalCpi('2026-06-01');

        $this->artisan('cpi:check-freshness')->assertFailed();
        $this->artisan('cpi:check-freshness')->assertFailed();

        Mail::assertSentCount(1);
    });

    it('does not email with the no-alert option', function () {
        publishedTotalCpi('2026-06-01');

        $this->artisan('cpi:check-freshness --no-alert')->assertFailed();

        Mail::assertNothingSent();
    });

    it('still fails without an administrator email', function () {
        config(['services.admin.email' => null]);
        publishedTotalCpi('2026-06-01');

        $this->artisan('cpi:check-freshness')->assertFailed();

        Mail::assertNothingSent();
    });

    it('is scheduled daily after the sync', function () {
        $events = collect(app(Schedule::class)->events());

        $check = $events->first(fn ($event) => str_contains($event->command, 'cpi:check-freshness'));
        $sync = $events->first(fn ($event) => str_contains($event->command, 'cpi:sync'));

        expect($check)->not->toBeNull()
            ->and($check->expression)->toBe('0 7 * * *')
            ->and($sync->expression)->toBe('0 6 * * *');
    });

    it('renders the alert with the latest and expected months', function () {
        $mail = new CpiDataStaleMail(Carbon::create(2026, 7, 1), Carbon::create(2026, 8, 1), Carbon::create(2026, 10, 5, 7));

        $mail->assertSeeInHtml('07.2026');
        $mail->assertSeeInHtml('08.2026');
        $mail->assertSeeInHtml('php artisan cpi:sync --from=2023-01-01');
    });
});
