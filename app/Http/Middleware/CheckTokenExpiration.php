<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the current access token from the request
        // Support both "Bearer token" and just "token" formats
        $accessToken = $request->bearerToken();
        
        // If no bearer token found, try to get Authorization header directly
        if (!$accessToken) {
            $authHeader = $request->header('Authorization');
            if ($authHeader) {
                // Remove "Bearer " prefix if present
                $accessToken = str_ireplace('Bearer ', '', $authHeader);
            }
        }

        if ($accessToken) {
            $token = \Laravel\Passport\Token::where('id', $accessToken)->first();

            if ($token && $token->expires_at && $token->expires_at->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token has expired',
                    'error' => 'TOKEN_EXPIRED'
                ], 401);
            }
        }

        return $next($request);
    }
}
