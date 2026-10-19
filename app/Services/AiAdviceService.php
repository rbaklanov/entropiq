<?php

namespace App\Services;

use App\Contracts\AiAdviceServiceInterface;
use App\Contracts\LlmServiceInterface;
use App\Models\AiAdvice;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AiAdviceService implements AiAdviceServiceInterface
{
    /** A rule is muted for a user when this many of their latest ratings for it are negative. */
    public const int MUTE_AFTER_DISLIKES = 2;

    /** A muted rule produces at most one advice per this many days instead of one per day. */
    public const int MUTED_RULE_COOLDOWN_DAYS = 14;

    public function __construct(
        private readonly AiAdviceRuleEngine $ruleEngine,
        private readonly LlmServiceInterface $llmService,
    ) {}

    /**
     * @return Collection<int, AiAdvice>
     */
    public function generateForUser(User $user): Collection
    {
        $payloads = $this->ruleEngine->evaluate($user);

        if (empty($payloads)) {
            return collect();
        }

        $advices = collect();

        foreach ($payloads as $payload) {
            if ($this->alreadyGeneratedToday($user, $payload->ruleKey)) {
                continue;
            }

            if ($this->isRuleMuted($user, $payload->ruleKey) && $this->generatedWithinCooldown($user, $payload->ruleKey)) {
                continue;
            }

            $text = $this->llmService->generateAdviceText($payload);

            $advice = AiAdvice::create([
                'user_id' => $user->id,
                'title' => $text['title'],
                'body' => $text['body'],
                'basis_data' => $payload->basisData,
                'is_read' => false,
                'generated_at' => Carbon::now(),
            ]);

            $advices->push($advice);
        }

        return $advices;
    }

    private function alreadyGeneratedToday(User $user, string $ruleKey): bool
    {
        return AiAdvice::where('user_id', $user->id)
            ->where('generated_at', '>=', Carbon::today())
            ->forRule($ruleKey)
            ->exists();
    }

    /**
     * The user's latest ratings for this rule are all negative. A later positive rating lifts the mute.
     */
    public function isRuleMuted(User $user, string $ruleKey): bool
    {
        $latestRatings = AiAdvice::where('user_id', $user->id)
            ->forRule($ruleKey)
            ->rated()
            ->latest('generated_at')
            ->limit(self::MUTE_AFTER_DISLIKES)
            ->pluck('rating');

        return $latestRatings->count() === self::MUTE_AFTER_DISLIKES
            && $latestRatings->every(fn (int $rating) => $rating === AiAdvice::RATING_UNHELPFUL);
    }

    private function generatedWithinCooldown(User $user, string $ruleKey): bool
    {
        return AiAdvice::where('user_id', $user->id)
            ->where('generated_at', '>=', Carbon::today()->subDays(self::MUTED_RULE_COOLDOWN_DAYS - 1))
            ->forRule($ruleKey)
            ->exists();
    }
}
