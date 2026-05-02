<?php

use App\Http\Controllers\AiAdviceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\TransactionsController;
use App\Livewire\Advice\AdviceDetail;
use App\Livewire\Advice\AdviceList;
use App\Livewire\Analytics;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\VerifyPage;
use App\Livewire\Dashboard;
use App\Livewire\Goals\GoalDetail;
use App\Livewire\Goals\GoalForm;
use App\Livewire\Goals\GoalsList;
use App\Livewire\Settings\ProfilePage;
use App\Livewire\Settings\SettingsPage;
use App\Livewire\Settings\SubscriptionPage;
use App\Livewire\Transactions\TransactionForm;
use App\Livewire\Transactions\TransactionsList;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => view('pages.guest.landing'))->name('landing');
Route::get('/privacy', fn () => view('pages.guest.privacy'))->name('privacy');
Route::get('/terms', fn () => view('pages.guest.terms'))->name('terms');

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['ru', 'en'], true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('locale.switch');

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
| Onboarding routes (auth required, no onboarding check)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified.phone'])->group(function () {
    Route::get('/onboarding/{step}', [OnboardingController::class, 'step'])
        ->where('step', '[1-3]')
        ->name('onboarding.step');
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/onboarding/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');
});

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified.phone', 'onboarding'])->group(function () {

    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/transactions', TransactionsList::class)->name('transactions.index');
    Route::get('/transactions/create', TransactionForm::class)->name('transactions.create');
    Route::get('/transactions/{transaction}/edit', TransactionForm::class)->name('transactions.edit');
    Route::delete('/transactions/{transaction}', [TransactionsController::class, 'destroy'])->name('transactions.destroy');

    Route::get('/goals', GoalsList::class)->name('goals.index');
    Route::get('/goals/create', GoalForm::class)->name('goals.create');
    Route::get('/goals/{goal}', GoalDetail::class)->name('goals.show');

    Route::get('/analytics', Analytics::class)->name('analytics');

    Route::get('/advice', AdviceList::class)->name('advice.index');
    Route::get('/advice/{advice}', AdviceDetail::class)->name('advice.detail');
    Route::post('/advice/{advice}/rate', [AiAdviceController::class, 'rate'])->name('advice.rate');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', SettingsPage::class)->name('index');
        Route::get('/profile', ProfilePage::class)->name('profile');
        Route::get('/subscription', SubscriptionPage::class)->name('subscription');
    });

    /*
    |----------------------------------------------------------------------
    | Premium-only routes
    |----------------------------------------------------------------------
    */

    Route::middleware('subscription')->group(function () {
        Route::get('/analytics/export', [ExportController::class, 'transactions'])->name('analytics.export');
        Route::get('/advice/scenarios', fn () => view('pages.app.advice.scenarios'))->name('advice.scenarios');
    });
});
