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
     * @OA\Get(
     *     path="/v1/comptes",
     *     summary="List accounts",
     *     description="List all accounts with US 2.0 compliance. Admin sees all accounts, client sees only their own.",
     *     tags={"Comptes"},
     *     security={{"passport":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Account status filter",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "bloque", "ferme"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by account number or holder name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort field",
     *         required=false,
     *         @OA\Schema(type="string", enum={"date_ouverture", "solde", "numero_compte", "statut"})
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Sort order",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Accounts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comptes récupérés avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Compte"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
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
     *     path="/v1/comptes/balance",
     *     summary="Get account balance",
     *     description="Get the balance of the user's primary account",
     *     tags={"Comptes"},
     *     security={{"passport":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Balance retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Solde récupéré avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="solde", type="number", format="float", example=1500.50),
     *                 @OA\Property(property="solde_formate", type="string", example="1 500,50 FCFA"),
     *                 @OA\Property(property="numero_compte", type="string", example="OM123456789"),
     *                 @OA\Property(property="plafond_journalier", type="number", format="float", example=50000.00),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="devise", type="string", example="FCFA")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function balance(): JsonResponse
    {
        $compte = auth()->user()->compte;

        if (!$compte) {
            throw new CompteNotFoundException();
        }

        return $this->successResponse([
            'solde' => (float) $compte->solde,
            'solde_formate' => $this->compteService->getFormattedBalance($compte),
            'numero_compte' => $compte->numero_compte,
            'plafond_journalier' => (float) $compte->plafond_journalier,
            'statut' => $compte->statut,
            'devise' => 'FCFA'
        ], 'Solde récupéré avec succès');
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes/{compte}",
     *     summary="Get account details",
     *     description="Get detailed information about a specific account",
     *     tags={"Comptes"},
     *     security={{"passport":{}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         description="Account ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Détails du compte récupérés avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function show(Compte $compte): JsonResponse
    {
        $user = auth()->user();

        // Vérifier les autorisations
        if (!$user->hasRole('admin') && $compte->user_id !== $user->id) {
            throw new UnauthorizedException();
        }

        return $this->successResponse(
            new CompteResource($compte->load(['user', 'client'])),
            'Détails du compte récupérés avec succès'
        );
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes/qr-code",
     *     summary="Generate QR code",
     *     description="Generate QR code for the user's primary account",
     *     tags={"Comptes"},
     *     security={{"passport":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="QR code generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="QR Code généré avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="qr_code", type="string", description="Base64 encoded QR code image"),
     *                 @OA\Property(property="numero_compte", type="string", example="OM123456789")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Account not found")
     * )
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
