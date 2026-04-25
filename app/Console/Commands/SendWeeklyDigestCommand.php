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

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->info(__('digest.no_recipients'));

            return self::SUCCESS;
        }

        $this->info("Sending digest to {$users->count()} user(s)...");

        foreach ($users as $user) {
            $this->info("Sending to user #{$user->id} ({$user->phone})...");

            $email = $user->email ?? "user{$user->id}@entropiq.local";

            Mail::to($email)->send(new WeeklyDigestMail($user, $from, $to));
        }

        $this->comment("Sent {$users->count()} digest(s).");

        return self::SUCCESS;
    }
}
