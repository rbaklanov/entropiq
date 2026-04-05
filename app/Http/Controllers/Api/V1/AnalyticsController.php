<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\AnalyticsServiceInterface;
use App\Contracts\SubscriptionServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsServiceInterface $analyticsService,
        private readonly SubscriptionServiceInterface $subscriptionService,
    ) {}

    public function expensesByCategory(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        [$from, $to] = $this->resolvePeriod($request);

        return response()->json([
            'data' => $this->analyticsService->getExpensesByCategory(
                $request->user()->id,
                $from,
                $to,
            ),
            'period' => $this->periodMeta($from, $to),
        ]);
    }

    public function balanceDynamics(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
            'granularity' => ['sometimes', 'in:day,month'],
        ]);

        [$from, $to] = $this->resolvePeriod($request);
        $granularity = $request->input('granularity', 'day');

        return response()->json([
            'data' => $this->analyticsService->getBalanceDynamics(
                $request->user()->id,
                $from,
                $to,
                $granularity,
            ),
            'period' => $this->periodMeta($from, $to),
        ]);
    }

    public function personalInflation(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        [$from, $to] = $this->resolvePeriod($request);

        return response()->json([
            'data' => $this->analyticsService->getPersonalInflationBreakdown(
                $request->user()->id,
                $from,
                $to,
            ),
            'period' => $this->periodMeta($from, $to),
        ]);
    }

    public function trends(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        [$from, $to] = $this->resolvePeriod($request);

        return response()->json([
            'data' => $this->analyticsService->getTrends(
                $request->user()->id,
                $from,
                $to,
            ),
            'period' => $this->periodMeta($from, $to),
        ]);
    }

    /**
     * Combined summary for all three analytics tabs.
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        [$from, $to] = $this->resolvePeriod($request);
        $userId = $request->user()->id;

        return response()->json([
            'expenses_by_category' => $this->analyticsService->getExpensesByCategory($userId, $from, $to),
            'balance_dynamics' => $this->analyticsService->getBalanceDynamics($userId, $from, $to),
            'personal_inflation' => $this->analyticsService->getPersonalInflationBreakdown($userId, $from, $to),
            'trends' => $this->analyticsService->getTrends($userId, $from, $to),
            'period' => $this->periodMeta($from, $to),
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(Request $request): array
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->input('to', now()->endOfMonth()->toDateString()));

        if (! $this->subscriptionService->canViewPeriod($request->user(), $from)) {
            abort(403, __('subscription.period_limit'));
        }

        return [$from, $to];
    }

    /**
     * @return array{from: string, to: string}
     */
    private function periodMeta(Carbon $from, Carbon $to): array
    {
        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }
}
