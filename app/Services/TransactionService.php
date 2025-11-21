<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\VerificationCode;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $compteEmetteur = Compte::where('user_id', $userId)->first();

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
        $compteEmetteur = Compte::where('user_id', $userId)->first();

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
     * Traite un transfert directement (sans vérification)
     */
    public function processTransfer(array $data, int $userId): Transaction
    {
        $compteEmetteur = Compte::where('user_id', $userId)->first();

        if (!$compteEmetteur) {
            throw new \Exception('Compte émetteur non trouvé');
        }

        $compteDestinataire = Compte::whereHas('user', function($q) use ($data) {
            $q->where('telephone', $data['destinataire_numero']);
        })->first();

        if (!$compteDestinataire) {
            throw new \Exception('Compte destinataire non trouvé');
        }

        if ($compteEmetteur->id === $compteDestinataire->id) {
            throw new \Exception('Impossible de transférer vers son propre compte');
        }

        $montant = $data['montant'];
        $frais = $this->calculateTransferFees($montant);
        $montantTotal = $montant + $frais;

        // Vérifier les limites journalières
        if (!$this->isWithinDailyLimit($compteEmetteur, $montantTotal)) {
            throw new \Exception('Limite journalière dépassée');
        }

        DB::transaction(function () use ($compteEmetteur, $compteDestinataire, $montant, $frais, $montantTotal, $data) {
            // Créer et traiter la transaction
            $transaction = Transaction::create([
                'compte_emetteur_id' => $compteEmetteur->id,
                'compte_destinataire_id' => $compteDestinataire->id,
                'type' => 'transfert',
                'montant' => $montant,
                'frais' => $frais,
                'montant_total' => $montantTotal,
                'destinataire_numero' => $data['destinataire_numero'],
                'destinataire_nom' => $compteDestinataire->user->nom . ' ' . $compteDestinataire->user->prenom,
                'statut' => 'validee',
                'code_verifie' => true,
                'description' => $data['description'] ?? 'Transfert',
            ]);

            // Mettre à jour les soldes
            $compteEmetteur->decrement('solde', $montantTotal);
            $compteDestinataire->increment('solde', $montant);
        });

        return Transaction::latest()->first();
    }

    /**
     * Traite un paiement directement (sans vérification)
     */
    public function processPayment(array $data, int $userId): Transaction
    {
        $compteEmetteur = Compte::where('user_id', $userId)->first();

        if (!$compteEmetteur) {
            throw new \Exception('Compte émetteur non trouvé');
        }

        $marchand = \App\Models\Marchand::where('code_marchand', $data['code_marchand'])->first();

        if (!$marchand) {
            throw new \Exception('Marchand non trouvé');
        }

        $montant = $data['montant'];
        $frais = $this->calculatePaymentFees($montant);
        $montantTotal = $montant + $frais;

        DB::transaction(function () use ($compteEmetteur, $marchand, $montant, $frais, $montantTotal, $data) {
            // Créer et traiter la transaction
            $transaction = Transaction::create([
                'compte_emetteur_id' => $compteEmetteur->id,
                'marchand_id' => $marchand->id,
                'type' => 'paiement',
                'montant' => $montant,
                'frais' => $frais,
                'montant_total' => $montantTotal,
                'statut' => 'validee',
                'code_verifie' => true,
                'description' => $data['description'] ?? 'Paiement marchand',
            ]);

            // Mettre à jour le solde
            $compteEmetteur->decrement('solde', $montantTotal);
        });

        return Transaction::latest()->first();
    }

    /**
     * Traite un dépôt directement
     */
    public function processDeposit(array $data, int $userId): Transaction
    {
        $compte = Compte::where('user_id', $userId)->first();

        if (!$compte) {
            throw new \Exception('Compte non trouvé');
        }

        $montant = $data['montant'];

        DB::transaction(function () use ($compte, $montant, $data) {
            // Créer et traiter la transaction
            $transaction = Transaction::create([
                'compte_emetteur_id' => $compte->id,
                'type' => 'depot',
                'montant' => $montant,
                'frais' => 0,
                'montant_total' => $montant,
                'statut' => 'validee',
                'code_verifie' => true,
                'description' => $data['description'] ?? 'Dépôt',
            ]);

            // Mettre à jour le solde du compte
            $compte->increment('solde', $montant);
        });

        return Transaction::latest()->first();
    }

    /**
     * Traite un retrait directement
     */
    public function processWithdrawal(array $data, int $userId): Transaction
    {
        $compte = Compte::where('user_id', $userId)->first();

        if (!$compte) {
            throw new \Exception('Compte non trouvé');
        }

        $montant = $data['montant'];

        // Vérifier le solde calculé
        if ($compte->solde < $montant) {
            throw new \Exception('Solde insuffisant');
        }

        // Vérifier les limites journalières
        if (!$this->isWithinDailyLimit($compte, $montant)) {
            throw new \Exception('Limite journalière dépassée');
        }

        DB::transaction(function () use ($compte, $montant, $data) {
            // Créer et traiter la transaction
            $transaction = Transaction::create([
                'compte_emetteur_id' => $compte->id,
                'type' => 'retrait',
                'montant' => $montant,
                'frais' => 0,
                'montant_total' => $montant,
                'statut' => 'validee',
                'code_verifie' => true,
                'description' => $data['description'] ?? 'Retrait',
            ]);

            // Mettre à jour le solde du compte
            $compte->decrement('solde', $montant);
        });

        return Transaction::latest()->first();
    }

    /**
     * Vérifie si le compte peut effectuer la transaction
     */
    private function isWithinDailyLimit(Compte $compte, float $montant): bool
    {
        // Calculer le total des transactions du jour
        $totalToday = $compte->transactionsEmises()
            ->whereDate('created_at', today())
            ->where('statut', 'validee')
            ->sum('montant_total');

        return ($totalToday + $montant) <= $compte->plafond_journalier;
    }

}