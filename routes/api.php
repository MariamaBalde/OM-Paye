<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CompteController;
use App\Http\Middleware\RatingMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Orange Money
|--------------------------------------------------------------------------
|
| Routes API versionnées avec architecture moderne
| - Versioning explicite dans l'URL (/v1/)
| - Middleware de taux pour protection
| - Format de réponse standardisé
| - Conformité US 2.0 pour les endpoints GET
|
*/

// Routes publiques (sans authentification)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// Route de test temporaire (sans auth)
Route::get('test/profile', [AuthController::class, 'profile']);

// Routes OAuth2 Passport
Route::prefix('oauth')->group(function () {
    Route::post('token', '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');
    Route::post('refresh', '\Laravel\Passport\Http\Controllers\TransientTokenController@refresh');
});

// API Version 1 - Architecture moderne avec US 2.0
Route::prefix('v1')->middleware(['auth:api'])->group(function () {

    // Authentification
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('profile', [AuthController::class, 'profile']);
    });

    // Comptes bancaires (US 2.0 compliant)
    Route::prefix('comptes')->group(function () {
        Route::get('/', [CompteController::class, 'index']);           // US 2.0: Lister comptes avec filtres
        Route::get('/balance', [CompteController::class, 'balance']);  // Solde compte principal
        Route::get('/qr-code', [CompteController::class, 'qrCode']);   // Générer QR code
        Route::get('/{compte}', [CompteController::class, 'show']);     // Détails compte
    });

    // Transactions financières
    Route::prefix('transactions')->middleware(RatingMiddleware::class . ':50,1')->group(function () {
        Route::post('transfer', [TransactionController::class, 'transfer']);     // Initier transfert
        Route::post('payment', [TransactionController::class, 'payment']);       // Effectuer paiement
        Route::post('verify-code', [TransactionController::class, 'verifyCode']); // Vérifier transaction
        Route::get('history', [TransactionController::class, 'history']);        // Historique (US 2.0)
    });

});

// API Legacy - Pour compatibilité (sera dépréciée)
Route::middleware('auth:api')->group(function () {

    // Authentification (legacy)
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('profile', [AuthController::class, 'profile']);
    });

    // Comptes bancaires (legacy)
    Route::prefix('comptes')->group(function () {
        Route::get('/', [CompteController::class, 'index']);
        Route::get('/balance', [CompteController::class, 'balance']);
        Route::get('/qr-code', [CompteController::class, 'qrCode']);
        Route::get('/{compte}', [CompteController::class, 'show']);
    });

    // Transactions financières (legacy)
    Route::prefix('transactions')->group(function () {
        Route::post('transfer', [TransactionController::class, 'transfer']);
        Route::post('payment', [TransactionController::class, 'payment']);
        Route::post('verify-code', [TransactionController::class, 'verifyCode']);
        Route::get('history', [TransactionController::class, 'history']);
    });

});
