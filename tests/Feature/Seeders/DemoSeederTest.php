<?php

use App\Actions\SendVerificationCode;
use App\Actions\VerifyCode;
use App\Contracts\AiAdviceServiceInterface;
use App\Enums\TransactionType;
use App\Models\AiAdvice;
use App\Models\Category;
use App\Models\Goal;
use App\Models\NotificationSetting;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AiAdviceRuleEngine;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function demoUser(): ?User
{
    return User::where('phone', DemoSeeder::phone())->first();
}

describe('DemoSeeder', function () {
    it('puts the demo account on the SMS demo phone', function () {
        config()->set('services.sms.demo_phone', '79991112233');

        $this->seed(CategorySeeder::class);
        $this->seed(DemoSeeder::class);

        expect(User::where('phone', '79991112233')->value('name'))->toBe('Алексей');
    });

    it('moves the account seeded with the old plus-prefixed phone', function () {
        $legacy = User::factory()->create(['phone' => '+79990000001']);

        $this->seed(CategorySeeder::class);
        $this->seed(DemoSeeder::class);

        expect($legacy->fresh()->phone)->toBe(DemoSeeder::phone())
            ->and(User::where('phone', '+79990000001')->exists())->toBeFalse();
    });

    it('lets the login form reach the demo account with the fixed demo code', function () {
        config()->set('services.sms.fixed_code', false);

        $this->seed(CategorySeeder::class);
        $this->seed(DemoSeeder::class);

        $code = app(SendVerificationCode::class)->execute(DemoSeeder::phone());
        $user = app(VerifyCode::class)->execute(DemoSeeder::phone(), '1111');

        expect($code->phone)->toBe(DemoSeeder::phone())
            ->and($user->is(demoUser()))->toBeTrue();
    });

    it('creates a complete Premium demo account', function () {
        $this->seed(CategorySeeder::class);
        $this->seed(DemoSeeder::class);

        $user = demoUser();

        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('Алексей')
            ->and($user->subscription_plan->value)->not->toBe('free')
            ->and($user->transactions()->count())->toBeGreaterThan(100)
            ->and($user->recurringRules()->count())->toBeGreaterThan(0)
            ->and($user->goals()->count())->toBeGreaterThan(0)
            ->and($user->aiAdvices()->count())->toBeGreaterThan(0)
            ->and(Subscription::where('user_id', $user->id)->exists())->toBeTrue()
            ->and(NotificationSetting::where('user_id', $user->id)->exists())->toBeTrue();
    });

    it('spreads transactions over a year and a half', function () {
        $this->seed(CategorySeeder::class);
        $this->seed(DemoSeeder::class);

        $months = demoUser()->transactions()->pluck('date')
            ->map(fn ($date) => $date->format('Y-m'))
            ->unique();

        expect($months->count())->toBeGreaterThanOrEqual(18);
    });

    it('can be run again without duplicating the demo account', function () {
        $this->seed(CategorySeeder::class);
        $this->seed(DemoSeeder::class);

        $goals = demoUser()->goals()->count();
        $advices = demoUser()->aiAdvices()->count();

        $this->seed(DemoSeeder::class);

        expect(User::where('phone', DemoSeeder::phone())->count())->toBe(1)
            ->and(demoUser()->goals()->count())->toBe($goals)
            ->and(demoUser()->aiAdvices()->count())->toBe($advices);
    });

    it('does not touch other users', function () {
        $this->seed(CategorySeeder::class);

        $other = User::factory()->create();
        $category = Category::factory()->expense()->create();
        Transaction::factory()->for($other)->expense()->count(3)->create(['category_id' => $category->id]);
        Goal::factory()->for($other)->create();
        AiAdvice::factory()->for($other)->create();

        $this->seed(DemoSeeder::class);

        expect($other->transactions()->count())->toBe(3)
            ->and($other->goals()->count())->toBe(1)
            ->and($other->aiAdvices()->count())->toBe(1);
    });

    it('leaves nothing behind when seeding fails halfway', function () {
        $this->seed(CategorySeeder::class);

        Goal::creating(fn () => throw new RuntimeException('boom'));

        try {
            $this->seed(DemoSeeder::class);
        } catch (RuntimeException) {
        }

        expect(demoUser())->toBeNull()
            ->and(Transaction::count())->toBe(0);
    });

    it('does not depend on Faker, which is not installed in production', function () {
        $offenders = collect(glob(database_path('seeders/*.php')))
            ->filter(fn (string $file) => str_contains(file_get_contents($file), 'fake(') || str_contains(file_get_contents($file), 'Faker'))
            ->map(fn (string $file) => basename($file))
            ->values()
            ->all();

        expect($offenders)->toBe([]);
    });
});

describe('DemoSeeder current month scenario', function () {
    $dates = [
        'first day of the month' => '2026-10-01 09:00:00',
        'early in the month' => '2026-10-06 14:00:00',
        'middle of the month' => '2026-10-15 12:00:00',
        'last day of the month' => '2026-10-31 23:00:00',
        'end of February' => '2027-02-28 10:00:00',
        'first minutes of the year' => '2027-01-01 00:30:00',
    ];

    beforeEach(function () {
        $this->seed(CategorySeeder::class);
        config(['services.llm.driver' => 'fake']);
    });

    it('adds current month transactions up to today and none in the future', function (string $date) {
        $this->travelTo(Carbon::parse($date));
        $this->seed(DemoSeeder::class);

        $currentMonth = demoUser()->transactions()->where('date', '>=', now()->startOfMonth())->get();

        expect($currentMonth)->not->toBeEmpty()
            ->and($currentMonth->max('date')->lte(now()->endOfDay()))->toBeTrue()
            ->and(demoUser()->transactions()->where('date', '>', now()->endOfDay())->count())->toBe(0);
    })->with($dates);

    it('does not overspend in a finished month except for the July trip', function () {
        $this->travelTo(Carbon::parse('2026-11-24 12:00:00'));
        $this->seed(DemoSeeder::class);

        $savings = Category::savings()->pluck('id');

        for ($monthsAgo = 1; $monthsAgo <= 18; $monthsAgo++) {
            $month = now()->startOfMonth()->subMonths($monthsAgo);
            $rows = demoUser()->transactions()->whereBetween('date', [$month, $month->copy()->endOfMonth()])->get();
            $income = $rows->where('type', TransactionType::Income)->sum('amount');
            $expense = $rows->where('type', TransactionType::Expense)->whereNotIn('category_id', $savings)->sum('amount');

            expect($income > $expense)->toBe($month->month !== 7);
        }
    });

    it('always triggers the spike, unusual transaction, goal and savings rules', function (string $date) {
        $this->travelTo(Carbon::parse($date));
        $this->seed(DemoSeeder::class);

        $payloads = collect(app(AiAdviceRuleEngine::class)->evaluate(demoUser()))->keyBy('ruleKey');

        expect($payloads->keys()->all())->toContain('category_spike', 'unusual_transaction', 'goal_behind_schedule', 'savings_optimization')
            ->and($payloads['category_spike']->basisData['category_name'])->toBe('Кафе и рестораны')
            ->and($payloads['category_spike']->basisData['growth_percent'])->toBeGreaterThanOrEqual(35)
            ->and($payloads['goal_behind_schedule']->basisData['goal_name'])->toBe('Отпуск в Турцию');
    })->with($dates);

    it('lets advice generation create advice for the demo account', function () {
        $this->seed(DemoSeeder::class);

        $advices = app(AiAdviceServiceInterface::class)->generateForUser(demoUser());
        $rules = $advices->map(fn (AiAdvice $advice) => $advice->basis_data['rule'] ?? null)->all();

        expect($advices->count())->toBeGreaterThanOrEqual(3)
            ->and($rules)->toContain('category_spike', 'unusual_transaction', 'savings_optimization');
    });

    it('keeps the previous eighteen months', function () {
        $this->seed(DemoSeeder::class);

        $months = demoUser()->transactions()
            ->where('date', '<', now()->startOfMonth())
            ->pluck('date')
            ->map(fn ($date) => $date->format('Y-m'))
            ->unique();

        expect($months->count())->toBe(18);
    });
});

describe('DemoSeeder advice texts', function () {
    it('shows money in rubles in every generated advice', function () {
        $this->seed(CategorySeeder::class);
        config(['services.llm.driver' => 'fake']);
        $this->seed(DemoSeeder::class);

        $advices = app(AiAdviceServiceInterface::class)->generateForUser(demoUser());

        $spike = $advices->first(fn (AiAdvice $a) => ($a->basis_data['rule'] ?? null) === 'category_spike');

        expect($spike)->not->toBeNull()
            ->and($spike->body)->toContain(App\Support\Money::amount($spike->basis_data['current_total']).' ₽')
            ->and($spike->body)->not->toContain((string) $spike->basis_data['current_total']);

        foreach ($advices as $advice) {
            expect($advice->body)->not->toMatch('/\d{7,}\s*₽/');
        }
    });
});

describe('DemoSeeder history', function () {
    beforeEach(function () {
        $this->travelTo(Carbon::parse('2026-11-24 12:00:00'));
        $this->seed(CategorySeeder::class);
        $this->seed(DemoSeeder::class);
    });

    it('gives the same transactions on every run', function () {
        $snapshot = fn () => demoUser()->transactions()
            ->orderBy('date')->orderBy('category_id')->orderBy('amount')->orderBy('comment')
            ->get(['date', 'category_id', 'amount', 'comment'])
            ->toArray();

        $first = $snapshot();
        $this->seed(DemoSeeder::class);

        expect($snapshot())->toBe($first);
    });

    it('keeps goal balances equal to their contributions and savings transfers', function () {
        $goals = demoUser()->goals()->with('contributions.transaction')->get();

        foreach ($goals as $goal) {
            expect((int) $goal->contributions->sum('amount'))->toBe($goal->current_amount)
                ->and((int) $goal->contributions->sum(fn ($c) => $c->transaction->amount))->toBe($goal->current_amount)
                ->and($goal->contributions->min('date')->gte($goal->started_at))->toBeTrue();
        }

        expect($goals->pluck('name')->all())->toContain('Отпуск в Турцию', 'Обучение в вузе');
    });

    it('stores past advice produced by the rules', function () {
        $advices = demoUser()->aiAdvices()->orderBy('generated_at')->get();

        expect($advices)->toHaveCount(3)
            ->and($advices->pluck('basis_data.rule')->unique()->values()->all())->toBe(['category_spike'])
            ->and($advices->pluck('basis_data.category_name')->all())->toBe(['Одежда', 'Развлечения', 'Здоровье'])
            ->and($advices->max('generated_at')->isPast())->toBeTrue();
    });

    it('schedules recurring rules in the future only', function () {
        expect(demoUser()->recurringRules->every(fn ($rule) => $rule->next_run_at->isFuture()))->toBeTrue();
    });
});
