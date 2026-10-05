<?php

namespace App\Console\Commands;

use App\Mail\WeeklyDigestMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDigestCommand extends Command
{
    protected $signature = 'digest:send
                            {--user= : Send only to a specific user ID}';

    protected $description = 'Send weekly financial digest email to subscribed users';

    public function handle(): int
    {
        $from = now()->subWeek()->startOfDay();
        $to = now()->subDay()->endOfDay();

        $query = User::query()
            ->where(function ($q) {
                $q->whereDoesntHave('notificationSetting')
                    ->orWhereHas('notificationSetting', fn ($sub) => $sub->where('email_weekly', true));
            });

        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        [$recipients, $withoutEmail] = $query->get()->partition(
            fn (User $user) => $user->hasVerifiedEmail()
        );

        if ($withoutEmail->isNotEmpty()) {
            $this->warn("Skipped {$withoutEmail->count()} user(s) without a verified email.");
        }

        if ($recipients->isEmpty()) {
            $this->info(__('digest.no_recipients'));

            return self::SUCCESS;
        }

        $this->info("Sending digest to {$recipients->count()} user(s)...");

        foreach ($recipients as $user) {
            $this->info("Sending to user #{$user->id}...");

            Mail::to($user->email)->send(new WeeklyDigestMail($user, $from, $to));
        }

        $this->comment("Sent {$recipients->count()} digest(s).");

        return self::SUCCESS;
    }
}
