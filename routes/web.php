<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ✅ Route principale pour la documentation
Route::get('/docs', function () {
    return view('l5-swagger::index', [
        'documentation' => 'default',
        'urlToDocs' => url('/api/api-docs-json'),
    ]);
});

// ✅ Route pour servir le JSON (depuis api.php aussi)
Route::get('/api/api-docs-json', function () {
    $jsonPath = storage_path('api-docs/api-docs.json');
    if (!file_exists($jsonPath)) {
        return response()->json(['error' => 'Documentation not found'], 404);
    }
    return response()->file($jsonPath, ['Content-Type' => 'application/json']);
});

// ✅ Route d'accueil
Route::get('/', function () {
    return ['message' => 'Orange Money API v1.0.0'];
});
