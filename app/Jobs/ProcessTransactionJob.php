<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * Execute the job.
     */
    public function handle(TransactionService $transactionService): void
    {
        $transaction = Transaction::find($this->transactionId);

        if (!$transaction) {
            Log::error('Transaction not found for processing', ['transaction_id' => $this->transactionId]);
            return;
        }

        try {
            DB::transaction(function () use ($transaction) {
                // Traiter la transaction selon son type
                switch ($transaction->type) {
                    case 'transfert':
                        $this->processTransfer($transaction);
                        break;
                    case 'paiement':
                        $this->processPayment($transaction);
                        break;
                    default:
                        Log::warning('Unknown transaction type', ['type' => $transaction->type]);
                        break;
                }

                // Marquer comme traitée
                $transaction->update([
                    'statut' => 'validee',
                    'date_transaction' => now()
                ]);

                Log::info('Transaction processed successfully', [
                    'transaction_id' => $transaction->id,
                    'type' => $transaction->type
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Transaction processing failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            $transaction->update(['statut' => 'echouee']);
            throw $e;
        }
    }

    private function processTransfer(Transaction $transaction): void
    {
        $compteEmetteur = $transaction->emetteur;
        $compteDestinataire = $transaction->destinataire;

        if (!$compteEmetteur || !$compteDestinataire) {
            throw new \Exception('Comptes manquants pour le transfert');
        }

        // Vérifier le solde
        if ($compteEmetteur->solde < $transaction->montant_total) {
            throw new \Exception('Solde insuffisant');
        }

        // Effectuer le transfert
        $compteEmetteur->decrement('solde', $transaction->montant_total);
        $compteDestinataire->increment('solde', $transaction->montant);
    }

    private function processPayment(Transaction $transaction): void
    {
        $compteEmetteur = $transaction->emetteur;
        $marchand = $transaction->marchand;

        if (!$compteEmetteur || !$marchand) {
            throw new \Exception('Compte ou marchand manquant pour le paiement');
        }

        // Vérifier le solde
        if ($compteEmetteur->solde < $transaction->montant_total) {
            throw new \Exception('Solde insuffisant');
        }

        // Effectuer le paiement
        $compteEmetteur->decrement('solde', $transaction->montant_total);
        // Ici on pourrait créditer le compte marchand si nécessaire
    }
}