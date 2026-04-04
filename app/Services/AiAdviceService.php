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
            ->whereJsonContains('basis_data->rule', $ruleKey)
            ->exists();
    }
}
