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
    return redirect('/docs');
});
