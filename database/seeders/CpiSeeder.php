<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class CpiSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('cpi:import', [
            '--categories' => true,
        ]);

        $this->command->info(Artisan::output());

        Artisan::call('cpi:import');

        $this->command->info(Artisan::output());
    }
}
