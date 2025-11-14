<?php

namespace App\Services;

use App\Interfaces\SmsServiceInterface;
use App\Services\Sms\TwilioSmsService;
use Illuminate\Support\Facades\Log;

/**
 * Gestionnaire principal des services SMS
 * Utilise le pattern Strategy pour changer de fournisseur facilement
 */
class SmsService implements SmsServiceInterface
{
    private SmsServiceInterface $service;

    public function __construct()
    {
        $driver = config('services.sms.default', 'log');

        $this->service = match ($driver) {
            'twilio' => new TwilioSmsService(),
            'log' => new LogSmsService(),
            default => new LogSmsService(),
        };
    }

    /**
     * Envoie un SMS
     */
    public function sendSms(string $to, string $message): array
    {
        return $this->service->sendSms($to, $message);
    }

    /**
     * Vérifie si le service est disponible
     */
    public function isAvailable(): bool
    {
        return $this->service->isAvailable();
    }

    /**
     * Obtient le solde du compte SMS
     */
    public function getBalance(): array
    {
        return $this->service->getBalance();
    }

    /**
     * Change le service SMS dynamiquement
     */
    public function setService(SmsServiceInterface $service): void
    {
        $this->service = $service;
    }

    /**
     * Obtient le service actuel
     */
    public function getCurrentService(): SmsServiceInterface
    {
        return $this->service;
    }
}

/**
 * Service SMS de fallback qui log seulement (pour développement)
 */
class LogSmsService implements SmsServiceInterface
{
    public function sendSms(string $to, string $message): array
    {
        Log::info("SMS envoyé à {$to}: {$message}");

        return [
            'success' => true,
            'message_id' => 'log_' . time(),
            'error' => null
        ];
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function getBalance(): array
    {
        return [
            'balance' => 999999,
            'currency' => 'XOF',
            'error' => null
        ];
    }
}