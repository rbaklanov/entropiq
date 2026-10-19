<?php

namespace App\Console\Commands;

use App\Services\SubscriptionRenewalService;
use Illuminate\Console\Command;

class RemindRenewalsCommand extends Command
{
    protected $signature = 'subscriptions:remind-renewals';

    protected $description = 'Email users whose subscription renews automatically within three days';

    public function handle(SubscriptionRenewalService $service): int
    {
        $this->info('Sending renewal reminders...');

        $sent = $service->sendReminders();

        $this->comment("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
