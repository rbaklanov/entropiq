<?php

namespace App\Console\Commands;

use App\Contracts\AiAdviceServiceInterface;
use App\Models\AiAdvice;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AdviceStatsCommand extends Command
{
    protected $signature = 'advice:stats
                            {--days= : Only count advice generated in the last N days}';

    protected $description = 'Show how users rate advice, grouped by rule';

    public function handle(AiAdviceServiceInterface $adviceService): int
    {
        $days = $this->option('days');

        if ($days !== null && (! ctype_digit($days) || (int) $days < 1)) {
            $this->error('The --days option must be a positive integer.');

            return self::INVALID;
        }

        $advices = AiAdvice::query()
            ->when($days !== null, fn ($query) => $query->where('generated_at', '>=', Carbon::today()->subDays((int) $days - 1)))
            ->get(['user_id', 'basis_data', 'rating']);

        $period = $days === null ? 'all time' : "the last {$days} day(s)";
        $this->info("Advice ratings for {$period}...");

        if ($advices->isEmpty()) {
            $this->comment('No advice found.');

            return self::SUCCESS;
        }

        $rows = $advices
            ->groupBy(fn (AiAdvice $advice): string => (string) ($advice->basis_data['rule'] ?? 'unknown'))
            ->sortKeys()
            ->map(fn (Collection $ruleAdvices, string $ruleKey) => $this->row($ruleKey, $ruleAdvices, $adviceService))
            ->values();

        $this->table(['Rule', 'Advices', 'Rated', 'Helpful', 'Unhelpful', 'Helpful share', 'Muted users'], $rows);

        $rated = $advices->whereIn('rating', [AiAdvice::RATING_HELPFUL, AiAdvice::RATING_UNHELPFUL])->count();
        $this->comment("Rated {$rated} of {$advices->count()} advice(s).");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, AiAdvice>  $ruleAdvices
     * @return list<int|string>
     */
    private function row(string $ruleKey, Collection $ruleAdvices, AiAdviceServiceInterface $adviceService): array
    {
        $helpful = $ruleAdvices->where('rating', AiAdvice::RATING_HELPFUL)->count();
        $unhelpful = $ruleAdvices->where('rating', AiAdvice::RATING_UNHELPFUL)->count();
        $rated = $helpful + $unhelpful;

        $mutedUsers = User::whereIn('id', $ruleAdvices->whereNotNull('rating')->pluck('user_id')->unique())
            ->get()
            ->filter(fn (User $user) => $adviceService->isRuleMuted($user, $ruleKey))
            ->count();

        return [
            $ruleKey,
            $ruleAdvices->count(),
            $rated,
            $helpful,
            $unhelpful,
            $rated > 0 ? round($helpful / $rated * 100).'%' : '—',
            $mutedUsers,
        ];
    }
}
