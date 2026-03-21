<?php

namespace App\Console\Commands;

use App\Services\RecurringService;
use Illuminate\Console\Command;

class ProcessRecurringCommand extends Command
{
    protected $signature = 'recurring:process';

    protected $description = 'Process due recurring rules and create transactions';

    public function handle(RecurringService $service): int
    {
        $this->info('Processing recurring rules...');

        $transactions = $service->processDueRules();

        $this->info("Created {$transactions->count()} transaction(s).");

        return self::SUCCESS;
    }
}
