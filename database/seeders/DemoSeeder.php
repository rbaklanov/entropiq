<?php

namespace Database\Seeders;

use App\Dto\AdvicePayload;
use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Enums\RecurringInterval;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionType;
use App\Models\AiAdvice;
use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalContribution;
use App\Models\NotificationSetting;
use App\Models\RecurringRule;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AiAdviceRuleEngine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    private const DEFAULT_PHONE = '79990000001';

    private const LEGACY_PHONE = '+79990000001';

    private const DEMO_NAME = 'Алексей';

    private const HISTORY_MONTHS = 18;

    private const ADVICE_HISTORY_MONTHS = 3;

    private const MONTHLY_PRICE_GROWTH = 0.005;

    private const DINING_GROWTH = 1.35;

    public function run(): void
    {
        DB::transaction(function () {
            $user = $this->createDemoUser();

            $this->command->info("Demo user created: {$user->phone}");

            $this->clear($user);

            $categories = $this->getCategories();
            $transactionCount = $this->seedTransactions($user, $categories);

            $months = self::HISTORY_MONTHS;
            $this->command->info("Created {$transactionCount} transactions over {$months} months.");

            $this->seedRecurringRules($user, $categories);
            $this->seedGoals($user);
            $this->seedSubscription($user);
            $this->seedNotificationSettings($user);

            $this->command->info('DemoSeeder completed.');
        });
    }

    /**
     * The demo account lives on the SMS demo phone, so the login form reaches it
     * with the fixed code even when real SMS are enabled.
     */
    public static function phone(): string
    {
        $demoPhone = config('services.sms.demo_phone');

        return is_string($demoPhone) && $demoPhone !== '' ? $demoPhone : self::DEFAULT_PHONE;
    }

    private function createDemoUser(): User
    {
        $phone = self::phone();

        if (! User::where('phone', $phone)->exists()) {
            User::where('phone', self::LEGACY_PHONE)->update(['phone' => $phone]);
        }

        return User::updateOrCreate(
            ['phone' => $phone],
            [
                'name' => self::DEMO_NAME,
                'email' => null,
                'email_verified_at' => null,
                'locale' => 'ru',
                'currency_code' => 'RUB',
                'subscription_plan' => SubscriptionPlan::Yearly,
                'phone_verified_at' => now(),
                'onboarding_completed_at' => now()->subMonths(self::HISTORY_MONTHS),
            ],
        );
    }

    private function clear(User $user): void
    {
        $user->goals()->delete();
        $user->recurringRules()->delete();
        $user->transactions()->delete();
        $user->aiAdvices()->delete();
    }

    /**
     * @return array{expense: array<string, Category>, income: array<string, Category>}
     */
    private function getCategories(): array
    {
        $expense = Category::where('type', TransactionType::Expense)
            ->where('is_system', true)
            ->get()
            ->keyBy(fn (Category $c) => $c->name['ru'] ?? '');

        $income = Category::where('type', TransactionType::Income)
            ->where('is_system', true)
            ->get()
            ->keyBy(fn (Category $c) => $c->name['ru'] ?? '');

        return [
            'expense' => $expense->all(),
            'income' => $income->all(),
        ];
    }

    /**
     * Amounts and dates depend only on the calendar month, so every run gives the same history.
     *
     * @param  array{expense: array<string, Category>, income: array<string, Category>}  $categories
     */
    private function seedTransactions(User $user, array $categories): int
    {
        $today = Carbon::today();
        $currentMonth = $today->copy()->startOfMonth();

        for ($monthsAgo = self::HISTORY_MONTHS; $monthsAgo >= 0; $monthsAgo--) {
            $month = $currentMonth->copy()->subMonths($monthsAgo);

            $this->seedMonthlyIncome($user, $categories['income'], $month, $today);
            $this->seedMonthlyExpenses($user, $categories['expense'], $month, $today);
            $this->seedPastEvent($user, $categories['expense'], $month, $monthsAgo);

            if ($monthsAgo >= 1 && $monthsAgo <= self::ADVICE_HISTORY_MONTHS) {
                $this->seedPastAdvice($user, $month->copy()->endOfMonth(), $monthsAgo);
            }
        }

        $this->seedCurrentMonthScenario($user, $categories['expense'], $currentMonth);

        return $user->transactions()->count();
    }

    /**
     * @param  array<string, Category>  $incomeCategories
     */
    private function seedMonthlyIncome(User $user, array $incomeCategories, Carbon $month, Carbon $today): void
    {
        $salary = $incomeCategories['Зарплата'] ?? null;

        if (! $salary) {
            return;
        }

        $amount = $month->lt(Carbon::create(2026, 1, 1)) ? 89_000_00 : 97_500_00;

        foreach ([5 => 'Зарплата', 20 => 'Аванс'] as $day => $comment) {
            $date = $month->copy()->day($day);

            if ($date->lte($today)) {
                $this->createTransaction($user, $salary, TransactionType::Income, $amount, $date, $comment);
            }
        }
    }

    /**
     * @param  array<string, Category>  $expenseCategories
     */
    private function seedMonthlyExpenses(User $user, array $expenseCategories, Carbon $month, Carbon $today): void
    {
        foreach ($this->getExpensePatterns() as $categoryName => $config) {
            $category = $expenseCategories[$categoryName] ?? null;

            if (! $category) {
                continue;
            }

            for ($i = 0; $i < $config['times']; $i++) {
                $key = "{$month->format('Y-m')}|{$categoryName}|{$i}";
                $date = isset($config['day'])
                    ? $month->copy()->day($config['day'])
                    : $month->copy()->addDays($this->spreadDay($key, $i, $config['times'], $month->daysInMonth));

                if ($date->gt($today)) {
                    continue;
                }

                $amount = $this->priceIn($month, $config['amount'], $key, $config['variance'] ?? 8);
                $comment = $config['comments'][$i % count($config['comments'])];

                $this->createTransaction($user, $category, TransactionType::Expense, $amount, $date, $comment);
            }
        }

        $travel = $expenseCategories['Путешествия'] ?? null;
        $tripDate = $month->copy()->day(12);

        if ($travel && $month->month === 7 && $tripDate->lte($today)) {
            $this->createTransaction($user, $travel, TransactionType::Expense, $this->priceIn($month, 65_000_00, 'trip', 0), $tripDate, 'Отпуск в Сочи');
        }
    }

    /**
     * One larger purchase in each of the last full months, so past advice differs from month to month.
     *
     * @param  array<string, Category>  $expenseCategories
     */
    private function seedPastEvent(User $user, array $expenseCategories, Carbon $month, int $monthsAgo): void
    {
        $events = [
            1 => ['Здоровье', 4_800_00, 'Стоматолог'],
            2 => ['Развлечения', 5_600_00, 'Билеты на концерт'],
            3 => ['Одежда', 7_200_00, 'Куртка'],
        ];

        [$categoryName, $amount, $comment] = $events[$monthsAgo] ?? [null, 0, null];
        $category = $expenseCategories[$categoryName ?? ''] ?? null;

        if ($category) {
            $this->createTransaction($user, $category, TransactionType::Expense, $amount, $month->copy()->day(17), $comment);
        }
    }

    /**
     * The advice the rules would have given at the end of that month.
     */
    private function seedPastAdvice(User $user, Carbon $monthEnd, int $monthsAgo): void
    {
        /** @var list<AdvicePayload> $payloads */
        $payloads = Carbon::withTestNow($monthEnd, fn () => app(AiAdviceRuleEngine::class)->evaluate($user));
        $payload = $payloads[0] ?? null;

        if (! $payload) {
            return;
        }

        AiAdvice::create([
            'user_id' => $user->id,
            'title' => $payload->title,
            'body' => $payload->body,
            'basis_data' => $payload->basisData,
            'is_read' => true,
            'rating' => $monthsAgo === 2 ? AiAdvice::RATING_UNHELPFUL : AiAdvice::RATING_HELPFUL,
            'generated_at' => $monthEnd->copy()->setTime(9, 0),
        ]);
    }

    /**
     * Cafe spending above the usual level and one untypical taxi ride, so the spike and unusual
     * transaction advice rules always fire.
     *
     * @param  array<string, Category>  $expenseCategories
     */
    private function seedCurrentMonthScenario(User $user, array $expenseCategories, Carbon $month): void
    {
        $today = Carbon::today();
        $dining = $expenseCategories['Кафе и рестораны'] ?? null;
        $transport = $expenseCategories['Транспорт'] ?? null;

        if (! $dining || ! $transport) {
            return;
        }

        $average = $this->previousMonthlyAverage($user, $dining, $month);
        $spent = (int) $user->transactions()
            ->where('category_id', $dining->id)
            ->where('date', '>=', $month)
            ->sum('amount');
        $extra = (int) ceil($average * self::DINING_GROWTH) - $spent;

        if ($extra > 0) {
            $comments = ['Ужин с друзьями', 'День рождения в ресторане', 'Ужин с коллегами', 'Доставка еды'];

            foreach ($comments as $index => $comment) {
                $date = $today->copy()->subDays($index * 2)->max($month);

                $this->createTransaction($user, $dining, TransactionType::Expense, (int) ceil($extra / count($comments) / 1000) * 1000, $date, $comment);
            }
        }

        $this->createTransaction(
            $user,
            $transport,
            TransactionType::Expense,
            (int) ceil($this->unusualPurchaseThreshold($user, $transport) * 1.2 / 1000) * 1000,
            $today,
            'Такси в аэропорт',
        );
    }

    private function previousMonthlyAverage(User $user, Category $category, Carbon $monthStart): float
    {
        $total = (int) $user->transactions()
            ->where('type', TransactionType::Expense)
            ->where('category_id', $category->id)
            ->where('date', '>=', $monthStart->copy()->subMonths(3))
            ->where('date', '<', $monthStart)
            ->sum('amount');

        return $total / 3;
    }

    private function unusualPurchaseThreshold(User $user, Category $category): float
    {
        $stats = $user->transactions()
            ->where('type', TransactionType::Expense)
            ->where('category_id', $category->id)
            ->where('date', '<', Carbon::now()->subWeek())
            ->where('date', '>=', Carbon::now()->subMonths(3)->startOfMonth())
            ->selectRaw('AVG(amount) as avg_amount, STDDEV(amount) as stddev_amount')
            ->first();

        $average = (float) $stats?->getAttribute('avg_amount');
        $deviation = (float) $stats?->getAttribute('stddev_amount');

        return $average + max($deviation, $average) * 3;
    }

    /**
     * Amounts are average prices of November 2026.
     *
     * @return array<string, array{amount: int, times: int, day?: int, variance?: int, comments: list<string|null>}>
     */
    private function getExpensePatterns(): array
    {
        return [
            'Жильё' => ['amount' => 55_000_00, 'times' => 1, 'day' => 7, 'variance' => 0, 'comments' => ['Аренда квартиры']],
            'Коммунальные' => ['amount' => 6_500_00, 'times' => 1, 'day' => 12, 'comments' => ['ЖКУ']],
            'Связь и интернет' => ['amount' => 1_300_00, 'times' => 1, 'day' => 15, 'variance' => 0, 'comments' => ['Мобильная связь и интернет']],
            'Продукты' => ['amount' => 2_800_00, 'times' => 12, 'variance' => 25, 'comments' => ['Пятёрочка', 'Перекрёсток', 'ВкусВилл', 'Магнит', null]],
            'Кафе и рестораны' => ['amount' => 1_500_00, 'times' => 8, 'variance' => 20, 'comments' => ['Бизнес-ланч', 'Кофе', 'Ужин', null]],
            'Транспорт' => ['amount' => 400_00, 'times' => 20, 'variance' => 15, 'comments' => ['Метро', 'Такси', 'Автобус', null]],
            'Подписки' => ['amount' => 400_00, 'times' => 3, 'variance' => 0, 'comments' => ['Яндекс Плюс', 'Облачное хранилище', 'Музыка']],
            'Здоровье' => ['amount' => 1_500_00, 'times' => 2, 'comments' => ['Аптека', 'Анализы']],
            'Одежда' => ['amount' => 2_750_00, 'times' => 2, 'comments' => ['Одежда', 'Обувь']],
            'Развлечения' => ['amount' => 2_000_00, 'times' => 4, 'variance' => 10, 'comments' => ['Кино', 'Боулинг', 'Театр', 'Настольные игры']],
            'Красота' => ['amount' => 2_000_00, 'times' => 1, 'comments' => ['Барбершоп']],
            'Образование' => ['amount' => 3_500_00, 'times' => 1, 'comments' => ['Курс английского']],
            'Домашние животные' => ['amount' => 1_600_00, 'times' => 2, 'comments' => ['Корм для кота', 'Наполнитель']],
            'Подарки' => ['amount' => 3_000_00, 'times' => 1, 'comments' => ['День рождения']],
            'Прочие расходы' => ['amount' => 1_500_00, 'times' => 2, 'comments' => ['Хозтовары', null]],
        ];
    }

    private function priceIn(Carbon $month, int $amount, string $key, int $variancePercent): int
    {
        $months = ($month->year - 2026) * 12 + $month->month - 11;
        $inflated = $amount * (1 + self::MONTHLY_PRICE_GROWTH) ** $months;
        $variance = (crc32($key) % 2001 - 1000) / 1000 * $variancePercent / 100;

        return (int) round($inflated * (1 + $variance) / 1000) * 1000;
    }

    private function spreadDay(string $key, int $index, int $times, int $daysInMonth): int
    {
        $step = $daysInMonth / $times;

        return min($daysInMonth - 1, (int) floor($index * $step) + crc32("day|{$key}") % max(1, (int) floor($step)));
    }

    /**
     * @param  array{expense: array<string, Category>, income: array<string, Category>}  $categories
     */
    private function seedRecurringRules(User $user, array $categories): void
    {
        $rules = [
            [$categories['income']['Зарплата'] ?? null, TransactionType::Income, 97_500_00, 'Зарплата', 5],
            [$categories['expense']['Жильё'] ?? null, TransactionType::Expense, 55_000_00, 'Аренда квартиры', 7],
            [$categories['expense']['Связь и интернет'] ?? null, TransactionType::Expense, 1_300_00, 'Мобильная связь и интернет', 15],
        ];

        foreach ($rules as [$category, $type, $amount, $comment, $day]) {
            if (! $category) {
                continue;
            }

            $nextRun = Carbon::today()->startOfMonth()->day($day);

            RecurringRule::create([
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'category_id' => $category->id,
                'currency_code' => 'RUB',
                'comment' => $comment,
                'interval' => RecurringInterval::Monthly,
                'next_run_at' => $nextRun->gt(Carbon::today()) ? $nextRun : $nextRun->addMonth(),
                'is_active' => true,
            ]);
        }
    }

    private function seedGoals(User $user): void
    {
        $savings = Category::system()->savings()->first();

        $goals = [
            ['name' => 'Отпуск в Турцию', 'type' => GoalType::Travel, 'icon' => '✈️', 'target' => 200_000_00, 'monthly' => 15_000_00, 'paid' => 4, 'left' => 4],
            ['name' => 'Подушка безопасности', 'type' => GoalType::SafetyNet, 'icon' => '🛡️', 'target' => 240_000_00, 'monthly' => 10_000_00, 'paid' => 14, 'left' => 10],
            ['name' => 'Обучение в вузе', 'type' => GoalType::Education, 'icon' => '🎓', 'target' => 1_000_000_00, 'monthly' => 15_000_00, 'paid' => 3, 'left' => 45],
        ];

        foreach ($goals as $data) {
            $goal = Goal::create([
                'user_id' => $user->id,
                'status' => GoalStatus::Active,
                'currency_code' => 'RUB',
                'name' => $data['name'],
                'type' => $data['type'],
                'icon' => $data['icon'],
                'target_amount' => $data['target'],
                'current_amount' => $data['monthly'] * $data['paid'],
                'started_at' => Carbon::today()->subMonthsNoOverflow($data['paid']),
                'target_date' => Carbon::today()->addMonthsNoOverflow($data['left']),
            ]);

            $this->seedGoalContributions($user, $goal, $data['monthly'], $data['paid'], $savings);
        }

        $this->command->info('Created 3 goals with contributions.');
    }

    private function seedGoalContributions(User $user, Goal $goal, int $amount, int $count, ?Category $savings): void
    {
        $lastPayment = Carbon::today()->startOfMonth()->day(6);

        if ($lastPayment->gt(Carbon::today())) {
            $lastPayment->subMonth();
        }

        for ($monthsAgo = $count - 1; $monthsAgo >= 0; $monthsAgo--) {
            $date = $lastPayment->copy()->subMonths($monthsAgo);

            $transaction = $savings
                ? $this->createTransaction($user, $savings, TransactionType::Expense, $amount, $date, __('goals.contribution_comment', ['goal' => $goal->name], 'ru'))
                : null;

            GoalContribution::create([
                'goal_id' => $goal->id,
                'transaction_id' => $transaction?->id,
                'amount' => $amount,
                'date' => $date,
            ]);
        }
    }

    private function seedSubscription(User $user): void
    {
        $user->subscriptions()->delete();

        Subscription::create([
            'user_id' => $user->id,
            'plan' => SubscriptionPlan::Yearly,
            'status' => SubscriptionStatus::Active,
            'starts_at' => Carbon::now()->subMonths(3),
            'ends_at' => Carbon::now()->addMonths(9),
        ]);
    }

    private function seedNotificationSettings(User $user): void
    {
        NotificationSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'email_weekly' => true,
                'push_goals' => true,
                'push_ai_advice' => true,
            ],
        );
    }

    private function createTransaction(
        User $user,
        Category $category,
        TransactionType $type,
        int $amount,
        Carbon $date,
        ?string $comment = null,
    ): Transaction {
        return Transaction::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'type' => $type,
            'amount' => max(100, $amount),
            'currency_code' => 'RUB',
            'date' => $date,
            'comment' => $comment,
            'is_recurring' => false,
        ]);
    }
}
