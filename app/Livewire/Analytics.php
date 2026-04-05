<?php

namespace App\Livewire;

use App\Contracts\AnalyticsServiceInterface;
use App\Contracts\SubscriptionServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Analytics extends Component
{
    #[Url]
    public string $tab = 'categories';

    #[Url]
    public string $period = 'month';

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    /** @return array{from: Carbon, to: Carbon} */
    private function resolvePeriod(): array
    {
        $now = Carbon::now();

        return match ($this->period) {
            'week' => [
                'from' => $now->copy()->startOfWeek(),
                'to' => $now->copy()->endOfWeek(),
            ],
            'quarter' => [
                'from' => $now->copy()->subMonths(3)->startOfDay(),
                'to' => $now->copy()->endOfDay(),
            ],
            'year' => [
                'from' => $now->copy()->startOfYear(),
                'to' => $now->copy()->endOfDay(),
            ],
            'all' => [
                'from' => Carbon::create(2020, 1, 1)->startOfDay(),
                'to' => $now->copy()->endOfDay(),
            ],
            default => [
                'from' => $now->copy()->startOfMonth(),
                'to' => $now->copy()->endOfMonth(),
            ],
        };
    }

    private function getGranularity(): string
    {
        return in_array($this->period, ['week', 'month', 'quarter'], true) ? 'day' : 'month';
    }

    public function render(): View
    {
        $analytics = app(AnalyticsServiceInterface::class);
        $subscriptionService = app(SubscriptionServiceInterface::class);
        $userId = auth()->id();
        $period = $this->resolvePeriod();
        $from = $period['from'];
        $to = $period['to'];
        $locale = app()->getLocale();

        $periodLocked = ! $subscriptionService->canViewPeriod(auth()->user(), $from);

        if ($periodLocked) {
            return view('livewire.analytics', [
                'periodLocked' => true,
                'locale' => $locale,
            ]);
        }

        $data = [];

        if ($this->tab === 'categories') {
            $data = $this->buildCategoriesData($analytics, $userId, $from, $to, $locale);
        } elseif ($this->tab === 'balance') {
            $data = $this->buildBalanceData($analytics, $userId, $from, $to);
        } elseif ($this->tab === 'inflation') {
            $data = $this->buildInflationData($analytics, $userId, $from, $to);
        }

        return view('livewire.analytics', array_merge($data, [
            'periodLocked' => false,
            'locale' => $locale,
        ]));
    }

    /** @return array<string, mixed> */
    private function buildCategoriesData(
        AnalyticsServiceInterface $analytics,
        int $userId,
        Carbon $from,
        Carbon $to,
        string $locale,
    ): array {
        $expenses = $analytics->getExpensesByCategory($userId, $from, $to);
        $trends = $analytics->getTrends($userId, $from, $to);
        $trendsMap = collect($trends)->keyBy('category_id');

        return [
            'expenses' => $expenses,
            'trendsMap' => $trendsMap,
            'chartData' => array_column($expenses, 'total'),
            'chartLabels' => array_map(fn (array $e) => $e['category_name'][$locale] ?? '—', $expenses),
            'chartColors' => array_map(fn (array $e) => $e['category_color'] ?? '#6366F1', $expenses),
        ];
    }

    /** @return array<string, mixed> */
    private function buildBalanceData(
        AnalyticsServiceInterface $analytics,
        int $userId,
        Carbon $from,
        Carbon $to,
    ): array {
        $granularity = $this->getGranularity();
        $dynamics = $analytics->getBalanceDynamics($userId, $from, $to, $granularity);

        $lastRow = ! empty($dynamics) ? end($dynamics) : null;
        $inflationLoss = $lastRow
            ? abs($lastRow['cumulative_balance'] - $lastRow['real_cumulative_balance'])
            : 0;

        $dateFormat = $granularity === 'month' ? 'M y' : 'd M';

        return [
            'dynamics' => $dynamics,
            'inflationLoss' => $inflationLoss,
            'chartSeries' => [
                ['name' => __('analytics.nominal_balance'), 'data' => array_column($dynamics, 'cumulative_balance')],
                ['name' => __('analytics.real_balance'), 'data' => array_column($dynamics, 'real_cumulative_balance')],
            ],
            'chartCategories' => array_map(
                fn (array $d) => Carbon::parse($d['date'])->translatedFormat($dateFormat),
                $dynamics,
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function buildInflationData(
        AnalyticsServiceInterface $analytics,
        int $userId,
        Carbon $from,
        Carbon $to,
    ): array {
        $breakdown = $analytics->getPersonalInflationBreakdown($userId, $from, $to);

        return [
            'personalRate' => $breakdown['personal_rate'],
            'officialRate' => $breakdown['official_rate'],
            'breakdown' => $breakdown['breakdown'],
        ];
    }
}
