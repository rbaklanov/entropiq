<?php

namespace Database\Seeders;

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
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoSeeder extends Seeder
{
    private const DEMO_PHONE = '+79990000001';

    private const DEMO_NAME = 'Алексей';

    public function run(): void
    {
        $user = $this->createDemoUser();

        $this->command->info("Demo user created: {$user->phone}");

        $categories = $this->getCategories();
        $transactionCount = $this->seedTransactions($user, $categories);

        $this->command->info("Created {$transactionCount} transactions over 6 months.");

        $this->seedRecurringRules($user, $categories);
        $this->seedGoals($user);
        $this->seedAiAdvice($user);
        $this->seedSubscription($user);
        $this->seedNotificationSettings($user);

        $this->command->info('DemoSeeder completed.');
    }

    private function createDemoUser(): User
    {
        return User::updateOrCreate(
            ['phone' => self::DEMO_PHONE],
            [
                'name' => self::DEMO_NAME,
                'locale' => 'ru',
                'currency_code' => 'RUB',
                'subscription_plan' => SubscriptionPlan::Yearly,
                'phone_verified_at' => now(),
                'onboarding_completed_at' => now()->subMonths(6),
            ],
        );
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
     * @param  array{expense: array<string, Category>, income: array<string, Category>}  $categories
     */
    private function seedTransactions(User $user, array $categories): int
    {
        $user->transactions()->delete();

        $count = 0;
        $startDate = Carbon::now()->subMonths(6)->startOfMonth();

        for ($month = 0; $month < 6; $month++) {
            $monthStart = $startDate->copy()->addMonths($month);
            $monthEnd = $monthStart->copy()->endOfMonth();
            $monthNumber = $monthStart->month;

            $count += $this->seedMonthlyIncome($user, $categories['income'], $monthStart, $monthEnd);
            $count += $this->seedMonthlyExpenses($user, $categories['expense'], $monthStart, $monthEnd, $monthNumber);
        }

        return $count;
    }

    /**
     * @param  array<string, Category>  $incomeCategories
     */
    private function seedMonthlyIncome(User $user, array $incomeCategories, Carbon $monthStart, Carbon $monthEnd): int
    {
        $count = 0;
        $salary = $incomeCategories['Зарплата'] ?? null;

        if ($salary) {
            $this->createTransaction($user, $salary, TransactionType::Income, $this->vary(95_000_00), $monthStart->copy()->day(10), 'Аванс');
            $this->createTransaction($user, $salary, TransactionType::Income, $this->vary(95_000_00), $monthStart->copy()->day(25), 'Зарплата');
            $count += 2;
        }

        $freelance = $incomeCategories['Фриланс'] ?? null;

        if ($freelance && fake()->boolean(40)) {
            $this->createTransaction($user, $freelance, TransactionType::Income, $this->vary(25_000_00, 40), $this->randomDate($monthStart, $monthEnd), 'Проект');
            $count++;
        }

        return $count;
    }

    /**
     * @param  array<string, Category>  $expenseCategories
     */
    private function seedMonthlyExpenses(User $user, array $expenseCategories, Carbon $monthStart, Carbon $monthEnd, int $monthNumber): int
    {
        $isSummer = in_array($monthNumber, [6, 7, 8], true);

        $patterns = $this->getExpensePatterns($isSummer);
        $count = 0;

        foreach ($patterns as $categoryName => $config) {
            $category = $expenseCategories[$categoryName] ?? null;

            if (! $category) {
                continue;
            }

            $times = fake()->numberBetween($config['min_times'], $config['max_times']);

            for ($i = 0; $i < $times; $i++) {
                $amount = $this->vary($config['avg_amount'], $config['variance'] ?? 20);
                $date = $this->randomDate($monthStart, $monthEnd);
                $comment = $config['comments'][array_rand($config['comments'])] ?? null;

                $this->createTransaction($user, $category, TransactionType::Expense, $amount, $date, $comment);
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array<string, array{avg_amount: int, min_times: int, max_times: int, variance?: int, comments: array<int, string|null>}>
     */
    private function getExpensePatterns(bool $isSummer): array
    {
        return [
            'Продукты' => [
                'avg_amount' => 2_500_00,
                'min_times' => 8,
                'max_times' => 12,
                'variance' => 30,
                'comments' => ['Пятёрочка', 'Перекрёсток', 'ВкусВилл', 'Магнит', null],
            ],
            'Кафе и рестораны' => [
                'avg_amount' => 1_200_00,
                'min_times' => $isSummer ? 6 : 4,
                'max_times' => $isSummer ? 8 : 6,
                'comments' => ['Бизнес-ланч', 'Кофе', 'Ужин с друзьями', null],
            ],
            'Транспорт' => [
                'avg_amount' => 350_00,
                'min_times' => 18,
                'max_times' => 24,
                'variance' => 15,
                'comments' => ['Метро', 'Яндекс Такси', 'Автобус', null],
            ],
            'Коммунальные' => [
                'avg_amount' => 6_500_00,
                'min_times' => 1,
                'max_times' => 1,
                'variance' => 10,
                'comments' => ['ЖКУ'],
            ],
            'Связь и интернет' => [
                'avg_amount' => 900_00,
                'min_times' => 1,
                'max_times' => 1,
                'variance' => 5,
                'comments' => ['Мобильная связь'],
            ],
            'Подписки' => [
                'avg_amount' => 500_00,
                'min_times' => 2,
                'max_times' => 3,
                'variance' => 30,
                'comments' => ['Яндекс Плюс', 'Spotify', 'YouTube Premium'],
            ],
            'Здоровье' => [
                'avg_amount' => 2_000_00,
                'min_times' => 0,
                'max_times' => 2,
                'variance' => 50,
                'comments' => ['Аптека', 'Стоматолог', null],
            ],
            'Одежда' => [
                'avg_amount' => 4_500_00,
                'min_times' => 0,
                'max_times' => 2,
                'variance' => 40,
                'comments' => ['Uniqlo', 'H&M', null],
            ],
            'Развлечения' => [
                'avg_amount' => 1_800_00,
                'min_times' => $isSummer ? 4 : 2,
                'max_times' => $isSummer ? 6 : 4,
                'variance' => 35,
                'comments' => ['Кино', 'Концерт', 'Боулинг', 'Парк', null],
            ],
            'Красота' => [
                'avg_amount' => 2_500_00,
                'min_times' => 0,
                'max_times' => 1,
                'variance' => 30,
                'comments' => ['Барбершоп', null],
            ],
            'Образование' => [
                'avg_amount' => 3_000_00,
                'min_times' => 0,
                'max_times' => 1,
                'variance' => 40,
                'comments' => ['Курс на Stepik', 'Книга', null],
            ],
            'Домашние животные' => [
                'avg_amount' => 1_500_00,
                'min_times' => 1,
                'max_times' => 2,
                'variance' => 25,
                'comments' => ['Корм для кота', 'Ветеринар'],
            ],
            'Подарки' => [
                'avg_amount' => 3_000_00,
                'min_times' => 0,
                'max_times' => 1,
                'variance' => 50,
                'comments' => ['День рождения друга', null],
            ],
            'Прочие расходы' => [
                'avg_amount' => 1_000_00,
                'min_times' => 1,
                'max_times' => 3,
                'variance' => 40,
                'comments' => [null],
            ],
        ];
    }

    /**
     * @param  array{expense: array<string, Category>, income: array<string, Category>}  $categories
     */
    private function seedRecurringRules(User $user, array $categories): void
    {
        $user->recurringRules()->delete();

        $salary = $categories['income']['Зарплата'] ?? null;
        if ($salary) {
            RecurringRule::create([
                'user_id' => $user->id,
                'type' => TransactionType::Income,
                'amount' => 95_000_00,
                'category_id' => $salary->id,
                'currency_code' => 'RUB',
                'comment' => 'Зарплата',
                'interval' => RecurringInterval::Monthly,
                'next_run_at' => Carbon::now()->startOfMonth()->addDays(24),
                'is_active' => true,
            ]);
        }

        $utilities = $categories['expense']['Коммунальные'] ?? null;
        if ($utilities) {
            RecurringRule::create([
                'user_id' => $user->id,
                'type' => TransactionType::Expense,
                'amount' => 6_500_00,
                'category_id' => $utilities->id,
                'currency_code' => 'RUB',
                'comment' => 'ЖКУ',
                'interval' => RecurringInterval::Monthly,
                'next_run_at' => Carbon::now()->startOfMonth()->addDays(4),
                'is_active' => true,
            ]);
        }

        $phone = $categories['expense']['Связь и интернет'] ?? null;
        if ($phone) {
            RecurringRule::create([
                'user_id' => $user->id,
                'type' => TransactionType::Expense,
                'amount' => 900_00,
                'category_id' => $phone->id,
                'currency_code' => 'RUB',
                'comment' => 'Мобильная связь',
                'interval' => RecurringInterval::Monthly,
                'next_run_at' => Carbon::now()->startOfMonth()->addDays(14),
                'is_active' => true,
            ]);
        }
    }

    private function seedGoals(User $user): void
    {
        $user->goals()->delete();

        $goals = [
            [
                'name' => 'Отпуск в Турцию',
                'type' => GoalType::Travel,
                'icon' => '✈️',
                'target_amount' => 200_000_00,
                'current_amount' => 60_000_00,
                'started_at' => Carbon::now()->subMonths(4),
                'target_date' => Carbon::now()->addMonths(4),
                'contributions_count' => 4,
            ],
            [
                'name' => 'Подушка безопасности',
                'type' => GoalType::SafetyNet,
                'icon' => '🛡️',
                'target_amount' => 300_000_00,
                'current_amount' => 45_000_00,
                'started_at' => Carbon::now()->subMonths(5),
                'target_date' => Carbon::now()->addMonths(7),
                'contributions_count' => 5,
            ],
            [
                'name' => 'Новый ноутбук',
                'type' => GoalType::LargePurchase,
                'icon' => '💻',
                'target_amount' => 120_000_00,
                'current_amount' => 72_000_00,
                'started_at' => Carbon::now()->subMonths(3),
                'target_date' => Carbon::now()->addMonth(),
                'contributions_count' => 3,
            ],
        ];

        foreach ($goals as $goalData) {
            $contributionsCount = $goalData['contributions_count'];
            unset($goalData['contributions_count']);

            $goal = Goal::create([
                'user_id' => $user->id,
                'status' => GoalStatus::Active,
                'currency_code' => 'RUB',
                ...$goalData,
            ]);

            $this->seedGoalContributions($goal, $contributionsCount);
        }

        $this->command->info('Created 3 goals with contributions.');
    }

    private function seedGoalContributions(Goal $goal, int $count): void
    {
        $totalAmount = $goal->current_amount;
        $remaining = $totalAmount;

        for ($i = 0; $i < $count; $i++) {
            $isLast = ($i === $count - 1);
            $amount = $isLast ? $remaining : (int) round($totalAmount / $count * $this->vary(100, 15) / 100);
            $amount = min($amount, $remaining);
            $remaining -= $amount;

            $monthsAgo = $count - $i;
            $date = Carbon::now()->subMonths($monthsAgo)->addDays(fake()->numberBetween(1, 15));

            GoalContribution::create([
                'goal_id' => $goal->id,
                'amount' => max(100, $amount),
                'date' => $date,
            ]);
        }
    }

    private function seedAiAdvice(User $user): void
    {
        $user->aiAdvices()->delete();

        $advices = [
            [
                'title' => 'Расходы на кафе выросли на 35%',
                'body' => 'В этом месяце вы потратили на кафе и рестораны значительно больше, чем в прошлом. Средний чек вырос с 1 100 ₽ до 1 500 ₽, а количество визитов увеличилось. Попробуйте установить лимит на эту категорию или чередовать обеды в кафе с домашней едой.',
                'basis_data' => ['rule' => 'category_spike', 'category' => 'dining', 'growth_percent' => 35],
                'is_read' => true,
                'rating' => 4,
                'days_ago' => 3,
            ],
            [
                'title' => 'Расходы превышают доходы',
                'body' => 'За последний месяц расходы составили 108% от доходов. Дефицит покрывается из накоплений. Рекомендуем пересмотреть необязательные траты: подписки, развлечения и кафе составляют 28% всех расходов.',
                'basis_data' => ['rule' => 'overspending', 'expense_ratio' => 1.08],
                'is_read' => true,
                'rating' => 5,
                'days_ago' => 7,
            ],
            [
                'title' => 'Цель "Новый ноутбук" отстаёт от графика',
                'body' => 'Для достижения цели в срок нужно откладывать 16 000 ₽/мес, но за последний месяц взнос составил 12 000 ₽. Увеличьте ежемесячный взнос на 4 000 ₽ или перенесите дедлайн.',
                'basis_data' => ['rule' => 'goal_behind_schedule', 'goal' => 'Новый ноутбук', 'gap_percent' => 25],
                'is_read' => true,
                'rating' => null,
                'days_ago' => 10,
            ],
            [
                'title' => 'Крупная покупка в "Одежда"',
                'body' => 'Транзакция на 8 500 ₽ в категории "Одежда" значительно превышает ваш средний чек 3 200 ₽. Если это разовая покупка, ничего страшного. Если нет, стоит пересмотреть бюджет.',
                'basis_data' => ['rule' => 'unusual_transaction', 'category' => 'clothing', 'amount' => 850000, 'avg' => 320000],
                'is_read' => false,
                'rating' => null,
                'days_ago' => 5,
            ],
            [
                'title' => 'Можно оптимизировать необязательные траты',
                'body' => 'Дискреционные расходы (кафе, развлечения, подписки) составляют 22% от общих трат. Сокращение на 10% высвободит около 4 500 ₽/мес, которые можно направить на цель "Подушка безопасности".',
                'basis_data' => ['rule' => 'savings_optimization', 'discretionary_share' => 0.22],
                'is_read' => false,
                'rating' => null,
                'days_ago' => 2,
            ],
            [
                'title' => 'Транспортные расходы стабильны',
                'body' => 'Расходы на транспорт за последние 3 месяца держатся в диапазоне 7 000–8 500 ₽. Это хороший показатель контроля. Если перейти на проездной за 2 900 ₽, экономия составит до 5 000 ₽/мес.',
                'basis_data' => ['rule' => 'savings_optimization', 'category' => 'transport', 'potential_savings' => 500000],
                'is_read' => true,
                'rating' => 3,
                'days_ago' => 14,
            ],
            [
                'title' => 'Продукты подорожали на 12% за год',
                'body' => 'Ваши расходы на продукты растут быстрее официальной инфляции (9,5%). Это может быть связано как с ростом цен, так и с увеличением объёма покупок. Сравните чеки за разные месяцы.',
                'basis_data' => ['rule' => 'category_spike', 'category' => 'groceries', 'growth_percent' => 12],
                'is_read' => false,
                'rating' => null,
                'days_ago' => 18,
            ],
            [
                'title' => 'Вы экономите 15% дохода',
                'body' => 'За последние 3 месяца вы стабильно откладываете около 15% от дохода. Это выше среднего по России (10%). Продолжайте в том же духе! При текущем темпе цель "Подушка безопасности" будет достигнута через 8 месяцев.',
                'basis_data' => ['rule' => 'savings_optimization', 'savings_rate' => 0.15],
                'is_read' => true,
                'rating' => 5,
                'days_ago' => 21,
            ],
            [
                'title' => 'Подписки: проверьте неиспользуемые',
                'body' => 'Вы оплачиваете 3 подписки общей стоимостью 1 400 ₽/мес. Проверьте, все ли из них вы активно используете. Отмена одной неиспользуемой подписки за год сэкономит до 6 000 ₽.',
                'basis_data' => ['rule' => 'savings_optimization', 'category' => 'subscriptions', 'total' => 140000],
                'is_read' => false,
                'rating' => null,
                'days_ago' => 25,
            ],
            [
                'title' => 'Цель "Отпуск в Турцию" на 30% выполнена',
                'body' => 'Вы накопили 60 000 ₽ из 200 000 ₽. При текущем темпе (15 000 ₽/мес) цель будет достигнута вовремя. Учтите, что с учётом инфляции (~0,8%/мес) реальная стоимость поездки может вырасти на 6–8%.',
                'basis_data' => ['rule' => 'goal_behind_schedule', 'goal' => 'Отпуск в Турцию', 'progress' => 30],
                'is_read' => true,
                'rating' => 4,
                'days_ago' => 30,
            ],
        ];

        foreach ($advices as $advice) {
            $daysAgo = $advice['days_ago'];
            unset($advice['days_ago']);

            AiAdvice::create([
                'user_id' => $user->id,
                'generated_at' => Carbon::now()->subDays($daysAgo),
                ...$advice,
            ]);
        }

        $this->command->info('Created 10 AI advice records.');
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

    private function vary(int $baseAmount, int $variancePercent = 20): int
    {
        $factor = 1 + (fake()->numberBetween(-$variancePercent, $variancePercent) / 100);

        return (int) round($baseAmount * $factor);
    }

    private function randomDate(Carbon $start, Carbon $end): Carbon
    {
        $days = $start->diffInDays($end);

        return $start->copy()->addDays(fake()->numberBetween(0, max(0, $days)));
    }
}
