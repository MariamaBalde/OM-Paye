<?php

namespace App\Interfaces;

/**
 * Interface pour les services SMS
 * Permet de changer facilement de fournisseur SMS
 */
interface SmsServiceInterface
{
    /**
     * Envoie un SMS
     *
     * @param string $to Numéro de téléphone destinataire
     * @param string $message Contenu du SMS
     * @return array ['success' => bool, 'message_id' => string|null, 'error' => string|null]
     */
    public function sendSms(string $to, string $message): array;

    /**
     * Vérifie si le service est disponible
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Obtient le solde du compte SMS
     *
     * @return array ['balance' => float|null, 'currency' => string|null, 'error' => string|null]
     */
    public function getBalance(): array;
}