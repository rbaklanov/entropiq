<?php

use App\Http\Controllers\Api\V1\AiAdviceController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GoalsController;
use App\Http\Controllers\Api\V1\NotificationSettingsController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RecurringRulesController;
use App\Http\Controllers\Api\V1\TransactionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    Route::post('/auth/send-code', [AuthController::class, 'sendCode'])->name('auth.sendCode');
    Route::post('/auth/verify-code', [AuthController::class, 'verifyCode'])->name('auth.verifyCode');

    Route::middleware(['auth:sanctum', 'verified.phone'])->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('/user', [ProfileController::class, 'show'])->name('user.show');
        Route::put('/user', [ProfileController::class, 'update'])->name('user.update');
        Route::delete('/user', [ProfileController::class, 'destroy'])->name('user.destroy');

        Route::get('/user/notification-settings', [NotificationSettingsController::class, 'show'])->name('user.notificationSettings.show');
        Route::put('/user/notification-settings', [NotificationSettingsController::class, 'update'])->name('user.notificationSettings.update');

        Route::get('/transactions/summary', [TransactionsController::class, 'summary'])->name('transactions.summary');
        Route::apiResource('transactions', TransactionsController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::apiResource('recurring-rules', RecurringRulesController::class);
        Route::apiResource('goals', GoalsController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::post('/goals/{goal}/contribute', [GoalsController::class, 'contribute'])->name('goals.contribute');
        Route::get('/goals/{goal}/scenarios', [GoalsController::class, 'scenarios'])->name('goals.scenarios');
        Route::get('/goals/{goal}/what-if', [GoalsController::class, 'whatIf'])->name('goals.whatIf');
        Route::get('/categories', fn () => null)->name('categories.index');

        Route::get('/analytics/summary', [AnalyticsController::class, 'summary'])->name('analytics.summary');
        Route::get('/analytics/expenses-by-category', [AnalyticsController::class, 'expensesByCategory'])->name('analytics.expensesByCategory');
        Route::get('/analytics/balance-dynamics', [AnalyticsController::class, 'balanceDynamics'])->name('analytics.balanceDynamics');
        Route::get('/analytics/personal-inflation', [AnalyticsController::class, 'personalInflation'])->name('analytics.personalInflation');
        Route::get('/analytics/trends', [AnalyticsController::class, 'trends'])->name('analytics.trends');

        Route::get('/advice', [AiAdviceController::class, 'index'])->name('advice.index');
        Route::get('/advice/{advice}', [AiAdviceController::class, 'show'])->name('advice.show');
        Route::post('/advice/{advice}/rate', [AiAdviceController::class, 'rate'])->name('advice.rate');

        Route::middleware('subscription')->group(function () {
            Route::get('/analytics/export', fn () => null)->name('analytics.export');
            Route::get('/advice/scenarios', fn () => null)->name('advice.scenarios');
        });
    });
});
