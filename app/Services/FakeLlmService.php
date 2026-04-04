<?php

namespace App\Services;

use App\Contracts\LlmServiceInterface;
use App\Dto\AdvicePayload;

class FakeLlmService implements LlmServiceInterface
{
    /**
     * @var array<string, array{title: string, body: string}>
     */
    private const TEMPLATES = [
        'category_spike' => [
            'title' => 'Расходы в категории «:category» растут',
            'body' => 'За текущий месяц вы потратили :current ₽ в категории «:category», что на :percent% больше вашего среднего (:avg ₽/мес). Попробуйте установить лимит на эту категорию или пересмотреть необходимость некоторых покупок.',
        ],
        'overspending' => [
            'title' => 'Расходы превысили доходы',
            'body' => 'В этом месяце вы потратили :expense ₽ при доходе :income ₽. Перерасход составляет :overspend ₽ (:percent%). Рекомендуем сократить необязательные траты до конца месяца.',
        ],
        'goal_behind_schedule' => [
            'title' => 'Цель «:goal» отстаёт от графика',
            'body' => 'Ожидаемый прогресс по цели «:goal» — :expected%, а фактический — :actual% (отставание :lag%). Увеличьте ежемесячный взнос или пересмотрите сроки.',
        ],
        'unusual_transaction' => [
            'title' => 'Нетипичная транзакция в «:category»',
            'body' => ':date была зафиксирована транзакция на :amount ₽ в категории «:category». Это в :multiplier раз больше вашего среднего расхода. Убедитесь, что это запланированная покупка.',
        ],
        'savings_optimization' => [
            'title' => 'Возможность сэкономить :saving ₽/мес',
            'body' => 'Необязательные расходы (кафе, развлечения, одежда, подписки) составляют :share% ваших трат. Сокращение на 10% позволит экономить ~:saving ₽ в месяц, что за год даст :annual ₽.',
        ],
    ];

    /**
     * @return array{title: string, body: string}
     */
    public function generateAdviceText(AdvicePayload $payload): array
    {
        $template = self::TEMPLATES[$payload->ruleKey] ?? null;

        if (! $template) {
            return [
                'title' => $payload->title,
                'body' => $payload->body,
            ];
        }

        return [
            'title' => $this->interpolate($template['title'], $payload),
            'body' => $this->interpolate($template['body'], $payload),
        ];
    }

    private function interpolate(string $template, AdvicePayload $payload): string
    {
        $replacements = $this->buildReplacements($payload);

        foreach ($replacements as $key => $value) {
            $template = str_replace(":{$key}", (string) $value, $template);
        }

        return $template;
    }

    /**
     * @return array<string, string|int|float>
     */
    private function buildReplacements(AdvicePayload $payload): array
    {
        $data = $payload->basisData;

        return match ($payload->ruleKey) {
            'category_spike' => [
                'category' => $data['category_name'] ?? '—',
                'current' => $data['current_total'] ?? 0,
                'avg' => $data['avg_monthly'] ?? 0,
                'percent' => $data['growth_percent'] ?? 0,
            ],
            'overspending' => [
                'income' => $data['income'] ?? 0,
                'expense' => $data['expense'] ?? 0,
                'overspend' => $data['overspend'] ?? 0,
                'percent' => $data['overspend_percent'] ?? 0,
            ],
            'goal_behind_schedule' => [
                'goal' => $data['goal_name'] ?? '—',
                'expected' => $data['expected_percent'] ?? 0,
                'actual' => $data['actual_percent'] ?? 0,
                'lag' => $data['lag_percent'] ?? 0,
            ],
            'unusual_transaction' => [
                'category' => $data['category_name'] ?? '—',
                'amount' => $data['amount'] ?? 0,
                'multiplier' => $data['multiplier'] ?? 0,
                'date' => $data['date'] ?? '—',
            ],
            'savings_optimization' => [
                'share' => $data['discretionary_share_percent'] ?? 0,
                'saving' => $data['monthly_saving'] ?? 0,
                'annual' => ($data['monthly_saving'] ?? 0) * 12,
            ],
            default => [],
        };
    }
}
