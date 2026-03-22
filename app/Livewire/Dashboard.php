<?php

namespace App\Livewire;

use App\Enums\TransactionType;
use App\Models\Transaction;
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
        $userId = auth()->id();

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

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

        return view('livewire.dashboard', [
            'monthlySummary' => $monthlySummary,
            'recentTransactions' => $recentTransactions,
            'topCategories' => $topCategories,
            'totalExpense' => $totalExpense,
            'savingsRate' => $savingsRate,
        ]);
    }
}
