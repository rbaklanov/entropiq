<?php

namespace App\Console\Commands;

use App\Services\SubscriptionRenewalService;
use Illuminate\Console\Command;

class RenewSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:renew';

    protected $description = 'Charge renewals for ended subscriptions with auto renewal and expire the rest';

    public function handle(SubscriptionRenewalService $service): int
    {
        $this->info('Processing ended subscriptions...');

        $summary = $service->processDue();

        $this->comment("Charged {$summary['charged']}, expired {$summary['expired']}, waiting for payment result {$summary['waiting']}.");

        return self::SUCCESS;
    }
}
