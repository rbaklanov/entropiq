<?php

namespace Database\Seeders;

use App\Enums\RecurringInterval;
use App\Enums\SubscriptionPlan;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\NotificationSetting;
use App\Models\RecurringRule;
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
