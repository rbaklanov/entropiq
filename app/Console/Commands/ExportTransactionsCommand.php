<?php

namespace App\Console\Commands;

use App\Contracts\ExportServiceInterface;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ExportTransactionsCommand extends Command
{
    protected $signature = 'export:transactions
                            {--user= : User ID (required)}
                            {--from= : Start date (Y-m-d)}
                            {--to= : End date (Y-m-d)}
                            {--output= : Output file path}';

    protected $description = 'Export user transactions to CSV file';

    public function handle(ExportServiceInterface $exportService): int
    {
        $userId = $this->option('user');

        if (! $userId) {
            $this->error('The --user option is required.');

            return self::FAILURE;
        }

        $user = User::find($userId);

        if (! $user) {
            $this->error("User #{$userId} not found.");

            return self::FAILURE;
        }

        $filters = [];

        if ($from = $this->option('from')) {
            $filters['from'] = Carbon::parse($from);
        }

        if ($to = $this->option('to')) {
            $filters['to'] = Carbon::parse($to);
        }

        $output = $this->option('output') ?? storage_path("app/exports/transactions_{$userId}.csv");
        $dir = dirname($output);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->info("Exporting transactions for user #{$userId}...");

        $response = $exportService->transactionsToCsv($user, $filters);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        file_put_contents($output, $content);

        $this->comment("Exported to {$output}");

        return self::SUCCESS;
    }
}
