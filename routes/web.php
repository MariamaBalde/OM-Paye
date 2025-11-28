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

Route::get('/docs', function () {
    return view('l5-swagger::index', [
        'documentation' => 'default',
        'urlToDocs' => url('/api/api-docs-json'),  // ✅ Chemin correct
    ]);
});

Route::get('/', function () {
    return ['message' => 'Orange Money API v1.0.0'];
});

// Redirection depuis /api-docs vers /docs
Route::get('/api-docs', function () {
    return view('swagger');
});

// Serve static API documentation
Route::get('/storage/api-docs/static-api-docs.json', function () {
    $path = storage_path('api-docs/static-api-docs.json');
    if (file_exists($path)) {
        return response()->file($path, ['Content-Type' => 'application/json']);
    }
    return response()->json(['error' => 'Documentation not found'], 404);
});

// Fallback redirect
Route::get('/docs', function () {
    return redirect('/api-docs');
});

Route::get('/docs/api-docs.json', function () {
    $path = storage_path('api-docs/api-docs.json');
    if (!file_exists($path)) {
        abort(404, 'Swagger JSON file not found.');
    }
    return response()->file($path);
});
