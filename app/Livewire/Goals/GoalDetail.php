<?php

namespace App\Livewire\Goals;

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Services\GoalCalculationService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read int $monthlyPayment
 * @property-read int $monthlyPaymentInflation
 */
#[Layout('components.layouts.app')]
class GoalDetail extends Component
{
    public Goal $goal;

    public int $whatIfAmount = 500000;

    public bool $showContributeForm = false;

    public int $contributeAmount = 0;

    public string $contributeAmountDisplay = '';

    public function mount(Goal $goal): void
    {
        abort_unless($goal->user_id === auth()->id(), 403);
        $this->goal = $goal;
    }

    public function getMonthlyPaymentProperty(): int
    {
        $calc = app(GoalCalculationService::class);

        return $calc->requiredMonthlyPayment(
            $this->goal->target_amount,
            $this->goal->current_amount,
            $calc->getMonthsLeft($this->goal),
        );
    }

    public function getMonthlyPaymentInflationProperty(): int
    {
        $calc = app(GoalCalculationService::class);

        return $calc->requiredMonthlyPaymentWithInflation(
            $this->goal->target_amount,
            $this->goal->current_amount,
            $calc->getMonthsLeft($this->goal),
        );
    }

    public function updatedContributeAmountDisplay(string $value): void
    {
        $normalized = str_replace(',', '.', $value);
        $normalized = preg_replace('/[^\d.]/', '', $normalized);

        $parts = explode('.', $normalized, 2);
        $integer = ltrim($parts[0], '0') ?: '';
        $decimal = isset($parts[1]) ? substr($parts[1], 0, 2) : null;

        $kopecks = ((int) $integer) * 100;
        if ($decimal !== null) {
            $kopecks += (int) str_pad($decimal, 2, '0');
        }

        $this->contributeAmount = $kopecks;

        if ($integer === '' && $decimal === null) {
            $this->contributeAmountDisplay = '';

            return;
        }

        $formatted = $integer !== '' ? number_format((int) $integer, 0, '', ' ') : '0';
        $this->contributeAmountDisplay = $decimal !== null
            ? "{$formatted}.{$decimal}"
            : $formatted;
    }

    public function contribute(): void
    {
        $this->validate([
            'contributeAmount' => ['required', 'integer', 'min:1'],
        ], [
            'contributeAmount.required' => __('goals.validation.amount_required'),
            'contributeAmount.min' => __('goals.validation.amount_required'),
        ]);

        if ($this->goal->isAchieved()) {
            session()->flash('error', __('goals.already_achieved'));

            return;
        }

        $this->goal->contributions()->create([
            'amount' => $this->contributeAmount,
            'date' => now()->toDateString(),
        ]);

        $this->goal->increment('current_amount', $this->contributeAmount);
        $this->goal->refresh();

        if ($this->goal->current_amount >= $this->goal->target_amount) {
            $this->goal->update(['status' => GoalStatus::Achieved]);
            $this->goal->refresh();
            session()->flash('success', __('goals.achieved'));
        } else {
            session()->flash('success', __('goals.contribution_added'));
        }

        $this->showContributeForm = false;
        $this->contributeAmount = 0;
        $this->contributeAmountDisplay = '';
    }

    public function deleteGoal(): void
    {
        $this->goal->contributions()->delete();
        $this->goal->delete();

        session()->flash('success', __('goals.deleted'));
        $this->redirectRoute('goals.index');
    }

    public function render(): View
    {
        $calc = app(GoalCalculationService::class);
        $monthsLeft = $calc->getMonthsLeft($this->goal);
        $scenarios = $calc->buildScenarios($this->goal);
        $whatIf = $calc->whatIf($this->goal, $this->whatIfAmount);
        $contributions = $this->goal->contributions()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $paceStatus = $this->calculatePaceStatus($calc);

        return view('livewire.goals.goal-detail', [
            'progress' => $this->goal->progressPercent(),
            'remaining' => $this->goal->remainingAmount(),
            'monthsLeft' => $monthsLeft,
            'scenarios' => $scenarios,
            'whatIf' => $whatIf,
            'contributions' => $contributions,
            'paceStatus' => $paceStatus,
        ]);
    }

    private function calculatePaceStatus(GoalCalculationService $calc): string
    {
        if ($this->goal->isAchieved()) {
            return 'achieved';
        }

        if (! $this->goal->target_date) {
            return 'on_track';
        }

        $monthsLeft = $calc->getMonthsLeft($this->goal);
        $totalMonths = max(1, (int) $this->goal->started_at->diffInMonths($this->goal->target_date));
        $elapsedMonths = max(1, (int) $this->goal->started_at->diffInMonths(now()));

        $expectedProgress = min(100, ($elapsedMonths / $totalMonths) * 100);
        $actualProgress = $this->goal->progressPercent();

        if ($actualProgress >= $expectedProgress + 5) {
            return 'ahead';
        }

        if ($actualProgress <= $expectedProgress - 5) {
            return 'behind';
        }

        return 'on_track';
    }
}
