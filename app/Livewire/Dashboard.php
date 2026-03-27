<?php

namespace App\Livewire;

use App\Enums\TransactionType;
use App\Models\Goal;
use App\Models\Transaction;
use App\Services\GoalCalculationService;
use App\Services\InflationService;
use App\Services\TransactionService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    private const RECENT_LIMIT = 5;

    private const TOP_CATEGORIES_LIMIT = 5;

    public function render(): View
    {
        $service = app(TransactionService::class);
        $inflation = app(InflationService::class);
        $calc = app(GoalCalculationService::class);
        $userId = auth()->id();

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $yearStart = Carbon::now()->startOfYear();

        $monthlySummary = $service->getSummary($userId, $monthStart, $monthEnd);
        $topExpenses = $service->getByCategory($userId, $monthStart, $monthEnd, TransactionType::Expense);

        $recentTransactions = Transaction::where('user_id', $userId)
            ->with('category')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(self::RECENT_LIMIT)
            ->get();

        $topCategories = collect($topExpenses)
            ->take(self::TOP_CATEGORIES_LIMIT)
            ->map(function (array $item) {
                $category = \App\Models\Category::find($item['category_id']);

                return [
                    'name' => $category ? ($category->name['ru'] ?? '—') : '—',
                    'icon' => $category ? $category->icon : '📦',
                    'color' => $category ? $category->color : '#6366F1',
                    'total' => $item['total'],
                    'count' => $item['count'],
                ];
            });

        $totalExpense = $monthlySummary['expense'];
        $savingsRate = $monthlySummary['income'] > 0
            ? round(($monthlySummary['income'] - $monthlySummary['expense']) / $monthlySummary['income'] * 100)
            : 0;

        $nominalBalance = $monthlySummary['balance'];
        $nationalInflation = $inflation->getCurrentCpi();
        $personalInflation = $inflation->calculatePersonalInflation($userId, $yearStart->copy(), Carbon::now());

        $monthlyInflation = $nationalInflation / 12;
        $realBalance = (int) round($nominalBalance / (1 + $monthlyInflation));
        $inflationLoss = $nominalBalance - $realBalance;

        $goals = Goal::where('user_id', $userId)
            ->active()
            ->orderByDesc('created_at')
            ->get();

        $currentInflation = $calc->getCurrentAnnualInflation();

        $goalData = $goals->map(function (Goal $goal) use ($calc, $currentInflation) {
            $monthsLeft = $calc->getMonthsLeft($goal);
            $nominalProgress = $goal->progressPercent();
            $yearsLeft = $monthsLeft / 12;
            $inflatedTarget = $goal->target_amount * pow(1 + $currentInflation, $yearsLeft);
            $realProgress = $inflatedTarget > 0
                ? min(100, (int) round($goal->current_amount / $inflatedTarget * 100))
                : $nominalProgress;

            return [
                'goal' => $goal,
                'monthly_payment' => $calc->requiredMonthlyPayment(
                    $goal->target_amount,
                    $goal->current_amount,
                    $monthsLeft,
                ),
                'completion_date' => $goal->target_date?->toDateString(),
                'real_progress' => $realProgress,
            ];
        });

        return view('livewire.dashboard', [
            'monthlySummary' => $monthlySummary,
            'recentTransactions' => $recentTransactions,
            'topCategories' => $topCategories,
            'totalExpense' => $totalExpense,
            'savingsRate' => $savingsRate,
            'nominalBalance' => $nominalBalance,
            'realBalance' => $realBalance,
            'inflationLoss' => $inflationLoss,
            'nationalInflation' => $nationalInflation,
            'personalInflation' => $personalInflation,
            'goalData' => $goalData,
        ]);
    }
}
