<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class TransactionService
{
    private const PER_PAGE = 20;

    /**
     * @param array{
     *     type?: string,
     *     category_id?: int,
     *     from?: string,
     *     to?: string,
     *     search?: string,
     * } $filters
     * @return LengthAwarePaginator<int, Transaction>
     */
    public function getForPeriod(int $userId, array $filters = [], int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return Transaction::where('user_id', $userId)
            ->with('category')
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['category_id'] ?? null, fn (Builder $q, int $id) => $q->byCategory($id))
            ->when(
                ($filters['from'] ?? null) && ($filters['to'] ?? null),
                fn (Builder $q) => $q->forPeriod(
                    Carbon::parse($filters['from']),
                    Carbon::parse($filters['to']),
                ),
            )
            ->when(
                $filters['search'] ?? null,
                fn (Builder $q, string $search) => $q->where('comment', 'ilike', "%{$search}%"),
            )
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return array{income: int, expense: int, balance: int}
     */
    public function getSummary(int $userId, Carbon $from, Carbon $to): array
    {
        $query = Transaction::where('user_id', $userId)->forPeriod($from, $to);

        $income = (clone $query)->income()->sum('amount');
        $expense = (clone $query)->expense()->sum('amount');

        return [
            'income' => (int) $income,
            'expense' => (int) $expense,
            'balance' => (int) ($income - $expense),
        ];
    }

    /**
     * @return array<int, array{category_id: int, total: int, count: int}>
     */
    public function getByCategory(int $userId, Carbon $from, Carbon $to, TransactionType $type): array
    {
        return Transaction::where('user_id', $userId)
            ->forPeriod($from, $to)
            ->where('type', $type)
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn (Transaction $row) => [
                'category_id' => (int) $row->category_id,
                'total' => (int) $row->getAttribute('total'),
                'count' => (int) $row->getAttribute('count'),
            ])
            ->all();
    }
}
