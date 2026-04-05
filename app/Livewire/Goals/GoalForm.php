<?php

namespace App\Livewire\Goals;

use App\Contracts\SubscriptionServiceInterface;
use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Services\GoalCalculationService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** @property-read int $monthlyPayment */
#[Layout('components.layouts.app')]
class GoalForm extends Component
{
    private const TOTAL_STEPS = 4;

    public int $step = 1;

    public string $type = '';

    public string $name = '';

    public int $targetAmount = 0;

    public string $targetAmountDisplay = '';

    public int $initialAmount = 0;

    public string $initialAmountDisplay = '';

    public string $targetDate = '';

    public int $presetMonths = 0;

    public function nextStep(): void
    {
        $this->validateStep();
        $this->step = min($this->step + 1, self::TOTAL_STEPS);
    }

    public function prevStep(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->step) {
            $this->step = $step;
        }
    }

    public function selectType(string $type): void
    {
        $this->type = $type;
        $this->nextStep();
    }

    public function setPresetMonths(int $months): void
    {
        $this->presetMonths = $months;
        $this->targetDate = now()->addMonths($months)->toDateString();
    }

    public function updatedTargetAmountDisplay(string $value): void
    {
        $this->targetAmount = $this->parseKopecks($value);
        $this->targetAmountDisplay = $this->formatDisplay($value);
    }

    public function updatedInitialAmountDisplay(string $value): void
    {
        $this->initialAmount = $this->parseKopecks($value);
        $this->initialAmountDisplay = $this->formatDisplay($value);
    }

    private function parseKopecks(string $value): int
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

        return $kopecks;
    }

    private function formatDisplay(string $value): string
    {
        $normalized = str_replace(',', '.', $value);
        $normalized = preg_replace('/[^\d.]/', '', $normalized);

        $parts = explode('.', $normalized, 2);
        $integer = ltrim($parts[0], '0') ?: '';
        $decimal = isset($parts[1]) ? substr($parts[1], 0, 2) : null;

        if ($integer === '' && $decimal === null) {
            return '';
        }

        $formatted = $integer !== '' ? number_format((int) $integer, 0, '', ' ') : '0';

        return $decimal !== null ? "{$formatted}.{$decimal}" : $formatted;
    }

    public function save(): void
    {
        $this->validateStep();

        $subscriptionService = app(SubscriptionServiceInterface::class);

        if (! $subscriptionService->canCreateGoal(auth()->user())) {
            session()->flash('error', __('goals.limit_reached'));
            $this->redirectRoute('goals.index');

            return;
        }

        $goal = auth()->user()->goals()->create([
            'name' => $this->name,
            'type' => $this->type,
            'status' => GoalStatus::Active,
            'target_amount' => $this->targetAmount,
            'current_amount' => $this->initialAmount,
            'started_at' => now()->toDateString(),
            'target_date' => $this->targetDate ?: null,
        ]);

        if ($this->initialAmount > 0) {
            $goal->contributions()->create([
                'amount' => $this->initialAmount,
                'date' => now()->toDateString(),
            ]);
        }

        session()->flash('success', __('goals.saved'));
        $this->redirectRoute('goals.index');
    }

    public function getMonthlyPaymentProperty(): int
    {
        if ($this->targetAmount <= 0) {
            return 0;
        }

        $months = 12;
        if ($this->targetDate !== '') {
            $months = max(1, (int) now()->diffInMonths($this->targetDate, false));
        }

        return app(GoalCalculationService::class)->requiredMonthlyPayment(
            $this->targetAmount,
            $this->initialAmount,
            $months,
        );
    }

    private function validateStep(): void
    {
        match ($this->step) {
            1 => $this->validate([
                'type' => ['required', 'string'],
            ], [
                'type.required' => __('goals.validation.type_required'),
            ]),
            2 => $this->validate([
                'name' => ['required', 'string', 'max:255'],
            ], [
                'name.required' => __('goals.validation.name_required'),
            ]),
            3 => $this->validate([
                'targetAmount' => ['required', 'integer', 'min:1'],
            ], [
                'targetAmount.required' => __('goals.validation.amount_required'),
                'targetAmount.min' => __('goals.validation.amount_required'),
            ]),
            default => null,
        };
    }

    public function render(): View
    {
        return view('livewire.goals.goal-form', [
            'goalTypes' => GoalType::cases(),
            'totalSteps' => self::TOTAL_STEPS,
            'monthlyPayment' => $this->monthlyPayment,
        ]);
    }
}
