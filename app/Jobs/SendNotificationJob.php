<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionId;
    protected $type;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $transactionId, string $type, int $userId = null)
    {
        $this->transactionId = $transactionId;
        $this->type = $type;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $transaction = Transaction::with(['emetteur.user', 'destinataire.user', 'marchand'])->find($this->transactionId);

        if (!$transaction) {
            Log::error('Transaction not found for notification', ['transaction_id' => $this->transactionId]);
            return;
        }

        try {
            switch ($this->type) {
                case 'transaction_initiated':
                    $this->sendTransactionInitiatedNotification($transaction);
                    break;
                case 'transaction_completed':
                    $this->sendTransactionCompletedNotification($transaction);
                    break;
                case 'transaction_failed':
                    $this->sendTransactionFailedNotification($transaction);
                    break;
                default:
                    Log::warning('Unknown notification type', ['type' => $this->type]);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Notification sending failed', [
                'transaction_id' => $transaction->id,
                'type' => $this->type,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendTransactionInitiatedNotification(Transaction $transaction): void
    {
        $user = $transaction->emetteur->user;

        // Ici on pourrait envoyer un SMS ou email
        // Pour l'exemple, on log seulement
        Log::info('Notification: Transaction initiée', [
            'user' => $user->telephone,
            'transaction' => $transaction->reference,
            'montant' => $transaction->montant . ' FCFA'
        ]);

        // TODO: Implémenter l'envoi réel de SMS/email
        // SMS::send($user->telephone, "Transaction initiée: {$transaction->reference}");
    }

    private function sendTransactionCompletedNotification(Transaction $transaction): void
    {
        $user = $transaction->emetteur->user;

        Log::info('Notification: Transaction validée', [
            'user' => $user->telephone,
            'transaction' => $transaction->reference,
            'montant' => $transaction->montant . ' FCFA',
            'solde_restant' => $transaction->emetteur->solde . ' FCFA'
        ]);

        // Notifier aussi le destinataire si c'est un transfert
        if ($transaction->destinataire) {
            $destinataire = $transaction->destinataire->user;
            Log::info('Notification destinataire: Transfert reçu', [
                'user' => $destinataire->telephone,
                'montant' => $transaction->montant . ' FCFA',
                'expediteur' => $user->nom . ' ' . $user->prenom
            ]);
        }

        // TODO: Implémenter l'envoi réel de SMS/email
    }

    private function sendTransactionFailedNotification(Transaction $transaction): void
    {
        $user = $transaction->emetteur->user;

        Log::info('Notification: Transaction échouée', [
            'user' => $user->telephone,
            'transaction' => $transaction->reference,
            'raison' => 'Solde insuffisant ou erreur système'
        ]);

        // TODO: Implémenter l'envoi réel de SMS/email
    }
}