<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\VerificationCode;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;

/**
 * Service pour la logique métier des transactions
 * Single Responsibility: Gérer toute la logique des transactions
 */
class TransactionService
{
    /**
     * Calcule les frais de transfert
     */
    public function calculateTransferFees(float $montant): float
    {
        // Orange Money fees: 1% with min 100 FCFA, max 5000 FCFA
        $fees = $montant * 0.01;
        return max(100, min($fees, 5000));
    }

    /**
     * Calcule les frais de paiement
     */
    public function calculatePaymentFees(float $montant): float
    {
        // Payment fees: 0.5% with min 50 FCFA
        return max(50, $montant * 0.005);
    }

    /**
     * Génère un code de vérification
     */
    public function generateVerificationCode(): string
    {
        return str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Initie un transfert
     */
    public function initiateTransfer(array $data, int $userId): Transaction
    {
        $compteEmetteur = Compte::where('user_id', $userId)
            ->where('type', 'principal')
            ->first();

        $compteDestinataire = Compte::whereHas('user', function($q) use ($data) {
            $q->where('telephone', $data['destinataire_numero']);
        })->first();

        $montant = $data['montant'];
        $frais = $this->calculateTransferFees($montant);

        DB::transaction(function () use ($compteEmetteur, $compteDestinataire, $montant, $frais, $data, $userId) {
            // Créer la transaction
            $transaction = Transaction::create([
                'compte_emetteur_id' => $compteEmetteur->id,
                'compte_destinataire_id' => $compteDestinataire->id,
                'type' => 'transfert',
                'montant' => $montant,
                'frais' => $frais,
                'montant_total' => $montant + $frais,
                'destinataire_numero' => $data['destinataire_numero'],
                'destinataire_nom' => $compteDestinataire->user->nom . ' ' . $compteDestinataire->user->prenom,
                'statut' => 'en_attente',
            ]);

            // Générer le code de vérification
            VerificationCode::create([
                'user_id' => $userId,
                'transaction_id' => $transaction->id,
                'code' => $this->generateVerificationCode(),
                'type' => 'sms',
                'expire_at' => now()->addMinutes(5),
            ]);
        });

        return Transaction::latest()->first();
    }

    /**
     * Initie un paiement marchand
     */
    public function initiatePayment(array $data, int $userId): Transaction
    {
        $compteEmetteur = Compte::where('user_id', $userId)
            ->where('type', 'principal')
            ->first();

        $marchand = \App\Models\Marchand::where('code_marchand', $data['code_marchand'])->first();
        $montant = $data['montant'];
        $frais = $this->calculatePaymentFees($montant);

        DB::transaction(function () use ($compteEmetteur, $marchand, $montant, $frais, $userId) {
            $transaction = Transaction::create([
                'compte_emetteur_id' => $compteEmetteur->id,
                'marchand_id' => $marchand->id,
                'type' => 'paiement',
                'montant' => $montant,
                'frais' => $frais,
                'montant_total' => $montant + $frais,
                'statut' => 'en_attente',
            ]);

            VerificationCode::create([
                'user_id' => $userId,
                'transaction_id' => $transaction->id,
                'code' => $this->generateVerificationCode(),
                'type' => 'sms',
                'expire_at' => now()->addMinutes(5),
            ]);
        });

        return Transaction::latest()->first();
    }

    /**
     * Valide une transaction avec code de vérification
     */
    public function verifyAndCompleteTransaction(int $transactionId, string $code, int $userId): Transaction
    {
        $transaction = Transaction::findOrFail($transactionId);

        // Vérifier que la transaction appartient à l'utilisateur
        if ($transaction->emetteur->user_id !== $userId) {
            throw new \Exception('Transaction non autorisée');
        }

        // Vérifier le code
        $verificationCode = $transaction->verificationCode;
        if (!$verificationCode || $verificationCode->code !== $code || $verificationCode->expire_at < now()) {
            throw new \Exception('Code de vérification invalide ou expiré');
        }

        DB::transaction(function () use ($transaction, $verificationCode) {
            // Marquer le code comme vérifié
            $verificationCode->update(['verifie' => true]);

            // Traiter la transaction
            $compteEmetteur = $transaction->emetteur;
            $compteEmetteur->decrement('solde', $transaction->montant_total);

            if ($transaction->destinataire) {
                $transaction->destinataire->increment('solde', $transaction->montant);
            }

            $transaction->update([
                'statut' => 'validee',
                'code_verifie' => true
            ]);
        });

        return $transaction->fresh();
    }
}