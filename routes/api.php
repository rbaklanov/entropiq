<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GoalsController;
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
        Route::get('/user', fn () => null)->name('user.show');

        Route::get('/transactions/summary', [TransactionsController::class, 'summary'])->name('transactions.summary');
        Route::apiResource('transactions', TransactionsController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::apiResource('recurring-rules', RecurringRulesController::class);
        Route::apiResource('goals', GoalsController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::post('/goals/{goal}/contribute', [GoalsController::class, 'contribute'])->name('goals.contribute');
        Route::get('/goals/{goal}/scenarios', [GoalsController::class, 'scenarios'])->name('goals.scenarios');
        Route::get('/goals/{goal}/what-if', [GoalsController::class, 'whatIf'])->name('goals.whatIf');
        Route::get('/categories', fn () => null)->name('categories.index');

        Route::get('/analytics/summary', fn () => null)->name('analytics.summary');
        Route::get('/advice', fn () => null)->name('advice.index');

        Route::middleware('subscription')->group(function () {
            Route::get('/analytics/export', fn () => null)->name('analytics.export');
            Route::get('/advice/scenarios', fn () => null)->name('advice.scenarios');
        });
    });
});
