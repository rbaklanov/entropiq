<?php

namespace App\Console\Commands;

use App\Contracts\AiAdviceServiceInterface;
use App\Models\User;
use Illuminate\Console\Command;

class AdviceGenerateCommand extends Command
{
    protected $signature = 'advice:generate
                            {--user= : Generate for a specific user ID}';

    protected $description = 'Generate AI advice for users based on financial analysis rules';

    public function handle(AiAdviceServiceInterface $adviceService): int
    {
        $query = User::query();

        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        $users = $query->get();
        $totalAdvices = 0;

        $this->info("Processing {$users->count()} user(s)...");

        foreach ($users as $user) {
            $this->info("Generating advice for user #{$user->id}...");

            $advices = $adviceService->generateForUser($user);
            $totalAdvices += $advices->count();

            foreach ($advices as $advice) {
                $this->line("  — {$advice->title}");
            }

            if ($advices->isEmpty()) {
                $this->line('  — no rules triggered');
            }
        }

        $this->comment("Generated {$totalAdvices} advice(s) for {$users->count()} user(s).");

        return self::SUCCESS;
    }
}
