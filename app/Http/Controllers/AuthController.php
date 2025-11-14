<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\SendSmsRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\SmsService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(
 *     title="Orange Money API",
 *     version="1.0.0",
 *     description="API for Orange Money payment system"
 * )
 * @OA\Server(
 *     url="/api",
 *     description="API server"
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
 *     @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
 *     @OA\Property(property="user", ref="#/components/schemas/User")
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
 */

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Étape 1: Initiation de connexion OM Pay",
     *     description="Saisir numéro de téléphone → SMS envoyé automatiquement → Retourne session_id pour tracker la session",
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
     *         description="SMS envoyé avec succès - Prêt pour saisir le code secret",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="SMS envoyé"),
     *             @OA\Property(property="data", ref="#/components/schemas/SmsSession")
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

        // Générer un code SMS (4 chiffres)
        $smsCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // Créer le code de vérification SMS avec session_id
        $verificationCode = VerificationCode::create([
            'user_id' => $user->id,
            'code' => $smsCode,
            'type' => 'sms',
            'expire_at' => now()->addMinutes(10), // 10 minutes pour compléter le processus
            'verifie' => false,
        ]);

        // Envoyer le SMS via le service configuré
        $smsService = new SmsService();
        $message = "Orange Money: Votre code de vérification est {$smsCode}. Valide 10 minutes.";

        $smsResult = $smsService->sendSms($request->telephone, $message);

        if (!$smsResult['success']) {
            Log::error('SMS sending failed', [
                'telephone' => $request->telephone,
                'error' => $smsResult['error']
            ]);

            // Supprimer le code de vérification si l'envoi SMS échoue
            $verificationCode->delete();

            return $this->errorResponse(
                'Erreur lors de l\'envoi du SMS. Veuillez réessayer.',
                500
            );
        }

        Log::info("SMS envoyé avec succès", [
            'telephone' => $request->telephone,
            'message_id' => $smsResult['message_id'],
            'session_id' => $verificationCode->id
        ]);

        return $this->successResponse(
            [
                'session_id' => (string) $verificationCode->id,
                'sms_sent' => true,
            ],
            'SMS envoyé avec succès'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/auth/verify-code-secret",
     *     summary="Étape 3: Vérification du code secret - Connexion finale",
     *     description="Après vérification SMS côté backend → Saisir code secret (4 chiffres) → Connexion complète",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","code_secret","session_id"},
     *             @OA\Property(property="telephone", type="string", example="782917770", description="Numéro de téléphone utilisé à l'étape 1"),
     *             @OA\Property(property="code_secret", type="string", example="1234", description="Code secret Orange Money (4 chiffres)"),
     *             @OA\Property(property="session_id", type="string", example="123", description="Session ID retourné à l'étape 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie - Accès au dashboard OM Pay",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", ref="#/components/schemas/AuthToken")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Session expirée ou invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Session expirée ou invalide")
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

        // Vérifier la session SMS
        $verificationCode = VerificationCode::where('id', $request->session_id)
            ->where('user_id', $user->id)
            ->where('type', 'sms')
            ->where('expire_at', '>', now())
            ->first();

        if (!$verificationCode) {
            return $this->errorResponse('Session expirée ou invalide', 400);
        }

        $compte = $user->comptePrincipal;

        if (!$compte || !Hash::check($request->code_secret, $compte->code_secret)) {
            return $this->errorResponse('Numéro de téléphone ou code secret incorrect', 401);
        }

        if ($user->statut !== 'actif') {
            return $this->errorResponse('Votre compte est ' . $user->statut, 403);
        }

        // Marquer le code de vérification comme utilisé
        $verificationCode->update(['verifie' => true]);

        // Générer token Passport
        $token = $user->createToken('OrangeMoney')->accessToken;

        return $this->successResponse(
            [
                'token' => $token,
                'user' => new UserResource($user),
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

}
