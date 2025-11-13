<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Repository pour les transactions
 * Open/Closed Principle: Extensible avec de nouveaux filtres
 * Single Responsibility: Gestion des données de transaction
 */
class TransactionRepository extends BaseRepository
{
    public function __construct(Transaction $transaction)
    {
        parent::__construct($transaction);
    }

    /**
     * Récupère l'historique des transactions d'un utilisateur
     * Open/Closed: Logique métier centralisée
     */
    public function getUserTransactionHistory(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where(function ($q) use ($userId) {
                $q->whereHas('emetteur', function ($subQ) use ($userId) {
                    $subQ->where('user_id', $userId);
                })->orWhereHas('destinataire', function ($subQ) use ($userId) {
                    $subQ->where('user_id', $userId);
                });
            })
            ->with(['emetteur.user', 'destinataire.user', 'marchand']);

        // Applique les filtres
        $query = $this->applyFilters($query, $filters);

        // Tri par défaut
        $query->orderBy('created_at', 'desc');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Récupère les transactions d'un marchand
     */
    public function getMerchantTransactions(int $merchantId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('marchand_id', $merchantId)
            ->with(['emetteur.user']);

        $query = $this->applyFilters($query, $filters);
        $query->orderBy('created_at', 'desc');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Recherche de transactions par critères
     */
    public function search(array $criteria): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['emetteur.user', 'destinataire.user', 'marchand']);

        // Appliquer les filtres via les méthodes spécialisées
        $filters = [];
        if (isset($criteria['montant_min'])) $filters['montant_min'] = $criteria['montant_min'];
        if (isset($criteria['montant_max'])) $filters['montant_max'] = $criteria['montant_max'];
        if (isset($criteria['type'])) $filters['type'] = $criteria['type'];
        if (isset($criteria['status'])) $filters['status'] = $criteria['statut'] ?? $criteria['status'];

        $query = $this->applyFilters($query, $filters);

        // Filtres de date manuels
        if (isset($criteria['date_debut'])) {
            $query->whereDate('created_at', '>=', $criteria['date_debut']);
        }

        if (isset($criteria['date_fin'])) {
            $query->whereDate('created_at', '<=', $criteria['date_fin']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($criteria['per_page'] ?? 20);
    }

    /**
     * Statistiques des transactions
     */
    public function getStatistics(array $filters = []): array
    {
        $query = $this->applyFilters($this->model->newQuery(), $filters);

        return [
            'total_transactions' => $query->count(),
            'total_montant' => $query->sum('montant'),
            'transactions_validees' => (clone $query)->where('statut', 'validee')->count(),
            'transactions_en_attente' => (clone $query)->where('statut', 'en_attente')->count(),
            'transactions_echouees' => (clone $query)->where('statut', 'echouee')->count(),
        ];
    }

    /**
     * Filtre par type de transaction
     */
    protected function filterType(Builder $query, $value): Builder
    {
        return $query->where('type', $value);
    }

    /**
     * Filtre par montant minimum
     */
    protected function filterMontantMin(Builder $query, $value): Builder
    {
        return $query->where('montant', '>=', $value);
    }

    /**
     * Filtre par montant maximum
     */
    protected function filterMontantMax(Builder $query, $value): Builder
    {
        return $query->where('montant', '<=', $value);
    }

    /**
     * Filtre par période
     */
    protected function filterPeriode(Builder $query, $value): Builder
    {
        // $value peut être 'today', 'week', 'month', 'year'
        switch ($value) {
            case 'today':
                return $query->whereDate('created_at', today());
            case 'week':
                return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            case 'month':
                return $query->whereMonth('created_at', now()->month)
                           ->whereYear('created_at', now()->year);
            case 'year':
                return $query->whereYear('created_at', now()->year);
            default:
                return $query;
        }
    }

    /**
     * Filtre par numéro destinataire
     */
    protected function filterDestinataire(Builder $query, $value): Builder
    {
        return $query->where('destinataire_numero', 'like', "%{$value}%");
    }

    /**
     * Filtre par code marchand
     */
    protected function filterMarchand(Builder $query, $value): Builder
    {
        return $query->where('code_marchand', $value);
    }
}