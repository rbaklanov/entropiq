<?php

namespace App\Services;

use App\Models\RecurringRule;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RecurringService
{
    /**
     * @return Collection<int, Transaction>
     */
    public function processDueRules(?Carbon $now = null): Collection
    {
        $now ??= now();

        $rules = RecurringRule::due()
            ->with('category')
            ->get();

        $created = collect();

        foreach ($rules as $rule) {
            while ($rule->next_run_at <= $now) {
                $transaction = $this->createTransactionFromRule($rule);
                $created->push($transaction);

                $rule->next_run_at = $rule->calculateNextRunAt();
            }

            $rule->save();
        }

        return $created;
    }

    /**
     * @param array{
     *     type: string,
     *     amount: int,
     *     category_id: int,
     *     interval: string,
     *     start_date?: string,
     *     currency_code?: string,
     *     comment?: string|null,
     * } $data
     */
    public function createRule(int $userId, array $data): RecurringRule
    {
        $nextRunAt = Carbon::parse($data['start_date'] ?? now()->toDateString());

        return RecurringRule::create([
            'user_id' => $userId,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'category_id' => $data['category_id'],
            'currency_code' => $data['currency_code'] ?? 'RUB',
            'comment' => $data['comment'] ?? null,
            'interval' => $data['interval'],
            'next_run_at' => $nextRunAt,
            'is_active' => true,
        ]);
    }

    public function deactivate(RecurringRule $rule): void
    {
        $rule->update(['is_active' => false]);
    }

    public function activate(RecurringRule $rule): void
    {
        $rule->update(['is_active' => true]);
    }

    private function createTransactionFromRule(RecurringRule $rule): Transaction
    {
        return Transaction::create([
            'user_id' => $rule->user_id,
            'category_id' => $rule->category_id,
            'type' => $rule->type,
            'amount' => $rule->amount,
            'currency_code' => $rule->currency_code,
            'date' => $rule->next_run_at->toDateString(),
            'comment' => $rule->comment,
            'is_recurring' => true,
        ]);
    }
}
