<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CompteController;
use App\Http\Middleware\RatingMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Orange Money MVP - Version 1
|--------------------------------------------------------------------------
|
| Architecture minimale avec seulement les 11 endpoints essentiels
| - Authentification (4 endpoints)
| - Transactions (5 endpoints)
| - Comptes (2 endpoints)
|
*/

// ✅ Route pour servir le fichier JSON (accessible publiquement)
Route::get('/api-docs-json', function () {
    $jsonPath = storage_path('api-docs/api-docs.json');
    if (!file_exists($jsonPath)) {
        return response()->json(['error' => 'Documentation not found'], 404);
    }
    return response()->file($jsonPath, ['Content-Type' => 'application/json']);
});

// ✅ Route pour afficher la documentation Swagger UI (AVANT le préfixe v1)
Route::get('/docs', function () {
    return view('l5-swagger::index', [
        'documentation' => 'default',
        'urlToDocs' => '/api-docs-json',  // ✅ URL RELATIVE
    ]);
})->name('l5-swagger.docs');

Route::prefix('v1')->group(function () {

    // Routes publiques (sans authentification)
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('verify-code-secret', [AuthController::class, 'verifyCodeSecret']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // Test route for getting token (temporary)
    Route::get('test-token', function () {
        $user = \App\Models\User::where('telephone', '770129911')->first();
        if ($user) {
            $token = $user->createToken('TestToken')->accessToken;
            return response()->json(['token' => $token]);
        }
        return response()->json(['error' => 'User not found'], 404);
    });

    // Authenticated routes - MVP Orange Money
    Route::middleware(['auth:api', 'check.token.expiration'])->group(function () {

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

        // Transactions financières (6 endpoints)
        Route::prefix('transactions')->middleware(RatingMiddleware::class . ':50,1')->group(function () {
            Route::get('{id}', [TransactionController::class, 'show']);
            Route::post('transfert', [TransactionController::class, 'transfer']);
            Route::post('paiement', [TransactionController::class, 'payment']);
            Route::post('depot', [TransactionController::class, 'deposit']);
            Route::post('retrait', [TransactionController::class, 'withdrawal']);
            Route::get('{numero_compte}/history', [TransactionController::class, 'index']);
        });

    });

});
