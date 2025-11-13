<?php

namespace App\Observers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     * Single Responsibility: Log automatique des transactions
     */
    public function created(Transaction $transaction): void
    {
        // Log de création de transaction
        Log::info('Transaction créée', [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'montant' => $transaction->montant,
            'statut' => $transaction->statut,
            'user_id' => auth()->id()
        ]);
    }

    /**
     * Handle the Transaction "updated" event.
     * Single Responsibility: Log des changements de statut
     */
    public function updated(Transaction $transaction): void
    {
        // Log des changements importants
        if ($transaction->wasChanged('statut')) {
            Log::info('Statut de transaction modifié', [
                'id' => $transaction->id,
                'ancien_statut' => $transaction->getOriginal('statut'),
                'nouveau_statut' => $transaction->statut,
                'user_id' => auth()->id()
            ]);
        }
    }
}
