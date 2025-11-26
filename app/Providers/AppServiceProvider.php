<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Transaction;
use App\Observers\UserObserver;
use App\Observers\TransactionObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Override the L5Swagger controller with our custom one
        $this->app->bind(\L5Swagger\Http\Controllers\SwaggerController::class, \App\Http\Controllers\SwaggerController::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enregistrer les observers
        User::observe(UserObserver::class);
        Transaction::observe(TransactionObserver::class);

        // Générer la documentation Swagger en production si elle n'existe pas
        if ($this->app->environment('production')) {
            $docsDir = storage_path('api-docs');
            $docsPath = $docsDir . '/api-docs.json';

            // Créer le répertoire s'il n'existe pas
            if (!is_dir($docsDir)) {
                mkdir($docsDir, 0755, true);
            }

            if (!file_exists($docsPath)) {
                try {
                    Artisan::call('l5-swagger:generate');
                } catch (\Exception $e) {
                    Log::error('Failed to generate Swagger docs: ' . $e->getMessage());
                }
            }
        }
    }
}
