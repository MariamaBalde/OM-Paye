<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TransactionHistoryController extends Controller
{
    use ApiResponseTrait;

    protected $transactionRepository;

    public function __construct(TransactionRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Get transaction history
     */
    public function index(Request $request): JsonResponse
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
            return $this->errorResponse('Rôle non autorisé pour consulter l\'historique', 403);
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
     * Get transaction details
     */
    public function show(int $id): JsonResponse
    {
        $user = auth()->user();
        $transaction = $this->transactionRepository->find($id);

        if (!$transaction) {
            return $this->notFoundResponse('Transaction non trouvée');
        }

        // Vérification des autorisations via Policy
        if (!Gate::allows('view', $transaction)) {
            return $this->errorResponse('Accès non autorisé à cette transaction', 403);
        }

        return $this->successResponse(
            new TransactionResource($transaction->load(['emetteur.user', 'destinataire.user', 'marchand'])),
            'Transaction récupérée'
        );
    }

    /**
     * Search transactions
     */
    public function search(Request $request): JsonResponse
    {
        $criteria = $request->only([
            'montant_min', 'montant_max', 'date_debut', 'date_fin',
            'type', 'status', 'per_page'
        ]);

        // Ajouter le filtre utilisateur pour les clients
        $user = auth()->user();
        if ($user->hasRole('client')) {
            $criteria['user_id'] = $user->id;
        }

        $transactions = $this->transactionRepository->search($criteria);

        return $this->paginatedResponse($transactions, 'Résultats de recherche');
    }

    /**
     * Get transaction statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = auth()->user();
        $filters = $request->only(['periode', 'type', 'status']);

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