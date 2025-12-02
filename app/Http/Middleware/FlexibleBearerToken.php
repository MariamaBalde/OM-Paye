<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom middleware to accept tokens with or without "Bearer" prefix
 * This allows clients to send tokens in multiple formats:
 * - Authorization: Bearer <token>
 * - Authorization: <token>
 */
class FlexibleBearerToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get Authorization header
        $authHeader = $request->header('Authorization');
        
        if ($authHeader) {
            // If it doesn't start with "Bearer ", add it
            if (!str_starts_with(strtolower($authHeader), 'bearer ')) {
                $request->headers->set('Authorization', 'Bearer ' . $authHeader);
            }
        }

        return $next($request);
    }
}
