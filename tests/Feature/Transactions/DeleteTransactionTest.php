<?php

use App\Actions\DeleteTransaction;
use App\Livewire\Dashboard;
use App\Livewire\Transactions\TransactionForm;
use App\Livewire\Transactions\TransactionsList;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function transactionOwner(): User
{
    return User::factory()->onboarded()->create(['phone_verified_at' => now()]);
}

function transactionFor(User $user): Transaction
{
    return Transaction::factory()->for($user)->create([
        'category_id' => Category::factory()->expense()->create()->id,
        'date' => now(),
    ]);
}

describe('DeleteTransaction action', function () {
    it('deletes the user own transaction', function () {
        $user = transactionOwner();
        $transaction = transactionFor($user);

        expect(app(DeleteTransaction::class)->execute($user, $transaction->id))->toBeTrue();
        $this->assertModelMissing($transaction);
    });

    it('does not delete a transaction of another user', function () {
        $transaction = transactionFor(transactionOwner());

        expect(app(DeleteTransaction::class)->execute(transactionOwner(), $transaction->id))->toBeFalse();
        $this->assertModelExists($transaction);
    });

    it('returns false for a missing transaction', function () {
        expect(app(DeleteTransaction::class)->execute(transactionOwner(), 999_999))->toBeFalse();
    });
});

describe('edit screen', function () {
    it('shows the delete button when editing', function () {
        $user = transactionOwner();
        $transaction = transactionFor($user);

        $this->actingAs($user)
            ->get(route('transactions.edit', $transaction))
            ->assertOk()
            ->assertSee(__('transactions.delete'))
            ->assertSee('wire:click="delete"', false);
    });

    it('does not show the delete button when creating', function () {
        $this->actingAs(transactionOwner())
            ->get(route('transactions.create'))
            ->assertOk()
            ->assertDontSee('wire:click="delete"', false);
    });

    it('deletes the transaction and redirects to the list', function () {
        $user = transactionOwner();
        $transaction = transactionFor($user);

        Livewire::actingAs($user)
            ->test(TransactionForm::class, ['transaction' => $transaction->id])
            ->call('delete')
            ->assertRedirect(route('transactions.index'));

        $this->assertModelMissing($transaction);
        expect(session('success'))->toBe(__('transactions.deleted'));
    });

    it('does nothing on the create screen', function () {
        $user = transactionOwner();
        $transaction = transactionFor($user);

        Livewire::actingAs($user)
            ->test(TransactionForm::class)
            ->call('delete')
            ->assertNoRedirect();

        $this->assertModelExists($transaction);
    });
});

describe('swipe delete', function () {
    it('deletes from the transactions list', function () {
        $user = transactionOwner();
        $transaction = transactionFor($user);

        Livewire::actingAs($user)
            ->test(TransactionsList::class)
            ->call('deleteTransaction', $transaction->id)
            ->assertDispatched('transaction-deleted');

        $this->assertModelMissing($transaction);
    });

    it('deletes from the dashboard and removes the row', function () {
        $user = transactionOwner();
        $transaction = transactionFor($user);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee("deleteTransaction({$transaction->id})", false)
            ->call('deleteTransaction', $transaction->id)
            ->assertDontSee("deleteTransaction({$transaction->id})", false);

        $this->assertModelMissing($transaction);
    });

    it('ignores a transaction of another user on the dashboard', function () {
        $transaction = transactionFor(transactionOwner());

        Livewire::actingAs(transactionOwner())
            ->test(Dashboard::class)
            ->call('deleteTransaction', $transaction->id);

        $this->assertModelExists($transaction);
    });

    it('ignores a transaction of another user in the list', function () {
        $transaction = transactionFor(transactionOwner());

        Livewire::actingAs(transactionOwner())
            ->test(TransactionsList::class)
            ->call('deleteTransaction', $transaction->id)
            ->assertNotDispatched('transaction-deleted');

        $this->assertModelExists($transaction);
    });
});
