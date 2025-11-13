<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Info(
 *     title="Orange Money API",
 *     version="1.0.0",
 *     description="API for Orange Money payment system with US 2.0 compliance"
 * )
 * @OA\Server(
 *     url="/api",
 *     description="API server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="apiKey",
 *     name="Authorization",
 *     in="header",
 *     description="Enter token in format: Bearer {token}"
 * )
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="Doe"),
 *     @OA\Property(property="prenom", type="string", example="John"),
 *     @OA\Property(property="nomComplet", type="string", example="Doe John"),
 *     @OA\Property(property="telephone", type="string", example="771234567"),
 *     @OA\Property(property="email", type="string", example="john.doe@example.com"),
 *     @OA\Property(property="statut", type="string", example="actif"),
 *     @OA\Property(property="langue", type="string", example="fr"),
 *     @OA\Property(property="themeSombre", type="boolean", example=false),
 *     @OA\Property(property="scannerActif", type="boolean", example=true),
 *     @OA\Property(property="soldeTotal", type="number", format="float", example=1500.50),
 *     @OA\Property(property="metadata", type="object",
 *         @OA\Property(property="derniereModification", type="string", format="date-time"),
 *         @OA\Property(property="dateCreation", type="string", format="date-time"),
 *         @OA\Property(property="version", type="integer", example=1)
 *     )
 * )
 * @OA\Schema(
 *     schema="OAuthToken",
 *     type="object",
 *     @OA\Property(property="token_type", type="string", example="Bearer"),
 *     @OA\Property(property="expires_in", type="integer", example=31536000),
 *     @OA\Property(property="access_token", type="string"),
 *     @OA\Property(property="refresh_token", type="string")
 * )
 */

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="User login",
     *     description="Authenticate user with phone number and secret code",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","code_secret"},
     *             @OA\Property(property="telephone", type="string", example="771234567"),
     *             @OA\Property(property="code_secret", type="string", example="1234")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=403, description="Account inactive")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('telephone', $request->telephone)->first();

        if (!$user) {
            return $this->errorResponse('Numéro de téléphone ou code secret incorrect', 401);
        }

        $compte = $user->comptePrincipal;

        if (!$compte || !Hash::check($request->code_secret, $compte->code_secret)) {
            return $this->errorResponse('Numéro de téléphone ou code secret incorrect', 401);
        }

        if ($user->statut !== 'actif') {
            return $this->errorResponse('Votre compte est ' . $user->statut, 403);
        }

        // Générer token Passport
        $token = $user->createToken('OrangeMoney')->accessToken;

        return $this->successResponse(
            [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            'Connexion réussie'
        );
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/logout",
     *     summary="User logout",
     *     description="Logout the authenticated user",
     *     tags={"Authentication"},
     *     security={{"passport":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function logout(): JsonResponse
    {
        // Pour Passport, on révoque le token actuel
        $accessToken = auth()->user()->token();
        $accessToken->revoke();

        return $this->successResponse('Déconnexion réussie');
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/refresh",
     *     summary="Refresh access token",
     *     description="Refresh the access token for authenticated user",
     *     tags={"Authentication"},
     *     security={{"passport":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token rafraîchi avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function refresh(): JsonResponse
    {
        $user = auth()->user();

        // Révoquer l'ancien token
        $user->token()->revoke();

        // Créer un nouveau token
        $newToken = $user->createToken('OrangeMoney')->accessToken;

        return $this->successResponse(
            [
                'token' => $newToken,
                'token_type' => 'Bearer',
            ],
            'Token rafraîchi avec succès'
        );
    }

    /**
     * @OA\Get(
     *     path="/v1/auth/profile",
     *     summary="Get user profile",
     *     description="Get the authenticated user's profile information",
     *     tags={"Authentication"},
     *     security={{"passport":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profil récupéré avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function profile(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Utilisateur non authentifié', 401);
        }

        return $this->successResponse(
            new UserResource($user),
            'Profil récupéré avec succès'
        );
    }

    /**
     * @OA\Post(
     *     path="/oauth/token",
     *     summary="Get OAuth access token",
     *     description="Get OAuth2 access token using password grant",
     *     tags={"OAuth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"grant_type","client_id","client_secret","username","password"},
     *             @OA\Property(property="grant_type", type="string", example="password"),
     *             @OA\Property(property="client_id", type="string", example="1"),
     *             @OA\Property(property="client_secret", type="string", example="client_secret_here"),
     *             @OA\Property(property="username", type="string", example="771234567"),
     *             @OA\Property(property="password", type="string", example="1234"),
     *             @OA\Property(property="scope", type="string", example="*")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token generated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/OAuthToken")
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function oauthToken()
    {
        // Cette méthode n'est que pour la documentation Swagger
        // Les vraies routes OAuth sont gérées par Passport
        return response()->json(['message' => 'Use Laravel Passport routes']);
    }

    /**
     * @OA\Post(
     *     path="/oauth/refresh",
     *     summary="Refresh OAuth access token",
     *     description="Refresh OAuth2 access token using refresh token",
     *     tags={"OAuth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"grant_type","client_id","client_secret","refresh_token"},
     *             @OA\Property(property="grant_type", type="string", example="refresh_token"),
     *             @OA\Property(property="client_id", type="string", example="1"),
     *             @OA\Property(property="client_secret", type="string", example="client_secret_here"),
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(ref="#/components/schemas/OAuthToken")
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=401, description="Invalid refresh token")
     * )
     */

    /**
     * @OA\Post(
     *     path="/oauth/refresh",
     *     summary="Refresh OAuth access token",
     *     description="Refresh OAuth2 access token using refresh token",
     *     tags={"OAuth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"grant_type","client_id","client_secret","refresh_token"},
     *             @OA\Property(property="grant_type", type="string", example="refresh_token"),
     *             @OA\Property(property="client_id", type="string", example="1"),
     *             @OA\Property(property="client_secret", type="string", example="client_secret_here"),
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(ref="#/components/schemas/OAuthToken")
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=401, description="Invalid refresh token")
     * )
     */
    public function oauthRefresh()
    {
        // Cette méthode n'est que pour la documentation Swagger
        return response()->json(['message' => 'Use Laravel Passport routes']);
    }
}
