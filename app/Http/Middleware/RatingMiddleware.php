<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Middleware Rating qui enregistre les utilisateurs qui ont atteint le Rating Limit
 * Implémente la surveillance des limites de taux pour les endpoints API
 */
class RatingMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $maxRequests = 100, int $decayMinutes = 1)
    {
        $user = $request->user();
        $ip = $request->ip();
        $route = $request->route() ? $request->route()->getName() : $request->path();

        // Clé pour le cache (par utilisateur ou IP)
        $key = $user ? "rating:user:{$user->id}" : "rating:ip:{$ip}";

        // Récupérer le compteur actuel
        $requests = cache()->get($key, 0);

        // Incrémenter le compteur
        $requests++;

        // Stocker dans le cache avec expiration
        cache()->put($key, $requests, now()->addMinutes($decayMinutes));

        // Vérifier si la limite est atteinte
        if ($requests > $maxRequests) {
            // Logger l'utilisateur qui a atteint la limite
            Log::warning('Rating limit exceeded', [
                'user_id' => $user ? $user->id : null,
                'ip' => $ip,
                'route' => $route,
                'requests' => $requests,
                'max_requests' => $maxRequests,
                'timestamp' => now()->toISOString()
            ]);

            // Retourner une réponse d'erreur 429 (Too Many Requests)
            return response()->json([
                'success' => false,
                'message' => 'Trop de requêtes. Veuillez réessayer plus tard.',
                'error' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $decayMinutes * 60 // en secondes
            ], 429, [
                'Retry-After' => $decayMinutes * 60,
                'X-RateLimit-Limit' => $maxRequests,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => now()->addMinutes($decayMinutes)->timestamp
            ]);
        }

        // Ajouter les headers de limite de taux à la réponse
        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $maxRequests);
        $response->headers->set('X-RateLimit-Remaining', max(0, $maxRequests - $requests));
        $response->headers->set('X-RateLimit-Reset', now()->addMinutes($decayMinutes)->timestamp);

        return $response;
    }
}