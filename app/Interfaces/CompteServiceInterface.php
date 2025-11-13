<?php

namespace App\Interfaces;

interface CompteServiceInterface
{
    public function generateQrCode(\App\Models\Compte $compte): string;
    public function canMakeTransaction(\App\Models\Compte $compte, float $montant): bool;
    public function isWithinDailyLimit(\App\Models\Compte $compte, float $montant): bool;
    public function getFormattedBalance(\App\Models\Compte $compte): string;
    public function getTotalBalance(int $userId): float;
}