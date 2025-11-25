<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SendSmsRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\TransactionResource;
use App\Models\User;
use App\Models\VerificationCode;
use App\Models\Transaction;
use App\Services\SmsService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Token;
use Laravel\Passport\RefreshToken;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * @OA\Info(
 *     title="Orange Money API",
 *     version="1.0.0",
 *     description="API for Orange Money payment system - Version 1"
 * )
 * @OA\Server(
 *     url="https://om-paye.onrender.com/api/v1",
 *     description="Production API V1 server"
 * )
 * @OA\Server(
 *     url="/api/v1",
 *     description="Local API V1 server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="passport",
 *     type="apiKey",
 *     name="Authorization",
 *     in="header",
 *     description="Enter token in format: Bearer {token}"
 * )
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="Diallo"),
 *     @OA\Property(property="prenom", type="string", example="Abdoulaye"),
 *     @OA\Property(property="nomComplet", type="string", example="Diallo Abdoulaye"),
 *     @OA\Property(property="telephone", type="string", example="782917770"),
 *     @OA\Property(property="email", type="string", example="abdoulaye.diallo@example.com"),
 *     @OA\Property(property="statut", type="string", example="actif"),
 *     @OA\Property(property="langue", type="string", example="fr"),
 *     @OA\Property(property="themeSombre", type="boolean", example=false),
 *     @OA\Property(property="scannerActif", type="boolean", example=true),
 *     @OA\Property(property="soldeTotal", type="number", format="float", example=0),
 *     @OA\Property(property="metadata", type="object",
 *         @OA\Property(property="derniereModification", type="string", format="date-time"),
 *         @OA\Property(property="dateCreation", type="string", format="date-time"),
 *         @OA\Property(property="version", type="integer", example=1)
 *     )
 * )
 * @OA\Schema(
 *     schema="AuthToken",
 *     type="object",
 *     @OA\Property(property="token_type", type="string", example="Bearer"),
 *     @OA\Property(property="expires_in", type="integer", example=1800, description="30 minutes en secondes"),
 *     @OA\Property(property="access_token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
 *     @OA\Property(property="refresh_token", type="string", example="refresh_token_string", description="Expire dans 1 heure")
 * )
 * @OA\Schema(
 *     schema="SmsSession",
 *     type="object",
 *     @OA\Property(property="session_id", type="string", example="123"),
 *     @OA\Property(property="sms_sent", type="boolean", example=true)
 * )
 * @OA\Schema(
 *     schema="OAuthToken",
 *     type="object",
 *     @OA\Property(property="token_type", type="string", example="Bearer"),
 *     @OA\Property(property="expires_in", type="integer", example=31536000),
 *     @OA\Property(property="access_token", type="string"),
 *     @OA\Property(property="refresh_token", type="string")
 * )
 * @OA\Schema(
 *     schema="Dashboard",
 *     type="object",
 *     @OA\Property(property="user", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="nom", type="string", example="Diallo")
 *     ),
 *     @OA\Property(property="compte", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="numero", type="string", example="OMCPT1234567890"),
 *         @OA\Property(property="solde", type="number", format="float", example=1500.50),
 *         @OA\Property(property="qrCode", type="string", example="QR_774047668")
 *     ),
 *     @OA\Property(property="recentTransactions", type="array",
 *         @OA\Items(ref="#/components/schemas/Transaction")
 *     )
 * )
 */

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Post(
     *     path="/auth/register",
     *     summary="Inscription utilisateur Orange Money",
     *     description="Créer un nouveau compte utilisateur avec numéro de téléphone et code secret. Un SMS de confirmation est envoyé.",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom","prenom","telephone","code_secret"},
     *             @OA\Property(property="nom", type="string", example="Diallo"),
     *             @OA\Property(property="prenom", type="string", example="Abdoulaye"),
     *             @OA\Property(property="telephone", type="string", example="774047668", description="Numéro de téléphone 9 chiffres"),
     *             @OA\Property(property="code_secret", type="string", example="1234", description="Code secret 4 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès. Un SMS de confirmation a été envoyé."),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies ne sont pas valides."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::withoutEvents(function () use ($request) {
            return User::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'telephone' => $request->telephone,
                'password' => bcrypt('default_password'), // Mot de passe par défaut, peut être changé plus tard
                'statut' => 'actif',
                'langue' => 'français',
                'theme_sombre' => false,
                'scanner_actif' => true,
            ]);
        });

        // Assigner le rôle client
        $clientRole = \App\Models\Role::where('name', 'client')->first();
        if ($clientRole) {
            $user->assignRole($clientRole);
        }

        // Créer le compte avec le code secret personnalisé
        $compte = \App\Models\Compte::create([
            'user_id' => $user->id,
            'numero_compte' => 'OMCPT' . time() . $user->id,
            'solde' => 0.00,
            'qr_code' => 'QR_' . $user->telephone,
            'code_secret' => bcrypt($request->code_secret),
            'plafond_journalier' => 500000.00,
            'statut' => 'actif',
            'date_ouverture' => now(),
        ]);

        // Créer le profil client
        \App\Models\Client::create([
            'compte_id' => $compte->id,
        ]);

        // Envoyer un SMS de confirmation d'inscription
        $smsService = new SmsService();
        $message = "Orange Money: Bienvenue {$user->prenom}! Votre compte a été créé avec succès.";
        $smsService->sendSms($request->telephone, $message);

        return $this->successResponse(
            null,
            'Compte créé avec succès. Un SMS de confirmation a été envoyé.',
            201
        );
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Initiation de connexion OM Pay",
     *     description="Saisir numéro de téléphone → Vérifie que l'utilisateur existe → Prêt pour vérification du code secret",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone"},
     *             @OA\Property(property="telephone", type="string", example="782917770", description="Numéro de téléphone sans indicatif (+221)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur trouvé - Prêt pour vérification du code secret",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Utilisateur trouvé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Numéro de téléphone invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le numéro de téléphone doit contenir exactement 9 chiffres.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce numéro de téléphone n'est pas enregistré dans Orange Money.")
     *         )
     *     )
     * )
     */
    public function login(SendSmsRequest $request): JsonResponse
    {
        $user = User::where('telephone', $request->telephone)->first();

        if (!$user) {
            return $this->errorResponse('Ce numéro de téléphone n\'est pas enregistré dans Orange Money.', 404);
        }

        return $this->successResponse(
            null,
            'Utilisateur trouvé. Prêt pour vérification du code secret.'
        );
    }

    /**
     * @OA\Post(
     *     path="/auth/verify-code-secret",
     *     summary="Vérification du code secret - Connexion finale",
     *     description="Saisir numéro de téléphone et code secret (4 chiffres) → Connexion complète",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","code_secret"},
     *             @OA\Property(property="telephone", type="string", example="782917770", description="Numéro de téléphone"),
     *             @OA\Property(property="code_secret", type="string", example="1234", description="Code secret Orange Money (4 chiffres)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie - Accès au dashboard OM Pay",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=1800, description="30 minutes en secondes"),
     *                 @OA\Property(property="access_token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
     *                 @OA\Property(property="refresh_token", type="string", example="refresh_token_string", description="Expire dans 1 heure")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Code secret incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Numéro de téléphone ou code secret incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Compte inactif",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Votre compte est suspendu")
     *         )
     *     )
     * )
     */
    public function verifyCodeSecret(LoginRequest $request): JsonResponse
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

        // Créer un token d'accès personnel (qui fonctionne comme OAuth2 token)
        $tokenResult = $user->createToken('OrangeMoney');

        // Créer un refresh token associé (1 heure)
        $refreshTokenId = Str::random(40);
        $refreshToken = new RefreshToken([
            'id' => $refreshTokenId,
            'access_token_id' => $tokenResult->token->id,
            'revoked' => false,
            'expires_at' => Carbon::now()->addHour(),
        ]);
        $refreshToken->save();

        return $this->successResponse(
            [
                'token_type' => 'Bearer',
                'expires_in' => 1800, // 30 minutes en secondes
                'access_token' => $tokenResult->accessToken,
                'refresh_token' => $refreshTokenId,
            ],
            'Connexion réussie'
        );
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
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
        // Révoquer le token actuel de l'utilisateur authentifié
        $user = auth()->user();
        if ($user && $user->token()) {
            $user->token()->revoke();

            // Révoquer également les refresh tokens associés
            RefreshToken::where('access_token_id', $user->token()->id)
                ->update(['revoked' => true]);
        }

        return $this->successResponse(null, 'Déconnexion réussie');
    }

    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     summary="Refresh access token",
     *     description="Use refresh token to get a new access token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="refresh_token_string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token rafraîchi avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=1800, description="30 minutes en secondes"),
     *                 @OA\Property(property="access_token", type="string", example="nouveau_token..."),
     *                 @OA\Property(property="refresh_token", type="string", example="nouveau_refresh_token", description="Expire dans 1 heure")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Refresh token required"),
     *     @OA\Response(response=401, description="Invalid refresh token")
     * )
     */
    public function refresh(): JsonResponse
    {
        $request = request();

        if (!$request->has('refresh_token')) {
            return $this->errorResponse('Refresh token requis', 400);
        }

        // Trouver le refresh token
        $refreshToken = RefreshToken::where('id', $request->refresh_token)
            ->where('revoked', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$refreshToken) {
            return $this->errorResponse('Refresh token invalide ou expiré', 401);
        }

        // Récupérer l'ancien access token et le révoquer
        $oldAccessToken = Token::find($refreshToken->access_token_id);
        if ($oldAccessToken) {
            $oldAccessToken->update(['revoked' => true]);
            $user = $oldAccessToken->user;
        } else {
            return $this->errorResponse('Token associé introuvable', 401);
        }

        // Révoquer l'ancien refresh token
        $refreshToken->update(['revoked' => true]);

        // Créer un nouveau token d'accès personnel
        $newTokenResult = $user->createToken('OrangeMoney');

        // Créer un nouveau refresh token (1 heure)
        $newRefreshTokenId = Str::random(40);
        $newRefreshToken = new RefreshToken([
            'id' => $newRefreshTokenId,
            'access_token_id' => $newTokenResult->token->id,
            'revoked' => false,
            'expires_at' => Carbon::now()->addHour(),
        ]);
        $newRefreshToken->save();

        return $this->successResponse(
            [
                'token_type' => 'Bearer',
                'expires_in' => 1800, // 30 minutes
                'access_token' => $newTokenResult->accessToken,
                'refresh_token' => $newRefreshTokenId,
            ],
            'Token rafraîchi avec succès'
        );
    }

    /**
     * @OA\Get(
     *     path="/auth/profile",
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
     * @OA\Get(
     *     path="/client/dashboard",
     *     summary="Get client dashboard data",
     *     description="Get dashboard data including user info, account details, and recent transactions",
     *     tags={"Dashboard"},
     *     security={{"passport":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Données du dashboard récupérées avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nom", type="string", example="Diallo")
     *                 ),
     *                 @OA\Property(property="compte", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="numero", type="string", example="OMCPT1234567890"),
     *                     @OA\Property(property="solde", type="number", format="float", example=1500.50),
     *                     @OA\Property(property="qrCode", type="string", example="QR_774047668")
     *                 ),
     *                 @OA\Property(property="recentTransactions", type="array",
     *                     @OA\Items(ref="#/components/schemas/Transaction")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function dashboard(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Utilisateur non authentifié', 401);
        }

        $compte = $user->compte;

        if (!$compte) {
            return $this->errorResponse('Compte non trouvé', 404);
        }

        // Récupérer les 10 dernières transactions validées
        $recentTransactions = Transaction::pourUtilisateur($user->id)
            ->validee()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $dashboardData = [
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
            ],
            'compte' => [
                'id' => $compte->id,
                'numero' => $compte->numero_compte,
                'solde' => (float) $compte->solde,
                'qrCode' => $compte->qr_code,
            ],
            'recentTransactions' => TransactionResource::collection($recentTransactions),
        ];

        return $this->successResponse(
            $dashboardData,
            'Données du dashboard récupérées avec succès'
        );
    }

}
