<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CompteController;
use App\Http\Middleware\RatingMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Orange Money MVP
|--------------------------------------------------------------------------
|
| Architecture minimale avec seulement les 11 endpoints essentiels
| - Authentification (4 endpoints)
| - Transactions (5 endpoints)
| - Comptes (2 endpoints)
|
*/

// Routes publiques (sans authentification)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-code-secret', [AuthController::class, 'verifyCodeSecret']);
});

// Authenticated routes - MVP Orange Money
Route::middleware(['auth:api'])->group(function () {

    // Authentification (2 endpoints supplémentaires)
    Route::prefix('auth')->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // Comptes bancaires (2 endpoints)
    Route::prefix('comptes')->group(function () {
        Route::get('balance', [CompteController::class, 'balance']);
        Route::get('{numero}', [CompteController::class, 'checkAccount']);
    });

    // Transactions financières (5 endpoints)
    Route::prefix('transactions')->middleware(RatingMiddleware::class . ':50,1')->group(function () {
        Route::post('transfer', [TransactionController::class, 'transfer']);
        Route::post('payment', [TransactionController::class, 'payment']);
        Route::post('verify-code', [TransactionController::class, 'verifyCode']);
        Route::get('history', [TransactionController::class, 'history']);
        Route::get('{id}', [TransactionController::class, 'show']);
    });

});
