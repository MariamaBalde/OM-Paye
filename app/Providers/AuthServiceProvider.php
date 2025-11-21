<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use App\Models\Transaction;
use App\Policies\TransactionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Transaction::class => TransactionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Configuration Passport - tokens expirent en 1 heure par défaut
        Passport::personalAccessTokensExpireIn(now()->addHour());

        // Définir les gates basés sur les permissions
        Gate::define('transfer-money', function ($user) {
            return $user->hasPermission('transfer-money');
        });

        Gate::define('pay-merchant', function ($user) {
            return $user->hasPermission('pay-merchant');
        });

        Gate::define('deposit-money', function ($user) {
            return $user->hasPermission('deposit-money');
        });

        Gate::define('withdraw-money', function ($user) {
            return $user->hasPermission('withdraw-money');
        });

        Gate::define('view-own-history', function ($user) {
            return $user->hasPermission('view-own-history');
        });
    }
}
