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
        //
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
            $docsPath = storage_path('api-docs/api-docs.json');
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
