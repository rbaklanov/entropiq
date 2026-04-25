<?php

namespace App\Livewire;

use App\Contracts\InflationServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Component;

class InflationCalculator extends Component
{
    private const DEFAULT_ANNUAL_RATE = 0.095;

    public string $amountInput = '500 000';

    public string $period = '1y';

    public ?int $realValue = null;

    public ?int $loss = null;

    public ?float $percentage = null;

    public function mount(): void
    {
        $this->calculate();
    }

    public function updatedAmountInput(): void
    {
        $this->formatAmountInput();
        $this->calculate();
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->calculate();
    }

    public function render(): View
    {
        return view('livewire.inflation-calculator');
    }

    private function formatAmountInput(): void
    {
        $cleaned = preg_replace('/\D/', '', $this->amountInput);
        $number = (int) $cleaned;

        if ($number > 0) {
            $this->amountInput = number_format($number, 0, '', ' ');
        }
    }

    private function calculate(): void
    {
        $cleaned = preg_replace('/\D/', '', $this->amountInput);
        $amountRubles = (int) $cleaned;

        if ($amountRubles <= 0) {
            $this->realValue = null;
            $this->loss = null;
            $this->percentage = null;

            return;
        }

        $amountKopecks = $amountRubles * 100;

        $years = match ($this->period) {
            '2y' => 2,
            '5y' => 5,
            default => 1,
        };

        $fromDate = Carbon::now()->subYears($years)->startOfMonth();
        $toDate = Carbon::now()->startOfMonth();

        $inflationService = app(InflationServiceInterface::class);
        $realValueKopecks = $inflationService->calculateRealValue($amountKopecks, $fromDate, $toDate);

        if ($realValueKopecks === $amountKopecks) {
            $compoundRate = pow(1 + self::DEFAULT_ANNUAL_RATE, $years);
            $realValueKopecks = (int) round($amountKopecks / $compoundRate);
        }

        $this->realValue = $realValueKopecks;
        $this->loss = $amountKopecks - $realValueKopecks;
        $this->percentage = round($realValueKopecks / $amountKopecks * 100, 1);
    }
}
