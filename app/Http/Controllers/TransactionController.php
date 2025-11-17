<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Repositories\TransactionRepository;
use App\Traits\ApiResponseTrait;
use App\Exceptions\UnauthorizedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="Gestion des transactions financières"
 * )
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="reference", type="string", example="TXN20241112001"),
 *     @OA\Property(property="type", type="string", enum={"transfert", "paiement", "depot", "retrait", "achat_credit"}, example="transfert"),
 *     @OA\Property(property="montant", type="number", format="float", example=5000.00),
 *     @OA\Property(property="frais", type="number", format="float", example=50.00),
 *     @OA\Property(property="statut", type="string", enum={"en_attente", "validee", "echouee", "annulee"}, example="validee"),
 *     @OA\Property(property="description", type="string", example="Transfert vers John Doe"),
 *     @OA\Property(property="date_execution", type="string", format="date-time"),
 *     @OA\Property(property="emetteur", ref="#/components/schemas/Compte"),
 *     @OA\Property(property="destinataire", ref="#/components/schemas/Compte"),
 *     @OA\Property(property="marchand", type="object")
 * )
 */

class TransactionController extends Controller
{
    use ApiResponseTrait;

    protected $transactionService;
    protected $transactionRepository;

    public function __construct(
        TransactionService $transactionService,
        TransactionRepository $transactionRepository
    ) {
        $this->transactionService = $transactionService;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @OA\Post(
     *     path="/transactions/transfer",
     *     summary="Initiate money transfer",
     *     description="Initiate a money transfer to another account",
     *     tags={"Transactions"},
     *     security={{"passport":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"destinataire_numero","montant"},
     *             @OA\Property(property="destinataire_numero", type="string", example="771234567"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000.00),
     *             @OA\Property(property="description", type="string", example="Transfert pour achat")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfer initiated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transfert initié. Veuillez saisir le code de vérification."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction_id", type="integer", example=123)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function transfer(TransferRequest $request): JsonResponse
    {
        // Vérification des autorisations avec Gate
        if (!Gate::allows('transfer-money')) {
            return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des transferts.', 403);
        }

        try {
            $transaction = $this->transactionService->initiateTransfer(
                $request->validated(),
                auth()->id()
            );

            return $this->successResponse(
                ['transaction_id' => $transaction->id],
                'Transfert initié. Veuillez saisir le code de vérification.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/transactions/payment",
     *     summary="Initiate merchant payment",
     *     description="Initiate a payment to a merchant",
     *     tags={"Transactions"},
     *     security={{"passport":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"marchand_id","montant"},
     *             @OA\Property(property="marchand_id", type="integer", example=1),
     *             @OA\Property(property="montant", type="number", format="float", example=2500.00),
     *             @OA\Property(property="description", type="string", example="Paiement pour services")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment initiated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement initié. Veuillez saisir le code de vérification."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction_id", type="integer", example=124)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function payment(PaymentRequest $request): JsonResponse
    {
        // Vérification des autorisations avec Gate
        if (!Gate::allows('pay-merchant')) {
            return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des paiements.', 403);
        }

        try {
            $transaction = $this->transactionService->initiatePayment(
                $request->validated(),
                auth()->id()
            );

            return $this->successResponse(
                ['transaction_id' => $transaction->id],
                'Paiement initié. Veuillez saisir le code de vérification.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/transactions/verify-code",
     *     summary="Verify and complete transaction",
     *     description="Verify transaction with code and complete the transaction",
     *     tags={"Transactions"},
     *     security={{"passport":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"transaction_id","code"},
     *             @OA\Property(property="transaction_id", type="integer", example=123),
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction validée avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function verifyCode(VerifyCodeRequest $request): JsonResponse
    {
        try {
            $transaction = $this->transactionService->verifyAndCompleteTransaction(
                $request->transaction_id,
                $request->code,
                auth()->id()
            );

            return $this->successResponse(
                new TransactionResource($transaction->load(['emetteur', 'destinataire', 'marchand'])),
                'Transaction validée avec succès'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/transactions/history",
     *     summary="Get transaction history",
     *     description="Get transaction history with US 2.0 compliance. Admin sees all transactions, client sees only their own.",
     *     tags={"Transactions"},
     *     security={{"passport":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Transaction type filter",
     *         required=false,
     *         @OA\Schema(type="string", enum={"transfert", "paiement", "depot", "retrait", "achat_credit"})
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Transaction status filter",
     *         required=false,
     *         @OA\Schema(type="string", enum={"en_attente", "validee", "echouee", "annulee"})
     *     ),
     *     @OA\Parameter(
     *         name="montant_min",
     *         in="query",
     *         description="Minimum amount filter",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="montant_max",
     *         in="query",
     *         description="Maximum amount filter",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="periode",
     *         in="query",
     *         description="Period filter",
     *         required=false,
     *         @OA\Schema(type="string", enum={"today", "week", "month", "year"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="General search",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort field",
     *         required=false,
     *         @OA\Schema(type="string", default="created_at")
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
     *         description="Transaction history retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Historique des transactions récupéré avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Transaction"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function history(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = Transaction::with(['emetteur.user', 'destinataire.user', 'marchand']);

        // Appliquer les filtres selon le rôle
        if ($user->hasRole('client')) {
            // Client ne voit que ses transactions
            $query->pourUtilisateur($user->id);
        } elseif ($user->hasRole('admin')) {
            // Admin peut voir toutes les transactions
            // Pas de filtre supplémentaire
        } elseif ($user->hasRole('marchand')) {
            // Marchand voit les paiements reçus
            $query->where('marchand_id', $user->marchand?->id);
        } else {
            throw new UnauthorizedException('Rôle non autorisé pour consulter l\'historique');
        }

        // Appliquer les filtres de requête
        $this->applyTransactionFilters($query, $request);

        // Appliquer le tri
        $this->applyTransactionSorting($query, $request);

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $transactions = $query->paginate($perPage);

        return $this->successResponse(
            TransactionResource::collection($transactions),
            'Historique des transactions récupéré avec succès'
        );
    }

    /**
     * @OA\Get(
     *     path="/transactions/{id}",
     *     summary="Get transaction details",
     *     description="Get detailed information about a specific transaction",
     *     tags={"Transactions"},
     *     security={{"passport":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Transaction ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction récupérée"),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Transaction not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $user = auth()->user();
        $transaction = $this->transactionRepository->find($id);

        if (!$transaction) {
            return $this->notFoundResponse('Transaction non trouvée');
        }

        // Vérification des autorisations via Policy
        $this->authorize('view', $transaction);

        return $this->successResponse(
            new \App\Http\Resources\TransactionResource($transaction->load(['emetteur.user', 'destinataire.user', 'marchand'])),
            'Transaction récupérée'
        );
    }

    /**
     * @OA\Hidden
     */
    public function search(): JsonResponse
    {
        $criteria = request()->only([
            'montant_min', 'montant_max', 'date_debut', 'date_fin',
            'type', 'status', 'per_page'
        ]);

        $transactions = $this->transactionRepository->search($criteria);

        return $this->paginatedResponse($transactions, 'Résultats de recherche');
    }

    /**
     * @OA\Hidden
     */
    public function statistics(): JsonResponse
    {
        $user = auth()->user();
        $filters = request()->only(['periode', 'type', 'status']);

        // Pour les clients, statistiques personnelles
        if ($user->hasRole('client')) {
            $filters['user_id'] = $user->id;
        }
        // Pour les admins, statistiques globales
        elseif ($user->hasRole('admin')) {
            // Pas de filtre user_id pour les admins
        }
        // Pour les marchands, statistiques de leurs transactions
        elseif ($user->hasRole('marchand')) {
            $filters['marchand_id'] = $user->marchand?->id;
        }

        $stats = $this->transactionRepository->getStatistics($filters);

        return $this->successResponse($stats, 'Statistiques récupérées');
    }

    /**
     * Appliquer les filtres de requête aux transactions
     */
    private function applyTransactionFilters($query, Request $request)
    {
        // Filtre par type
        if ($request->has('type') && in_array($request->type, ['transfert', 'paiement', 'depot', 'retrait', 'achat_credit'])) {
            $query->where('type', $request->type);
        }

        // Filtre par statut
        if ($request->has('status') && in_array($request->status, ['en_attente', 'validee', 'echouee', 'annulee'])) {
            $query->where('statut', $request->status);
        }

        // Filtre par montant
        if ($request->has('montant_min') && is_numeric($request->montant_min)) {
            $query->where('montant', '>=', $request->montant_min);
        }

        if ($request->has('montant_max') && is_numeric($request->montant_max)) {
            $query->where('montant', '<=', $request->montant_max);
        }

        // Filtre par période
        if ($request->has('periode') && in_array($request->periode, ['today', 'week', 'month', 'year'])) {
            $query->periode($request->periode);
        }

        // Filtre par destinataire
        if ($request->has('destinataire') && !empty($request->destinataire)) {
            $query->where('destinataire_numero', 'like', "%{$request->destinataire}%")
                  ->orWhere('destinataire_nom', 'like', "%{$request->destinataire}%");
        }

        // Recherche générale
        if ($request->has('search') && !empty($request->search)) {
            $query->rechercher($request->search);
        }
    }

    /**
     * Appliquer le tri aux transactions
     */
    private function applyTransactionSorting($query, Request $request)
    {
        $sortBy = $request->get('sort', 'created_at');
        $order = strtolower($request->get('order', 'desc'));

        // Valider l'ordre
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        $query->trierPar($sortBy, $order);
    }
}
