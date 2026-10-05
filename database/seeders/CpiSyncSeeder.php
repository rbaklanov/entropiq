<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class CpiSyncSeeder extends Seeder
{
    private const FIRST_SEEDED_MONTH = '2023-01-01';

    public function run(): void
    {
        try {
            $exitCode = Artisan::call('cpi:sync', [
                '--from' => self::FIRST_SEEDED_MONTH,
                '--no-alert' => true,
            ]);
        } catch (Throwable $exception) {
            $this->warnAboutFallback($exception->getMessage());

            return;
        }

        if ($exitCode !== 0) {
            $this->warnAboutFallback(trim(Artisan::output()));

            return;
        }

        $this->command->info(Artisan::output());
    }

    private function warnAboutFallback(string $details): void
    {
        $this->command->warn('EMISS is unavailable, CPI stays on approximate seed data (source rosstat). Run `cpi:sync --from=2023-01-01` later.');
        $this->command->line($details);
    }
}
