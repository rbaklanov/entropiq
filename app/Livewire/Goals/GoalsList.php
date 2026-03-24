<?php

namespace App\Livewire\Goals;

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Services\GoalCalculationService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class GoalsList extends Component
{
    #[Url]
    public string $filter = 'active';

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    public function deleteGoal(int $id): void
    {
        $goal = Goal::find($id);

        if (! $goal || $goal->user_id !== auth()->id()) {
            return;
        }

        $goal->delete();
    }

    public function render(): View
    {
        $calc = app(GoalCalculationService::class);

        $query = Goal::where('user_id', auth()->id())
            ->orderByDesc('created_at');

        if ($this->filter === 'active') {
            $query->where('status', GoalStatus::Active);
        } elseif ($this->filter === 'achieved') {
            $query->where('status', GoalStatus::Achieved);
        }

        $goals = $query->get();

        $goalData = $goals->map(function (Goal $goal) use ($calc) {
            $monthsLeft = $calc->getMonthsLeft($goal);
            $currentPayment = $calc->estimateCurrentMonthlyPayment($goal);
            $completionDate = $calc->predictCompletionDate(
                $goal->target_amount,
                $goal->current_amount,
                $currentPayment,
            );

            return [
                'goal' => $goal,
                'monthly_payment' => $calc->requiredMonthlyPayment(
                    $goal->target_amount,
                    $goal->current_amount,
                    $monthsLeft,
                ),
                'completion_date' => $completionDate?->toDateString(),
            ];
        });

        $allGoals = Goal::where('user_id', auth()->id())->get();
        $totalTarget = $allGoals->sum('target_amount');
        $totalCurrent = $allGoals->sum('current_amount');
        $overallProgress = $totalTarget > 0
            ? round($totalCurrent / $totalTarget * 100, 1)
            : 0;

        return view('livewire.goals.goals-list', [
            'goalData' => $goalData,
            'totalTarget' => $totalTarget,
            'totalCurrent' => $totalCurrent,
            'overallProgress' => $overallProgress,
            'hasGoals' => $allGoals->isNotEmpty(),
        ]);
    }
}
