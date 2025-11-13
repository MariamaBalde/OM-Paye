<?php

namespace App\Services;

use App\Models\Compte;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Service pour la logique métier des comptes
 * Single Responsibility: Gérer les opérations sur les comptes
 */
class CompteService
{
    /**
     * Génère un QR code pour le compte
     */
    public function generateQrCode(Compte $compte): string
    {
        $data = [
            'numero_compte' => $compte->numero_compte,
            'user_id' => $compte->user_id,
            'type' => $compte->type,
        ];

        // Encoder en JSON puis base64 pour le QR code
        $qrData = base64_encode(json_encode($data));

        return $qrData;
    }

    /**
     * Vérifie si un compte peut effectuer une transaction
     */
    public function canMakeTransaction(Compte $compte, float $montant): bool
    {
        return $compte->statut === 'actif' &&
               $compte->solde >= $montant &&
               $this->isWithinDailyLimit($compte, $montant);
    }

    /**
     * Vérifie la limite journalière
     */
    public function isWithinDailyLimit(Compte $compte, float $montant): bool
    {
        // Calculer le total des transactions du jour
        $totalToday = $compte->transactionsEmises()
            ->whereDate('created_at', today())
            ->where('statut', 'validee')
            ->sum('montant_total');

        return ($totalToday + $montant) <= $compte->plafond_journalier;
    }

    /**
     * Obtient le solde formaté
     */
    public function getFormattedBalance(Compte $compte): string
    {
        return number_format($compte->solde, 2, ',', ' ') . ' FCFA';
    }

    /**
     * Calcule le solde total de tous les comptes actifs d'un utilisateur
     */
    public function getTotalBalance(int $userId): float
    {
        return Compte::where('user_id', $userId)
            ->where('statut', 'actif')
            ->sum('solde');
    }
}