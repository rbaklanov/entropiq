<?php

namespace App\Contracts;

use Illuminate\Support\Carbon;

interface AnalyticsServiceInterface
{
    /**
     * Expenses grouped by category for a donut chart.
     *
     * @return array<int, array{
     *     category_id: int,
     *     category_name: array<string, string>,
     *     category_icon: string|null,
     *     category_color: string|null,
     *     total: int,
     *     count: int,
     *     share: float,
     * }>
     */
    public function getExpensesByCategory(int $userId, Carbon $from, Carbon $to): array;

    /**
     * Balance dynamics by day or month (nominal + real value adjusted for inflation).
     *
     * @return array<int, array{
     *     date: string,
     *     income: int,
     *     expense: int,
     *     balance: int,
     *     cumulative_balance: int,
     *     real_cumulative_balance: int,
     * }>
     */
    public function getBalanceDynamics(int $userId, Carbon $from, Carbon $to, string $granularity = 'day'): array;

    /**
     * Personal inflation breakdown: each category's share, CPI, and contribution.
     *
     * @return array{
     *     personal_rate: float,
     *     official_rate: float,
     *     breakdown: array<int, array{
     *         category_id: int,
     *         category_name: array<string, string>,
     *         category_icon: string|null,
     *         share: float,
     *         category_cpi: float,
     *         contribution: float,
     *     }>,
     * }
     */
    public function getPersonalInflationBreakdown(int $userId, Carbon $from, Carbon $to): array;

    /**
     * Category expense trends compared with the previous period of equal length.
     *
     * @return array<int, array{
     *     category_id: int,
     *     category_name: array<string, string>,
     *     category_icon: string|null,
     *     current_total: int,
     *     previous_total: int,
     *     change_percent: float|null,
     *     direction: string,
     * }>
     */
    public function getTrends(int $userId, Carbon $from, Carbon $to): array;
}
