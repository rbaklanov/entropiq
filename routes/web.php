<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionsController;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\VerifyPage;
use App\Livewire\Transactions\TransactionForm;
use App\Livewire\Transactions\TransactionsList;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => view('pages.guest.landing'))->name('landing');

/*
|--------------------------------------------------------------------------
| Auth routes (login / verify / logout)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', LoginPage::class)->name('auth.login');
    Route::get('/verify', VerifyPage::class)->name('auth.verify');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified.phone'])->group(function () {

    Route::get('/dashboard', fn () => view('pages.app.dashboard'))->name('dashboard');

    Route::get('/transactions', TransactionsList::class)->name('transactions.index');
    Route::get('/transactions/create', TransactionForm::class)->name('transactions.create');
    Route::get('/transactions/{transaction}/edit', TransactionForm::class)->name('transactions.edit');
    Route::delete('/transactions/{transaction}', [TransactionsController::class, 'destroy'])->name('transactions.destroy');

    Route::get('/goals', fn () => view('pages.app.goals.index'))->name('goals.index');
    Route::get('/goals/create', fn () => view('pages.app.goals.create'))->name('goals.create');
    Route::get('/goals/{goal}', fn () => view('pages.app.goals.show'))->name('goals.show');

    Route::get('/analytics', fn () => view('pages.app.analytics'))->name('analytics');

    Route::get('/advice', fn () => view('pages.app.advice.index'))->name('advice.index');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', fn () => view('pages.app.settings.index'))->name('index');
        Route::get('/profile', fn () => view('pages.app.settings.profile'))->name('profile');
        Route::get('/subscription', fn () => view('pages.app.settings.subscription'))->name('subscription');
        Route::get('/notifications', fn () => view('pages.app.settings.notifications'))->name('notifications');
    });

    /*
    |----------------------------------------------------------------------
    | Premium-only routes
    |----------------------------------------------------------------------
    */

    Route::middleware('subscription')->group(function () {
        Route::get('/analytics/export', fn () => null)->name('analytics.export');
        Route::get('/advice/scenarios', fn () => view('pages.app.advice.scenarios'))->name('advice.scenarios');
    });
});
