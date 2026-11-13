<?php

namespace App\Actions;

use App\Models\Transaction;
use App\Models\User;

class DeleteTransaction
{
    /**
     * Deletes the user's own transaction. Returns false when it does not exist or belongs to someone else.
     */
    public function execute(User $user, int $transactionId): bool
    {
        $transaction = Transaction::query()
            ->whereKey($transactionId)
            ->where('user_id', $user->id)
            ->first();

        if ($transaction === null) {
            return false;
        }

        $transaction->delete();

        return true;
    }
}
