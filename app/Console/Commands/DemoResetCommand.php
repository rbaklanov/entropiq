<?php

namespace App\Console\Commands;

use App\Contracts\AiAdviceServiceInterface;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class DemoResetCommand extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Restore the original data of the shared demo account and generate its advice';

    public function handle(AiAdviceServiceInterface $adviceService): int
    {
        if (! User::where('phone', DemoSeeder::phone())->exists()) {
            $this->comment('Demo account not found, nothing to reset.');

            return self::SUCCESS;
        }

        $this->info('Restoring demo account data...');

        if ($this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $user = User::where('phone', DemoSeeder::phone())->firstOrFail();
        $advices = $adviceService->generateForUser($user);

        $this->comment("Demo account restored, generated {$advices->count()} advice(s).");

        return self::SUCCESS;
    }
}
