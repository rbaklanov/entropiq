<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function exportUser(bool $premium = false): array
{
    $user = $premium
        ? User::factory()->premium()->create(['phone_verified_at' => now()])
        : User::factory()->create(['phone_verified_at' => now()]);

    $token = $user->createToken('test')->plainTextToken;

    return [$user, $token];
}

function exportHeaders(string $token): array
{
    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
}

describe('GET /analytics/export (web)', function () {
    it('requires premium subscription', function () {
        $user = User::factory()->create(['phone_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/analytics/export')
            ->assertRedirect(route('settings.subscription'));
    });

    it('downloads CSV for premium user', function () {
        $user = User::factory()->premium()->create(['phone_verified_at' => now()]);
        $category = Category::factory()->expense()->create();

        Transaction::factory()->for($user)->expense()->create([
            'category_id' => $category->id,
            'amount' => 15000,
            'date' => now()->subDays(1),
        ]);

        $response = $this->actingAs($user)
            ->get('/analytics/export')
            ->assertOk();

        expect($response->headers->get('Content-Type'))->toContain('text/csv');
    });

    it('supports date filters', function () {
        $user = User::factory()->premium()->create(['phone_verified_at' => now()]);
        $category = Category::factory()->expense()->create();

        Transaction::factory()->for($user)->expense()->create([
            'category_id' => $category->id,
            'amount' => 10000,
            'date' => '2026-01-15',
        ]);

        Transaction::factory()->for($user)->expense()->create([
            'category_id' => $category->id,
            'amount' => 20000,
            'date' => '2026-03-15',
        ]);

        $response = $this->actingAs($user)
            ->get('/analytics/export?from=2026-03-01&to=2026-03-31')
            ->assertOk();

        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));

        expect(count($lines))->toBe(2);
    });
});

describe('export:transactions command', function () {
    it('requires --user option', function () {
        $this->artisan('export:transactions')
            ->assertFailed()
            ->expectsOutputToContain('--user option is required');
    });

    it('fails for non-existent user', function () {
        $this->artisan('export:transactions --user=999')
            ->assertFailed()
            ->expectsOutputToContain('not found');
    });

    it('exports CSV to file', function () {
        $user = User::factory()->create();
        $category = Category::factory()->expense()->create();

        Transaction::factory()->for($user)->expense()->create([
            'category_id' => $category->id,
            'amount' => 5000,
            'date' => now(),
        ]);

        $output = storage_path('app/exports/test_export.csv');

        $this->artisan("export:transactions --user={$user->id} --output={$output}")
            ->assertSuccessful()
            ->expectsOutputToContain('Exported to');

        expect(file_exists($output))->toBeTrue();

        $content = file_get_contents($output);
        expect($content)->toContain('50,00');

        @unlink($output);
    });
});
