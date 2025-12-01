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
    $documentation = 'default';
    $config = config('l5-swagger.documentations.' . $documentation);

    return view('l5-swagger::index', [
        'documentation' => $documentation,
        'urlToDocs' => url('/api/api-docs-json'),
        'operationsSorter' => $config['operations_sort'] ?? null,
        'configUrl' => $config['additional_config_url'] ?? null,
        'validatorUrl' => $config['validator_url'] ?? null,
        'useAbsolutePath' => $config['paths']['use_absolute_path'] ?? true,
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

// ✅ Route d'accueilreturn
Route::get('/', function () {
     ['message' => 'Orange Money API v1.0.0'];
});
