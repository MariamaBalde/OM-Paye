<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TransactionPolicy
{
    /**
     * Determine whether the user can view any transactions.
     */
    public function viewAny(User $user): bool
    {
        return $user->statut === 'actif';
    }

    /**
     * Determine whether the user can view the transaction.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        // L'utilisateur peut voir ses propres transactions
        if ($transaction->compte_emetteur && $transaction->compte_emetteur->user_id === $user->id) {
            return true;
        }

        // L'utilisateur peut voir les transactions reçues (si destinataire)
        if ($transaction->compte_destinataire && $transaction->compte_destinataire->user_id === $user->id) {
            return true;
        }

        // Les marchands peuvent voir les paiements reçus
        if ($user->isMarchand() && $transaction->marchand_id && $transaction->marchand->user_id === $user->id) {
            return true;
        }

        // Les admins peuvent voir toutes les transactions
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create transactions.
     */
    public function create(User $user): bool
    {
        return $user->statut === 'actif' &&
               $user->comptePrincipal &&
               $user->comptePrincipal->statut === 'actif';
    }

    /**
     * Determine whether the user can update the transaction.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        // Seules les transactions en attente peuvent être modifiées
        if ($transaction->statut !== 'en_attente') {
            return false;
        }

        // L'utilisateur doit être l'émetteur
        return $transaction->compte_emetteur &&
               $transaction->compte_emetteur->user_id === $user->id;
    }

    /**
     * Determine whether the user can verify the transaction.
     */
    public function verify(User $user, Transaction $transaction): bool
    {
        return $transaction->statut === 'en_attente' &&
               $transaction->compte_emetteur &&
               $transaction->compte_emetteur->user_id === $user->id;
    }

    /**
     * Determine whether the user can cancel the transaction.
     */
    public function cancel(User $user, Transaction $transaction): bool
    {
        return $transaction->statut === 'en_attente' &&
               $transaction->compte_emetteur &&
               $transaction->compte_emetteur->user_id === $user->id;
    }

    /**
     * Determine whether the user can view all transactions (admin only).
     */
    public function viewAll(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        return false; // Les transactions ne peuvent pas être supprimées
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Transaction $transaction): bool
    {
        return false; // Pas de soft delete pour les transactions
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Transaction $transaction): bool
    {
        return false; // Les transactions sont conservées pour audit
    }
}
