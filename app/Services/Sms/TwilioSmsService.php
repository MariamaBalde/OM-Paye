<?php

namespace App\Services\Sms;

use App\Interfaces\SmsServiceInterface;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

/**
 * Service SMS pour Twilio
 * Alternative fiable pour l'envoi de SMS
 */
class TwilioSmsService implements SmsServiceInterface
{
    private ?Client $client;
    private string $fromNumber;

    public function __construct()
    {
        $sid = config('services.sms.twilio.sid');
        $token = config('services.sms.twilio.token');
        $this->fromNumber = config('services.sms.twilio.from_number', '+1234567890');

        if ($sid && $token) {
            $this->client = new Client($sid, $token);
        } else {
            $this->client = null;
        }
    }

    /**
     * Envoie un SMS via Twilio
     */
    public function sendSms(string $to, string $message): array
    {
        if (!$this->client) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Twilio not configured'
            ];
        }

        try {
            // Formatage du numéro pour Twilio
            $formattedNumber = $this->formatPhoneNumber($to);

            $sms = $this->client->messages->create(
                $formattedNumber,
                [
                    'from' => $this->fromNumber,
                    'body' => $message
                ]
            );

            return [
                'success' => true,
                'message_id' => $sms->sid,
                'error' => null
            ];

        } catch (\Exception $e) {
            Log::error('Twilio SMS send error', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Vérifie si le service est disponible
     */
    public function isAvailable(): bool
    {
        return $this->client !== null;
    }

    /**
     * Obtient le solde du compte Twilio
     */
    public function getBalance(): array
    {
        if (!$this->client) {
            return [
                'balance' => null,
                'currency' => null,
                'error' => 'Twilio not configured'
            ];
        }

        try {
            $balance = $this->client->balance->fetch();

            return [
                'balance' => (float) $balance->balance,
                'currency' => $balance->currency,
                'error' => null
            ];

        } catch (\Exception $e) {
            return [
                'balance' => null,
                'currency' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Formate le numéro de téléphone pour Twilio
     */
    private function formatPhoneNumber(string $number): string
    {
        // Supprimer tous les espaces et caractères spéciaux
        $number = preg_replace('/\s+/', '', $number);

        // Si le numéro commence par 221, ajouter +
        if (str_starts_with($number, '221')) {
            return '+' . $number;
        }

        // Si le numéro commence par 00 221, convertir
        if (str_starts_with($number, '00221')) {
            return '+221' . substr($number, 5);
        }

        // Si le numéro commence par +, l'utiliser tel quel
        if (str_starts_with($number, '+')) {
            return $number;
        }

        // Sinon, ajouter +221 au début (pour les numéros sénégalais)
        return '+221' . ltrim($number, '0');
    }
}