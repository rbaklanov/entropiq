<?php

use App\Mail\WeeklyDigestMail;
use App\Models\Category;
use App\Models\NotificationSetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

describe('digest:send command', function () {
    it('sends digest to users with email_weekly enabled', function () {
        Mail::fake();

        $user = User::factory()->create();
        NotificationSetting::factory()->for($user)->create(['email_weekly' => true]);

        $category = Category::factory()->expense()->create();
        Transaction::factory()->for($user)->expense()->create([
            'category_id' => $category->id,
            'date' => now()->subDays(3),
        ]);

        $this->artisan('digest:send')
            ->assertSuccessful()
            ->expectsOutputToContain('Sending digest to 1 user(s)');

        Mail::assertSent(WeeklyDigestMail::class, fn ($mail) => $mail->user->id === $user->id);
    });

    it('skips users with email_weekly disabled', function () {
        Mail::fake();

        $user = User::factory()->create();
        NotificationSetting::factory()->for($user)->allDisabled()->create();

        $this->artisan('digest:send')
            ->assertSuccessful();

        Mail::assertNothingSent();
    });

    it('sends to users without notification settings (defaults are enabled)', function () {
        Mail::fake();

        $user = User::factory()->create();

        $this->artisan('digest:send')
            ->assertSuccessful()
            ->expectsOutputToContain('Sending digest to 1 user(s)');

        Mail::assertSent(WeeklyDigestMail::class, fn ($mail) => $mail->user->id === $user->id);
    });

    it('sends only to specific user with --user option', function () {
        Mail::fake();

        $target = User::factory()->create();
        NotificationSetting::factory()->for($target)->create(['email_weekly' => true]);

        $other = User::factory()->create();
        NotificationSetting::factory()->for($other)->create(['email_weekly' => true]);

        $this->artisan("digest:send --user={$target->id}")
            ->assertSuccessful()
            ->expectsOutputToContain('Sending digest to 1 user(s)');

        Mail::assertSent(WeeklyDigestMail::class, 1);
        Mail::assertSent(WeeklyDigestMail::class, fn ($mail) => $mail->user->id === $target->id);
    });

    it('includes correct digest data', function () {
        Mail::fake();

        $user = User::factory()->create();
        NotificationSetting::factory()->for($user)->create(['email_weekly' => true]);

        $incomeCategory = Category::factory()->income()->create();
        $expenseCategory = Category::factory()->expense()->create();

        Transaction::factory()->for($user)->income()->create([
            'category_id' => $incomeCategory->id,
            'amount' => 100000,
            'date' => now()->subDays(3),
        ]);

        Transaction::factory()->for($user)->expense()->create([
            'category_id' => $expenseCategory->id,
            'amount' => 30000,
            'date' => now()->subDays(2),
        ]);

        $this->artisan('digest:send')->assertSuccessful();

        Mail::assertSent(WeeklyDigestMail::class, function ($mail) {
            expect($mail->digest['total_income'])->toBe(100000);
            expect($mail->digest['total_expense'])->toBe(30000);
            expect($mail->digest['balance'])->toBe(70000);
            expect($mail->digest['transactions_count'])->toBe(2);

            return true;
        });
    });
});
