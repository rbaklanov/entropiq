<?php

use App\Models\CpiCategory;
use App\Models\CpiValue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('cpi:import --categories', function () {
    it('imports categories from default JSON file', function () {
        $this->artisan('cpi:import', ['--categories' => true])
            ->assertSuccessful();

        expect(CpiCategory::count())->toBeGreaterThan(0);
        expect(CpiCategory::where('code', 'TOTAL')->exists())->toBeTrue();
        expect(CpiCategory::where('code', 'FOOD')->exists())->toBeTrue();
    });

    it('sets parent_code for subcategories', function () {
        $this->artisan('cpi:import', ['--categories' => true])
            ->assertSuccessful();

        $food = CpiCategory::where('code', 'FOOD')->first();

        expect($food->parent_code)->toBe('TOTAL');
    });

    it('is idempotent — re-import does not duplicate records', function () {
        $this->artisan('cpi:import', ['--categories' => true])->assertSuccessful();
        $firstCount = CpiCategory::count();

        $this->artisan('cpi:import', ['--categories' => true])->assertSuccessful();
        $secondCount = CpiCategory::count();

        expect($secondCount)->toBe($firstCount);
    });
});

describe('cpi:import values', function () {
    it('imports values from default JSON file', function () {
        $this->artisan('cpi:import')
            ->assertSuccessful();

        expect(CpiValue::count())->toBeGreaterThan(0);
    });

    it('is idempotent — re-import does not duplicate records', function () {
        $this->artisan('cpi:import')->assertSuccessful();
        $firstCount = CpiValue::count();

        $this->artisan('cpi:import')->assertSuccessful();
        $secondCount = CpiValue::count();

        expect($secondCount)->toBe($firstCount);
    });

    it('filters records with --from option', function () {
        $this->artisan('cpi:import', ['--from' => '2025-01-01'])
            ->assertSuccessful();

        $oldest = CpiValue::orderBy('period')->first();

        expect($oldest->period->gte(now()->parse('2025-01-01')))->toBeTrue();
    });

    it('filters records with --to option', function () {
        $this->artisan('cpi:import', ['--to' => '2023-06-01'])
            ->assertSuccessful();

        $newest = CpiValue::orderByDesc('period')->first();

        expect($newest->period->lte(now()->parse('2023-06-01')))->toBeTrue();
    });

    it('fails when file does not exist', function () {
        $this->artisan('cpi:import', ['file' => '/tmp/nonexistent.json'])
            ->assertFailed();
    });

    it('fails when JSON has invalid format', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'cpi_test_');
        file_put_contents($tmpFile, json_encode(['invalid' => 'structure']));

        $this->artisan('cpi:import', ['file' => $tmpFile])
            ->assertFailed();

        unlink($tmpFile);
    });

    it('imports from a custom file path', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'cpi_test_');
        file_put_contents($tmpFile, json_encode([
            'data' => [
                [
                    'period' => '2025-01-01',
                    'category_code' => 'TOTAL',
                    'value' => 100.84,
                    'source' => 'test',
                ],
                [
                    'period' => '2025-02-01',
                    'category_code' => 'TOTAL',
                    'value' => 100.46,
                    'source' => 'test',
                ],
            ],
        ]));

        $this->artisan('cpi:import', ['file' => $tmpFile])
            ->assertSuccessful();

        expect(CpiValue::count())->toBe(2);
        expect(CpiValue::where('value', 100.84)->exists())->toBeTrue();

        unlink($tmpFile);
    });

    it('skips records with out-of-range values', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'cpi_test_');
        file_put_contents($tmpFile, json_encode([
            'data' => [
                [
                    'period' => '2025-01-01',
                    'category_code' => 'TOTAL',
                    'value' => 100.50,
                    'source' => 'test',
                ],
                [
                    'period' => '2025-02-01',
                    'category_code' => 'TOTAL',
                    'value' => 50.00,
                    'source' => 'test',
                ],
                [
                    'period' => '2025-03-01',
                    'category_code' => 'TOTAL',
                    'value' => 200.00,
                    'source' => 'test',
                ],
            ],
        ]));

        $this->artisan('cpi:import', ['file' => $tmpFile])
            ->assertSuccessful();

        expect(CpiValue::count())->toBe(1);
        expect(CpiValue::first()->value)->toBe('100.50');

        unlink($tmpFile);
    });

    it('skips records with missing required fields', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'cpi_test_');
        file_put_contents($tmpFile, json_encode([
            'data' => [
                ['period' => '2025-01-01', 'category_code' => 'TOTAL', 'value' => 100.50],
                ['period' => '2025-02-01', 'value' => 100.50],
                ['category_code' => 'TOTAL', 'value' => 100.50],
                ['period' => '2025-04-01', 'category_code' => 'TOTAL'],
            ],
        ]));

        $this->artisan('cpi:import', ['file' => $tmpFile])
            ->assertSuccessful();

        expect(CpiValue::count())->toBe(1);

        unlink($tmpFile);
    });
});
