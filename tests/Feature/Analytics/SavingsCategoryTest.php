<?php

use App\Livewire\Analytics;
use App\Livewire\Dashboard;
use App\Livewire\Goals\GoalDetail;
use App\Mail\WeeklyDigestMail;
use App\Models\Category;
use App\Models\CpiCategory;
use App\Models\CpiValue;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\User;
use App\Rules\Advice\CategorySpikeRule;
use App\Rules\Advice\OverspendingRule;
use App\Rules\Advice\SavingsOptimizationRule;
use App\Rules\Advice\UnusualTransactionRule;
use App\Services\AnalyticsService;
use App\Services\ExportService;
use App\Services\InflationService;
use App\Services\TransactionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->onboarded()->create(['phone_verified_at' => now()]);
    $this->savings = Category::factory()->savings()->system()->create(['name' => ['ru' => 'Накопления', 'en' => 'Savings']]);
    $this->food = Category::factory()->expense()->system()->create(['name' => ['ru' => 'Продукты', 'en' => 'Food']]);

    CpiCategory::create([
        'code' => 'FOOD',
        'name' => 'Продовольственные товары',
        'mapping_to_app_category_id' => $this->food->id,
    ]);

    for ($month = 1; $month <= 12; $month++) {
        foreach (['TOTAL' => 100.5, 'FOOD' => 101.0] as $code => $value) {
            CpiValue::create([
                'period' => Carbon::create(2025, $month, 1),
                'category_code' => $code,
                'value' => $value,
                'source' => 'test',
            ]);
        }
    }
});

function addTransaction(User $user, Category $category, int $amount, string $type = 'expense', string $date = '2025-06-15'): void
{
    Transaction::factory()->for($user)->{$type}()->create([
        'category_id' => $category->id,
        'amount' => $amount,
        'date' => $date,
    ]);
}

function addMonthWithSavings(User $user, Category $salary, Category $food, Category $savings): void
{
    addTransaction($user, $salary, 100000, 'income');
    addTransaction($user, $food, 40000);
    addTransaction($user, $savings, 30000);
}

describe('savings category in inflation figures', function () {
    it('is marked by the category seeder', function () {
        $this->seed(CategorySeeder::class);

        expect(Category::system()->savings()->pluck('name')->pluck('ru')->all())->toBe(['Накопления']);
    });

    it('leaves personal inflation to spending on goods and services', function () {
        addTransaction($this->user, $this->food, 100000);

        $foodOnly = app(InflationService::class)->calculatePersonalInflation(
            $this->user->id,
            Carbon::create(2025, 1, 1),
            Carbon::create(2025, 12, 31),
        );

        addTransaction($this->user, $this->savings, 900000);

        $withSavings = app(InflationService::class)->calculatePersonalInflation(
            $this->user->id,
            Carbon::create(2025, 1, 1),
            Carbon::create(2025, 12, 31),
        );

        expect($withSavings)->toBe($foodOnly);
        expect($withSavings)->toEqualWithDelta(pow(1.01, 12) - 1, 0.0001);
    });

    it('drops savings from the personal inflation breakdown', function () {
        addTransaction($this->user, $this->food, 100000);
        addTransaction($this->user, $this->savings, 900000);

        $result = app(AnalyticsService::class)->getPersonalInflationBreakdown(
            $this->user->id,
            Carbon::create(2025, 1, 1),
            Carbon::create(2025, 12, 31),
        );

        expect(array_column($result['breakdown'], 'category_id'))->toBe([$this->food->id]);
        expect($result['breakdown'][0]['share'])->toBe(1.0);
    });

    it('keeps transfers to savings in the balance', function () {
        addTransaction($this->user, $this->food, 30000, 'income', '2025-03-01');
        addTransaction($this->user, $this->food, 10000, 'expense', '2025-03-05');
        addTransaction($this->user, $this->savings, 15000, 'expense', '2025-03-05');

        $result = app(AnalyticsService::class)->getBalanceDynamics(
            $this->user->id,
            Carbon::parse('2025-03-01'),
            Carbon::parse('2025-03-31'),
        );

        expect($result[1]['expense'])->toBe(25000);
        expect($result[1]['savings'])->toBe(15000);
        expect($result[1]['balance'])->toBe(-10000);
        expect($result[1]['cumulative_balance'])->toBe(20000);
    });

    it('counts savings transfers in the money that inflation erodes', function () {
        addTransaction($this->user, $this->food, 100000, 'income');
        addTransaction($this->user, $this->savings, 100000);

        $loss = app(InflationService::class)->calculateInflationLoss(
            $this->user->id,
            Carbon::create(2025, 1, 1),
            Carbon::create(2025, 12, 31),
        );

        expect($loss)->toBe((int) round(100000 * (1 - 1 / pow(1.005, 12))));
    });

    it('shows no inflation loss when the balance is negative', function () {
        Carbon::setTestNow('2025-12-20');

        addTransaction($this->user, $this->food, 500000, 'expense', '2025-12-10');

        Livewire::actingAs($this->user)
            ->test(Analytics::class)
            ->set('tab', 'balance')
            ->assertViewHas('inflationLoss', 0);
    });

    it('books a goal contribution to the savings category', function () {
        $goal = Goal::factory()->for($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(GoalDetail::class, ['goal' => $goal])
            ->set('contributeAmount', 50000)
            ->call('contribute');

        expect(Transaction::where('user_id', $this->user->id)->value('category_id'))->toBe($this->savings->id);
    });
});

describe('savings category in spending figures', function () {
    beforeEach(function () {
        Carbon::setTestNow('2025-06-20');

        $this->salary = Category::factory()->income()->system()->create();
    });

    it('leaves savings out of expenses in the period summary', function () {
        addMonthWithSavings($this->user, $this->salary, $this->food, $this->savings);

        $summary = app(TransactionService::class)->getSummary(
            $this->user->id,
            Carbon::parse('2025-06-01'),
            Carbon::parse('2025-06-30'),
        );

        expect($summary)->toBe(['income' => 100000, 'expense' => 40000, 'savings' => 30000, 'balance' => 60000]);
    });

    it('returns savings apart from expenses in the API summary', function () {
        addMonthWithSavings($this->user, $this->salary, $this->food, $this->savings);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/transactions/summary?from=2025-06-01&to=2025-06-30')
            ->assertOk()
            ->assertExactJson(['income' => 100000, 'expense' => 40000, 'savings' => 30000, 'balance' => 60000]);
    });

    it('keeps savings out of the dashboard top expenses and counts them as left from income', function () {
        addMonthWithSavings($this->user, $this->salary, $this->food, $this->savings);

        Livewire::actingAs($this->user)
            ->test(Dashboard::class)
            ->assertViewHas('topCategories', fn ($categories) => $categories->pluck('total')->all() === [40000])
            ->assertViewHas('savingsRate', 60.0);
    });

    it('does not call a transfer to savings overspending', function () {
        addTransaction($this->user, $this->salary, 100000, 'income');
        addTransaction($this->user, $this->food, 40000);
        addTransaction($this->user, $this->savings, 80000);

        expect(app(OverspendingRule::class)->evaluate($this->user))->toBeNull();
    });

    it('ignores a jump in savings transfers when looking for spending spikes', function () {
        foreach (['2025-03-15', '2025-04-15', '2025-05-15'] as $date) {
            addTransaction($this->user, $this->savings, 10000, 'expense', $date);
        }

        addTransaction($this->user, $this->savings, 50000, 'expense', '2025-06-10');

        expect((new CategorySpikeRule)->evaluate($this->user))->toBeNull();
    });

    it('does not flag a large transfer to savings as an unusual transaction', function () {
        foreach (range(1, 10) as $day) {
            addTransaction($this->user, $this->savings, 1000, 'expense', "2025-04-{$day}");
        }

        addTransaction($this->user, $this->savings, 50000, 'expense', '2025-06-18');

        expect((new UnusualTransactionRule)->evaluate($this->user))->toBeNull();
    });

    it('measures the discretionary share against spending without savings', function () {
        $cafe = Category::factory()->expense()->system()->create(['name' => ['ru' => 'Кафе и рестораны', 'en' => 'Cafes']]);

        addTransaction($this->user, $cafe, 20000);
        addTransaction($this->user, $this->food, 80000);
        addTransaction($this->user, $this->savings, 1000000);

        $result = app(SavingsOptimizationRule::class)->evaluate($this->user);

        expect($result)->not->toBeNull();
        expect($result->basisData['total_expense'])->toBe(100000);
        expect($result->basisData['discretionary_share_percent'])->toBe(20);
    });

    it('shows savings apart from expenses in the weekly digest', function () {
        addMonthWithSavings($this->user, $this->salary, $this->food, $this->savings);

        $mail = new WeeklyDigestMail($this->user, Carbon::parse('2025-06-14'), Carbon::parse('2025-06-20'));

        expect($mail->digest['total_expense'])->toBe(40000);
        expect($mail->digest['total_savings'])->toBe(30000);
        expect($mail->digest['balance'])->toBe(60000);
        expect(array_column($mail->digest['top_categories'], 'total'))->toBe([40000]);

        $mail->assertSeeInHtml(__('digest.savings'));
    });

    it('prints savings apart from expenses in the PDF export', function () {
        addMonthWithSavings($this->user, $this->salary, $this->food, $this->savings);

        Pdf::spy();

        app(ExportService::class)->transactionsToPdf($this->user);

        Pdf::shouldHaveReceived('loadView')
            ->once()
            ->withArgs(fn (string $view, array $data) => $data['expense'] === 40000
                && $data['savings'] === 30000
                && $data['balance'] === 60000);
    });
});
