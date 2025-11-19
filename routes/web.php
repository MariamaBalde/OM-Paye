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

Route::get('/', function () {
    return view('welcome');
});

// Custom Swagger UI route
Route::get('/api-docs', function () {
    $documentation = 'default';
    $urlToDocs = route('l5-swagger.'.$documentation.'.api');
    $configUrl = null;
    $validatorUrl = null;
    $operationsSorter = null;

    return view('vendor.l5-swagger.index', compact(
        'documentation',
        'urlToDocs',
        'configUrl',
        'validatorUrl',
        'operationsSorter'
    ));
});

// Fallback redirect
Route::get('/docs', function () {
    return redirect('/api-docs');
});
