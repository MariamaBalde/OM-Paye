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
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// Authenticated routes - MVP Orange Money
Route::middleware(['auth:api'])->group(function () {

    // Authentification (2 endpoints supplémentaires)
    Route::prefix('auth')->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // Dashboard client
    Route::prefix('client')->group(function () {
        Route::get('dashboard', [AuthController::class, 'dashboard']);
    });

    // Comptes bancaires (1 endpoint)
    Route::prefix('comptes')->group(function () {
        Route::get('{numcompte}/balance', [CompteController::class, 'balance']);
    });

    // Transactions financières (3 endpoints)
    Route::prefix('transactions')->middleware(RatingMiddleware::class . ':50,1')->group(function () {
        Route::post('transfer', [TransactionController::class, 'transfer']);
        Route::post('payment', [TransactionController::class, 'payment']);
        Route::get('{numerocompte}/history', [TransactionController::class, 'history']);
    });

});
