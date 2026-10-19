<?php

namespace App\Livewire;

use App\Contracts\AnalyticsServiceInterface;
use App\Contracts\InflationServiceInterface;
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
        $exportParams = ['from' => $from->toDateString(), 'to' => $to->toDateString()];

        if ($periodLocked) {
            return view('livewire.analytics', [
                'periodLocked' => true,
                'locale' => $locale,
                'exportParams' => $exportParams,
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
            'exportParams' => $exportParams,
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
            ...$this->donutSlices($expenses, $locale),
        ];
    }

    /**
     * The largest categories get muted palette colours, the rest share one grey "other" slice:
     * nobody can tell seventeen hues apart on a ring.
     *
     * @param  array<int, array<string, mixed>>  $expenses  sorted by total, largest first
     * @return array{chartData: list<int>, chartLabels: list<string>, chartColors: list<string>, categorySwatches: array<int, string>}
     */
    private function donutSlices(array $expenses, string $locale): array
    {
        $palette = ['gray-900', 'primary-600', 'success-600', 'premium-500', 'primary-300'];
        $otherColor = 'gray-300';

        $highlighted = array_slice($expenses, 0, count($palette));
        $rest = array_slice($expenses, count($palette));

        $data = [];
        $labels = [];
        $colors = [];
        $swatches = [];

        foreach ($highlighted as $index => $expense) {
            $data[] = (int) $expense['total'];
            $labels[] = $expense['category_name'][$locale] ?? '—';
            $colors[] = $palette[$index];
            $swatches[(int) $expense['category_id']] = $palette[$index];
        }

        if (count($rest) === 1) {
            $data[] = (int) $rest[0]['total'];
            $labels[] = $rest[0]['category_name'][$locale] ?? '—';
            $colors[] = $otherColor;
            $swatches[(int) $rest[0]['category_id']] = $otherColor;
        }

        if (count($rest) > 1) {
            $data[] = (int) array_sum(array_column($rest, 'total'));
            $labels[] = __('analytics.other_categories', ['count' => count($rest)]);
            $colors[] = $otherColor;

            foreach ($rest as $expense) {
                $swatches[(int) $expense['category_id']] = $otherColor;
            }
        }

        return [
            'chartData' => $data,
            'chartLabels' => $labels,
            'chartColors' => $colors,
            'categorySwatches' => $swatches,
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

        $inflation = app(InflationServiceInterface::class);
        $cpiPublishedMonth = $inflation->hasEstimatedMonths($from, $to)
            ? $inflation->latestPublishedPeriod()?->translatedFormat('F Y')
            : null;

        return [
            'cpiPublishedMonth' => $cpiPublishedMonth,
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
            'cpiBasisMonth' => app(InflationServiceInterface::class)->latestPublishedPeriod()?->translatedFormat('F Y'),
            'personalRate' => $breakdown['personal_rate'],
            'officialRate' => $breakdown['official_rate'],
            'breakdown' => $breakdown['breakdown'],
        ];
    }
}
