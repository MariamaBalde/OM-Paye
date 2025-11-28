<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\Compte;
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
     *     path="/api/v1/transactions/transfert",
     *     description="Processus transfert d'argent a un autre compte",
     *     tags={"Transactions"},
     *     security={{"passport":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"destinataire_numero","montant"},
     *             @OA\Property(property="destinataire_numero", type="string", example="771234567"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfer processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function transfer(TransferRequest $request): JsonResponse
    {
        try {
            $transaction = $this->transactionService->processTransfer(
                $request->validated(),  // ← Contient 'description' du client
                auth()->id()
            );

            return $this->successResponse(
                new TransactionResource($transaction->load(['emetteur.user', 'destinataire.user'])),
                'Transfert effectué avec succès'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/transactions/paiement",
     *     description="Processus paiement pour un marchand",
     *     tags={"Transactions"},
     *     security={{"passport":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code_marchand","montant"},
     *             @OA\Property(property="code_marchand", type="string", example="MARCHAND001"),
     *             @OA\Property(property="montant", type="number", format="float", example=2500.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement effectué avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction")
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
        // if (!Gate::allows('pay-merchant')) {
        //     return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des paiements.', 403);
        // }

        try {
            $transaction = $this->transactionService->processPayment(
                $request->validated(),
                auth()->id()
            );

            return $this->successResponse(
                new TransactionResource($transaction->load(['emetteur.user', 'marchand'])),
                'Paiement effectué avec succès'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/transactions/depot",
     *     description="Processus de dépôt à l'account",
     *     tags={"Transactions"},
     *     security={{"passport":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant"},
     *             @OA\Property(property="montant", type="number", format="float", example=50000.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Deposit processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dépôt effectué avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function deposit(Request $request): JsonResponse
    {
        $request->validate([
            'montant' => 'required|numeric|min:100|max:1000000',
        ]);

        // Vérification des autorisations
        // if (!Gate::allows('deposit-money')) {
        //     return $this->errorResponse('Vous n\'avez pas l\'autorisation d\'effectuer des dépôts.', 403);
        // }

        try {
            $transaction = $this->transactionService->processDeposit(
                $request->all(),
                auth()->id()
            );

            return $this->successResponse(
                new TransactionResource($transaction->load(['emetteur.user'])),
                'Dépôt effectué avec succès'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/transactions/retrait",
     *     description="Processus de retrait du compte",
     *     tags={"Transactions"},
     *     security={{"passport":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant"},
     *             @OA\Property(property="montant", type="number", format="float", example=20000.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Withdrawal processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Retrait effectué avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function withdrawal(Request $request): JsonResponse
    {
        $request->validate([
            'montant' => 'required|numeric|min:100|max:1000000',
        ]);

        try {
            $transaction = $this->transactionService->processWithdrawal(
                $request->all(),
                auth()->id()
            );

            return $this->successResponse(
                new TransactionResource($transaction->load(['emetteur.user'])),
                'Retrait effectué avec succès'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }




  

    /**
     * @OA\Get(
     *     path="/api/v1/transactions/{numero_compte}/history",
     *     description="Obtenir l'historique des transactions d'un compte spécifique à l'aide de filtres",
     *     tags={"Transactions"},
     *     security={{"passport":{}}},
     *     @OA\Parameter(
     *         name="numero_compte",
     *         in="path",
     *         description="Account number",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
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
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transactions", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", enum={"transfert", "paiement", "depot", "retrait", "achat_credit"}, example="transfert"),
     *                     @OA\Property(property="type_label", type="string", example="Transfert d'argent"),
     *                     @OA\Property(property="montant", type="number", format="float", example=-5000),
     *                     @OA\Property(property="devise", type="string", example="CFA"),
     *                     @OA\Property(property="montant_formate", type="string", example="- 5 000 CFA"),
     *                     @OA\Property(property="destinataire", type="object",
     *                         @OA\Property(property="nom", type="string", nullable=true, example="Alpha Gounass"),
     *                         @OA\Property(property="numero", type="string", nullable=true, example="771234567")
     *                     ),
     *                     @OA\Property(property="statut", type="string", enum={"en_attente", "validee", "echouee", "annulee"}, example="validee"),
     *                     @OA\Property(property="date", type="string", format="date-time", example="2024-11-02T09:21:00Z"),
     *                     @OA\Property(property="date_formate", type="string", example="02/11 09:21"),
     *                     @OA\Property(property="reference", type="string", example="OM-TXN123456")
     *                 )),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="total", type="integer", example=45),
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=20),
     *                     @OA\Property(property="last_page", type="integer", example=3),
     *                     @OA\Property(property="has_more", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Access denied to this account"),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function index(string $numero_compte, Request $request): JsonResponse
    {
        $user = auth()->user();

        // Trouver le compte par numéro
        $compte = Compte::where('numero_compte', $numero_compte)->first();

        if (!$compte) {
            return $this->notFoundResponse('Compte non trouvé');
        }

        // Vérifier que l'utilisateur a accès à ce compte
        if ($compte->user_id !== $user->id) {
            return $this->errorResponse('Accès non autorisé à ce compte', 403);
        }

        $query = Transaction::query();

        // Filtrer uniquement les transactions de ce compte (émises ou reçues)
        $query->where(function ($q) use ($compte) {
            $q->where('compte_emetteur_id', $compte->id)
              ->orWhere('compte_destinataire_id', $compte->id);
        });

        // Appliquer les filtres de requête
        $this->applyTransactionFilters($query, $request);

        // Appliquer le tri
        $this->applyTransactionSorting($query, $request);

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $transactions = $query->paginate($perPage);

        // Formater les données selon le prototype
        $transactionData = collect($transactions->items())->map(function ($transaction) use ($compte) {
            // Calculer le montant selon le type de transaction et la perspective du compte
            $montant = $this->calculateDisplayAmount($transaction, $compte);

            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'type_label' => $this->getTypeLabel($transaction->type),
                'montant' => (float) $montant,
                'devise' => 'CFA',
                'montant_formate' => ($montant < 0 ? '-' : '') . ' ' . number_format(abs($montant), 0, ',', ' ') . ' CFA',
                'destinataire' => [
                    'nom' => $transaction->destinataire_nom,
                    'numero' => $transaction->destinataire_numero
                ],
                'statut' => $transaction->statut,
                'date' => $transaction->date_transaction?->toISOString(),
                'date_formate' => $transaction->date_transaction?->format('d/m H:i'),
                'reference' => $transaction->reference
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Historique des transactions récupéré avec succès',
            'data' => [
                'transactions' => $transactionData,
                'pagination' => [
                    'total' => $transactions->total(),
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                    'last_page' => $transactions->lastPage(),
                    'has_more' => $transactions->hasMorePages()
                ]
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/transactions/{id}",
     *     summary="Afficher les détails d'une transaction",
     *     description="Récupérer les détails complets d'une transaction spécifique au format prototype",
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
     *             @OA\Property(property="message", type="string", example="Détails de la transaction récupérés avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="reference", type="string", example="PP251119.1400.C27819"),
     *                 @OA\Property(property="destinataire", type="string", example="Sarr Fatou"),
     *                 @OA\Property(property="expediteur", type="string", example="774047668"),
     *                 @OA\Property(property="montant", type="string", example="300 CFA"),
     *                 @OA\Property(property="date", type="string", example="19/11/2025 14:00"),
     *                 @OA\Property(property="message_recu", type="string", example="Transaction effectuée"),
     *                 @OA\Property(property="actions", type="object",
     *                     @OA\Property(property="partager", type="boolean", example=true),
     *                     @OA\Property(property="appeler", type="boolean", example=true),
     *                     @OA\Property(property="annuler", type="boolean", example=false)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Access denied to this transaction"),
     *     @OA\Response(response=404, description="Transaction not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $user = auth()->user();

        // Trouver la transaction
        $transaction = Transaction::with(['emetteur.user', 'destinataire.user', 'marchand'])->find($id);

        if (!$transaction) {
            return $this->notFoundResponse('Transaction non trouvée');
        }

        // Vérifier que l'utilisateur a accès à cette transaction
        // L'utilisateur doit être soit l'émetteur, soit le destinataire, soit un admin
        $hasAccess = false;

        if ($user->hasRole('admin')) {
            $hasAccess = true;
        } elseif ($transaction->emetteur && $transaction->emetteur->user_id === $user->id) {
            $hasAccess = true;
        } elseif ($transaction->destinataire && $transaction->destinataire->user_id === $user->id) {
            $hasAccess = true;
        }

        if (!$hasAccess) {
            return $this->errorResponse('Accès non autorisé à cette transaction', 403);
        }

        // Formater les données selon le prototype exact
        $data = [
            'reference' => $this->generatePrototypeReference($transaction),
            'destinataire' => $this->formatDestinataireWithEmoji($transaction),
            'expediteur' => $this->formatExpediteur($transaction),
            'montant' => $this->formatMontant($transaction),
            'date' => $this->formatDate($transaction),
            'actions' => [
                'partager' => true,
                'appeler' => true,
                'annuler' => $this->canCancelTransaction($transaction)
            ]
        ];

        return $this->successResponse($data, 'Détails de la transaction récupérés avec succès');
    }

    /**
     * Appliquer les filtres de requête
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

        // Filtre par montant minimum
        if ($request->has('montant_min') && is_numeric($request->montant_min)) {
            $query->where('montant', '>=', $request->montant_min);
        }

        // Filtre par montant maximum
        if ($request->has('montant_max') && is_numeric($request->montant_max)) {
            $query->where('montant', '<=', $request->montant_max);
        }

        // Filtre par période
        if ($request->has('periode')) {
            switch ($request->periode) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    break;
                case 'year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }
        }

        // Recherche générale
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('emetteur.user', function ($userQuery) use ($search) {
                      $userQuery->where('nom', 'like', "%{$search}%")
                                ->orWhere('prenom', 'like', "%{$search}%");
                  })
                  ->orWhereHas('destinataire.user', function ($userQuery) use ($search) {
                      $userQuery->where('nom', 'like', "%{$search}%")
                                ->orWhere('prenom', 'like', "%{$search}%");
                  });
            });
        }
    }
/**
 * Appliquer le tri
 */
private function applyTransactionSorting($query, Request $request)
{
    $sortBy = $request->get('sort', 'created_at');
    $order = strtolower($request->get('order', 'desc'));

    // Valider les colonnes de tri autorisées
    $allowedSorts = ['created_at', 'montant', 'type', 'statut'];
    if (!in_array($sortBy, $allowedSorts)) {
        $sortBy = 'created_at';
    }

    // Valider l'ordre
    if (!in_array($order, ['asc', 'desc'])) {
        $order = 'desc';
    }

    $query->orderBy($sortBy, $order);
}

/**
 * Obtenir le label lisible pour un type de transaction
 */
private function getTypeLabel(string $type): string
{
    $labels = [
        'transfert' => 'Transfert d\'argent',
        'paiement' => 'Paiement marchand',
        'depot' => 'Dépôt',
        'retrait' => 'Retrait',
        'achat_credit' => 'Achat de crédit'
    ];

    return $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
}

/**
 * Calculer le montant à afficher selon le type de transaction et la perspective du compte
 */
private function calculateDisplayAmount($transaction, $compte): float
{
    switch ($transaction->type) {
        case 'depot':
            // Les dépôts sont toujours positifs (crédits)
            return $transaction->montant;

        case 'retrait':
            // Les retraits sont toujours négatifs (débits)
            return -$transaction->montant;

        case 'paiement':
        case 'achat_credit':
            // Les paiements sont toujours négatifs (débits)
            return -$transaction->montant;

        case 'transfert':
            // Pour les transferts, dépend de si le compte est émetteur ou destinataire
            if ($transaction->compte_emetteur_id == $compte->id) {
                // Transfert sortant : négatif
                return -$transaction->montant;
            } else {
                // Transfert entrant : positif
                return $transaction->montant;
            }

        default:
            return $transaction->montant;
    }
}

/**
 * Générer la référence au format prototype (PP251119.1400.C27819)
 */
private function generatePrototypeReference($transaction): string
{
    $date = $transaction->created_at ?? now();
    $day = $date->format('d');
    $month = $date->format('m');
    $year = $date->format('y');
    $hour = $date->format('H');
    $minute = $date->format('i');

    // Générer un code aléatoire de 5 caractères
    $randomCode = strtoupper(substr(md5($transaction->id), 0, 5));

    return "PP{$year}{$month}{$day}.{$hour}{$minute}.{$randomCode}";
}

/**
 * Formater le destinataire avec nom complet
 */
private function formatDestinataireWithEmoji($transaction): string
{
    if ($transaction->destinataire && $transaction->destinataire->user) {
        return $transaction->destinataire->user->nom . ' ' . $transaction->destinataire->user->prenom;
    } elseif ($transaction->destinataire_nom) {
        return $transaction->destinataire_nom;
    }
    return 'Destinataire inconnu';
}

/**
 * Formater l'expéditeur (numéro de téléphone uniquement)
 */
private function formatExpediteur($transaction): string
{
    if ($transaction->emetteur && $transaction->emetteur->user) {
        return $transaction->emetteur->user->telephone;
    }
    return 'Numéro inconnu';
}

/**
 * Formater le montant (avec CFA, sans frais séparés)
 */
private function formatMontant($transaction): string
{
    return $transaction->montant . ' CFA';
}

/**
 * Formater la date au format français
 */
private function formatDate($transaction): string
{
    $date = $transaction->date_transaction ?? $transaction->created_at ?? now();
    return $date->format('d/m/Y H:i');
}

/**
 * Vérifier si la transaction peut être annulée
 */
private function canCancelTransaction($transaction): bool
{
    // Une transaction peut être annulée si elle est récente (moins de 24h) et en statut 'validee'
    $isRecent = $transaction->created_at && $transaction->created_at->diffInHours(now()) < 24;
    return $transaction->statut === 'validee' && $isRecent;
}


}
