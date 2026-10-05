<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
        $user = User::factory()->onboarded()->create(['phone_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/analytics/export')
            ->assertRedirect(route('settings.subscription'));
    });

    it('downloads CSV for premium user', function () {
        $user = User::factory()->premium()->onboarded()->create(['phone_verified_at' => now()]);
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
        $user = User::factory()->premium()->onboarded()->create(['phone_verified_at' => now()]);
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

describe('GET /analytics/export formats', function () {
    beforeEach(function () {
        $this->user = User::factory()->premium()->onboarded()->create(['phone_verified_at' => now()]);

        Transaction::factory()->for($this->user)->expense()->create([
            'category_id' => Category::factory()->expense()->create()->id,
            'amount' => 150000,
            'date' => '2026-03-15',
            'comment' => 'Аренда',
        ]);
    });

    it('downloads a PDF document', function () {
        $response = $this->actingAs($this->user)
            ->get('/analytics/export?format=pdf')
            ->assertOk();

        expect($response->headers->get('Content-Type'))->toContain('application/pdf')
            ->and($response->streamedContent())->toStartWith('%PDF');
    });

    it('downloads an Excel workbook with the transaction rows', function () {
        $response = $this->actingAs($this->user)
            ->get('/analytics/export?format=xlsx&from=2026-03-01&to=2026-03-31')
            ->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->streamedContent());

        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        @unlink($path);

        expect($rows)->toHaveCount(2)
            ->and($rows[0][0])->toBe(__('export.date'))
            ->and($rows[1][2])->not->toBeEmpty()
            ->and($rows[1][5])->toBe('Аренда');
    });

    it('applies the date filter to the Excel workbook', function () {
        $response = $this->actingAs($this->user)
            ->get('/analytics/export?format=xlsx&from=2026-04-01&to=2026-04-30')
            ->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->streamedContent());

        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        @unlink($path);

        expect($rows)->toHaveCount(1);
    });

    it('rejects an unknown format', function () {
        $this->actingAs($this->user)
            ->getJson('/analytics/export?format=docx')
            ->assertUnprocessable();
    });

    it('does not export other users transactions', function () {
        $other = User::factory()->premium()->onboarded()->create(['phone_verified_at' => now()]);

        $response = $this->actingAs($other)->get('/analytics/export?format=csv')->assertOk();

        expect(trim($response->streamedContent()))->not->toContain('Аренда');
    });

    it('requires premium for PDF and Excel', function () {
        $free = User::factory()->onboarded()->create(['phone_verified_at' => now()]);

        $this->actingAs($free)->get('/analytics/export?format=pdf')->assertRedirect(route('settings.subscription'));
        $this->actingAs($free)->get('/analytics/export?format=xlsx')->assertRedirect(route('settings.subscription'));
    });
});

describe('export buttons on the analytics page', function () {
    it('links free users to the subscription page with a lock', function () {
        $free = User::factory()->onboarded()->create(['phone_verified_at' => now()]);

        $this->actingAs($free)
            ->get(route('analytics'))
            ->assertOk()
            ->assertSee('🔒')
            ->assertSee(route('settings.subscription'))
            ->assertDontSee('format=pdf', false);
    });

    it('links premium users to the export without a lock', function () {
        $premium = User::factory()->premium()->onboarded()->create(['phone_verified_at' => now()]);

        $this->actingAs($premium)
            ->get(route('analytics'))
            ->assertOk()
            ->assertSee('format=pdf', false)
            ->assertSee('format=xlsx', false)
            ->assertDontSee('🔒');
    });
});
