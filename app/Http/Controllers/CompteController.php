<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Services\CompteService;
use App\Traits\ApiResponseTrait;
use App\Http\Resources\CompteResource;
use App\Exceptions\CompteNotFoundException;
use App\Exceptions\UnauthorizedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Comptes",
 *     description="Gestion des comptes bancaires"
 * )
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="numero_compte", type="string", example="OM123456789"),
 *     @OA\Property(property="solde", type="number", format="float", example=1500.50),
 *     @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="actif"),
 *     @OA\Property(property="plafond_journalier", type="number", format="float", example=50000.00),
 *     @OA\Property(property="date_ouverture", type="string", format="date", example="2024-01-15"),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="client", type="object")
 * )
 */

class CompteController extends Controller
{
    use ApiResponseTrait;

    protected $compteService;

    public function __construct(CompteService $compteService)
    {
        $this->compteService = $compteService;
    }

    /**
     * @OA\Hidden
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = Compte::with(['user', 'client']);

        // Appliquer les filtres selon le rôle
        if ($user->hasRole('admin')) {
            // Admin peut voir tous les comptes
            // Pas de filtre supplémentaire
        } else {
            throw new UnauthorizedException('Seuls les administrateurs peuvent lister les comptes');
        }

        // Appliquer les scopes globaux
        $query->nonArchive();

        // Appliquer les filtres de requête
        $this->applyFilters($query, $request);

        // Appliquer le tri
        $this->applySorting($query, $request);

        // Pagination
        $perPage = min($request->get('limit', 10), 100);
        $comptes = $query->paginate($perPage);

        return $this->successResponse(
            CompteResource::collection($comptes),
            'Comptes récupérés avec succès'
        );
    }

    /**
     * @OA\Get(
     *     path="/comptes/{numcompte}/balance",
     *     summary="Get account balance by account number",
     *     description="Get the balance of a specific account by its account number",
     *     tags={"Comptes"},
     *     security={{"passport":{}}},
     *     @OA\Parameter(
     *         name="numcompte",
     *         in="path",
     *         description="Account number",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Balance retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Solde récupéré avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="solde", type="number", format="float", example=1500.50),
     *                 @OA\Property(property="numero_compte", type="string", example="OM123456789"),
     *                 @OA\Property(property="plafond_journalier", type="number", format="float", example=50000.00),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="devise", type="string", example="FCFA")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Access denied to this account"),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function balance(string $numcompte): JsonResponse
    {
        $user = auth()->user();

        // Trouver le compte par numéro
        $compte = Compte::where('numero_compte', $numcompte)->first();

        if (!$compte) {
            return $this->notFoundResponse('Compte non trouvé');
        }

        // Vérifier que l'utilisateur a accès à ce compte
        if ($compte->user_id !== $user->id) {
            return $this->errorResponse('Accès non autorisé à ce compte', 403);
        }

        return $this->successResponse([
            'solde' => (float) $compte->solde,
            'numero_compte' => $compte->numero_compte,
            'plafond_journalier' => (float) $compte->plafond_journalier,
            'statut' => $compte->statut,
            'devise' => 'FCFA'
        ], 'Solde récupéré avec succès');
    }


    /**
     * @OA\Hidden
     */
    public function qrCode(): JsonResponse
    {
        $compte = auth()->user()->compte;

        if (!$compte) {
            throw new CompteNotFoundException();
        }

        return $this->successResponse([
            'qr_code' => $this->compteService->generateQrCode($compte),
            'numero_compte' => $compte->numero_compte,
        ], 'QR Code généré avec succès');
    }

    /**
     * Appliquer les filtres de requête
     */
    private function applyFilters($query, Request $request)
    {
        // Filtre par statut
        if ($request->has('statut') && in_array($request->statut, ['actif', 'bloque', 'ferme'])) {
            $query->where('statut', $request->statut);
        }

        // Recherche par titulaire ou numéro
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_compte', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('nom', 'like', "%{$search}%")
                               ->orWhere('prenom', 'like', "%{$search}%")
                               ->orWhere('telephone', 'like', "%{$search}%");
                  });
            });
        }
    }

    /**
     * Appliquer le tri
     */
    private function applySorting($query, Request $request)
    {
        $sortBy = $request->get('sort', 'date_ouverture');
        $order = strtolower($request->get('order', 'desc'));

        // Valider les colonnes de tri autorisées
        $allowedSorts = ['date_ouverture', 'solde', 'numero_compte', 'statut'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'date_ouverture';
        }

        // Valider l'ordre
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        // Tri spécial pour titulaire (nom + prénom)
        if ($sortBy === 'titulaire') {
            $query->join('users', 'comptes.user_id', '=', 'users.id')
                  ->orderBy('users.nom', $order)
                  ->orderBy('users.prenom', $order)
                  ->select('comptes.*');
        } else {
            $query->orderBy($sortBy, $order);
        }
    }
}
