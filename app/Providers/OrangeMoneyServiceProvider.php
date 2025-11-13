<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Transaction;
use App\Observers\UserObserver;
use App\Observers\TransactionObserver;
use App\Services\TransactionService;
use App\Services\CompteService;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class OrangeMoneyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     * Dependency Inversion: Fournir les services via injection de dépendances
     */
    public function register(): void
    {
        // Enregistrer les services comme singletons
        $this->app->singleton(TransactionService::class, function ($app) {
            return new TransactionService();
        });

        $this->app->singleton(CompteService::class, function ($app) {
            return new CompteService();
        });

        // Enregistrement des repositories
        $this->app->bind(TransactionRepository::class, function ($app) {
            return new TransactionRepository(new \App\Models\Transaction());
        });
    }

    /**
     * Bootstrap services.
     * Single Responsibility: Enregistrer les observers et gates automatiquement
     */
    public function boot(): void
    {
        // Enregistrer les observers
        User::observe(UserObserver::class);
        Transaction::observe(TransactionObserver::class);

        // Enregistrer les Gates
        $this->registerGates();
    }

    /**
     * Enregistrer les Gates d'autorisation
     */
    private function registerGates(): void
    {
        Gate::define('transfer-money', function ($user) {
            return $user->hasRole('client') &&
                   $user->statut === 'actif' &&
                   $user->comptePrincipal &&
                   $user->comptePrincipal->statut === 'actif';
        });

        Gate::define('pay-merchant', function ($user) {
            return $user->hasRole('client') &&
                   $user->statut === 'actif' &&
                   $user->comptePrincipal &&
                   $user->comptePrincipal->statut === 'actif';
        });

        Gate::define('view-own-account', function ($user) {
            return $user->statut === 'actif';
        });

        Gate::define('manage-users', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('manage-merchants', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('receive-payments', function ($user) {
            return $user->hasRole('marchand');
        });
    }
}
