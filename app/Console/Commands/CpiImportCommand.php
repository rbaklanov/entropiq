<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\CpiCategory;
use App\Models\CpiValue;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CpiImportCommand extends Command
{
    protected $signature = 'cpi:import
        {file? : Path to JSON file with CPI values (default: database/data/cpi_values.json)}
        {--categories : Also import CPI categories from database/data/cpi_categories.json}
        {--from= : Import only records from this date (YYYY-MM-DD)}
        {--to= : Import only records up to this date (YYYY-MM-DD)}';

    protected $description = 'Import CPI (Consumer Price Index) data from a JSON file';

    private const VALID_VALUE_MIN = 90.0;

    private const VALID_VALUE_MAX = 130.0;

    public function handle(): int
    {
        if ($this->option('categories')) {
            $this->importCategories();
        }

        return $this->importValues();
    }

    private function importCategories(): void
    {
        $path = database_path('data/cpi_categories.json');

        if (! file_exists($path)) {
            $this->error("Categories file not found: {$path}");

            return;
        }

        /** @var array<int, array{code: string, name: string, parent_code: ?string, app_category_ru: ?string}> $categories */
        $categories = json_decode(file_get_contents($path), true);
        $imported = 0;
        $skipped = 0;

        foreach ($categories as $item) {
            $mappingId = null;

            if ($item['app_category_ru']) {
                $appCategory = Category::where('name->ru', $item['app_category_ru'])
                    ->where('is_system', true)
                    ->first();
                $mappingId = $appCategory?->id;
            }

            CpiCategory::updateOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'parent_code' => $item['parent_code'],
                    'mapping_to_app_category_id' => $mappingId,
                ],
            );

            $imported++;
        }

        $this->info("CPI categories: {$imported} imported, {$skipped} skipped.");
    }

    private function importValues(): int
    {
        $file = $this->argument('file') ?? database_path('data/cpi_values.json');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $json = json_decode(file_get_contents($file), true);

        if (! $json || ! isset($json['data'])) {
            $this->error('Invalid JSON format. Expected {"data": [...]}');

            return self::FAILURE;
        }

        /** @var array<int, array{period: string, category_code: string, value: float, source?: string}> $records */
        $records = $json['data'];

        $from = $this->option('from') ? Carbon::parse($this->option('from')) : null;
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : null;

        $imported = 0;
        $skipped = 0;
        $invalid = 0;

        $this->output->progressStart(count($records));

        foreach ($records as $record) {
            $this->output->progressAdvance();

            if (! $this->validateRecord($record)) {
                $invalid++;

                continue;
            }

            $period = Carbon::parse($record['period']);

            if ($from && $period->lt($from)) {
                $skipped++;

                continue;
            }

            if ($to && $period->gt($to)) {
                $skipped++;

                continue;
            }

            CpiValue::updateOrCreate(
                [
                    'period' => $period->toDateString(),
                    'category_code' => $record['category_code'],
                ],
                [
                    'value' => $record['value'],
                    'source' => $record['source'] ?? 'rosstat',
                ],
            );

            $imported++;
        }

        $this->output->progressFinish();
        $this->info("CPI values: {$imported} imported, {$skipped} skipped, {$invalid} invalid.");

        if ($invalid > 0) {
            $this->warn('Some records were skipped due to validation errors. Check the data file.');
        }

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $record */
    private function validateRecord(array $record): bool
    {
        if (empty($record['period']) || empty($record['category_code']) || ! isset($record['value'])) {
            return false;
        }

        $value = (float) $record['value'];

        if ($value < self::VALID_VALUE_MIN || $value > self::VALID_VALUE_MAX) {
            $this->warn("Value out of range ({$value}) for {$record['period']} / {$record['category_code']}");

            return false;
        }

        return true;
    }
}
