<?php

namespace App\Listeners;

use App\Events\TransactionCompleted;
use App\Jobs\SendNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class TransactionCompletedListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(TransactionCompleted $event): void
    {
        $transaction = $event->transaction;

        // Log de l'événement
        Log::info('Transaction complétée', [
            'transaction_id' => $transaction->id,
            'type' => $transaction->type,
            'montant' => $transaction->montant,
            'user_id' => $transaction->emetteur->user_id
        ]);

        // Envoyer notification de succès
        SendNotificationJob::dispatch(
            $transaction->id,
            'transaction_completed',
            $transaction->emetteur->user_id
        )->onQueue('notifications');

        // Actions supplémentaires selon le type de transaction
        switch ($transaction->type) {
            case 'transfert':
                $this->handleTransferCompleted($transaction);
                break;
            case 'paiement':
                $this->handlePaymentCompleted($transaction);
                break;
        }
    }

    private function handleTransferCompleted($transaction): void
    {
        // Mettre à jour les statistiques du destinataire
        if ($transaction->destinataire) {
            // Ici on pourrait mettre à jour des statistiques ou envoyer des notifications push
            Log::info('Transfert reçu', [
                'destinataire' => $transaction->destinataire->user->telephone,
                'montant' => $transaction->montant
            ]);
        }
    }

    private function handlePaymentCompleted($transaction): void
    {
        // Notifier le marchand
        if ($transaction->marchand) {
            Log::info('Paiement marchand reçu', [
                'marchand' => $transaction->marchand->nom_commercial,
                'montant' => $transaction->montant
            ]);
        }
    }
}