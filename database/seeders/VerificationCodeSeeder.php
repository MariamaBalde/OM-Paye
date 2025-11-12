<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VerificationCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les comptes et transactions
        $compte1 = \App\Models\Compte::whereHas('user', function($q) {
            $q->where('telephone', '782917770');
        })->first();

        $transaction1 = \App\Models\Transaction::where('compte_emetteur_id', $compte1?->id ?? 0)
            ->where('statut', 'en_attente')
            ->first();

        // Code actif pour transaction en attente
        if ($compte1 && $transaction1) {
            \App\Models\VerificationCode::create([
                'user_id' => $compte1->user_id,
                'transaction_id' => $transaction1->id,
                'code' => '9876',
                'type' => 'sms',
                'expire_at' => now()->addMinutes(5),
                'verifie' => false,
            ]);
        }

        // Code vérifié pour transaction réussie
        if ($compte1) {
            $transaction2 = \App\Models\Transaction::where('compte_emetteur_id', $compte1->id)
                ->where('statut', 'validee')
                ->first();
            if ($transaction2) {
                \App\Models\VerificationCode::create([
                    'user_id' => $compte1->user_id,
                    'transaction_id' => $transaction2->id,
                    'code' => '1234',
                    'type' => 'sms',
                    'expire_at' => now()->subMinutes(10),
                    'verifie' => true,
                ]);
            }
        }

        // Code expiré
        $compte2 = \App\Models\Compte::whereHas('user', function($q) {
            $q->where('telephone', '771234567');
        })->first();

        if ($compte2) {
            \App\Models\VerificationCode::create([
                'user_id' => $compte2->user_id,
                'code' => '0000',
                'type' => 'app',
                'expire_at' => now()->subMinutes(10),
                'verifie' => false,
            ]);
        }

        // Codes pour autres comptes
        $comptes = \App\Models\Compte::all();
        foreach ($comptes as $compte) {
            \App\Models\VerificationCode::create([
                'user_id' => $compte->user_id,
                'code' => rand(1000, 9999),
                'type' => rand(0, 1) ? 'sms' : 'app',
                'expire_at' => now()->addMinutes(rand(1, 10)),
                'verifie' => rand(0, 1) ? true : false,
            ]);
        }
    }
}
