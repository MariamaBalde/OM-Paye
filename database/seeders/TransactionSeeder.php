<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les comptes et marchands
        $compte1 = \App\Models\Compte::whereHas('user', function($q) {
            $q->where('telephone', '782917770');
        })->first(); // Abdoulaye

        $compte2 = \App\Models\Compte::whereHas('user', function($q) {
            $q->where('telephone', '771234567');
        })->first(); // Fatou

        $compte3 = \App\Models\Compte::whereHas('user', function($q) {
            $q->where('telephone', '765432109');
        })->first(); // Moussa

        $marchand1 = \App\Models\Marchand::where('code_marchand', 'ORANGE001')->first();
        $marchand2 = \App\Models\Marchand::where('code_marchand', 'EXPRESS002')->first();

        // Transactions d'Abdoulaye
        if ($compte1) {
            // Transfert réussi vers Fatou
            if ($compte2) {
                \App\Models\Transaction::create([
                    'compte_emetteur_id' => $compte1->id,
                    'compte_destinataire_id' => $compte2->id,
                    'type' => 'transfert',
                    'montant' => 25000.00,
                    'frais' => 500.00,
                    'montant_total' => 25500.00,
                    'destinataire_numero' => '771234567',
                    'destinataire_nom' => 'Fatou Sarr',
                    'statut' => 'validee',
                    'code_verification' => '1234',
                    'code_verifie' => true,
                ]);
            }

            // Paiement marchand réussi
            if ($marchand1) {
                \App\Models\Transaction::create([
                    'compte_emetteur_id' => $compte1->id,
                    'marchand_id' => $marchand1->id,
                    'type' => 'paiement',
                    'montant' => 15000.00,
                    'frais' => 300.00,
                    'montant_total' => 15300.00,
                    'statut' => 'validee',
                    'code_verification' => '5678',
                    'code_verifie' => true,
                ]);
            }

            // Dépôt réussi
            \App\Models\Transaction::create([
                'compte_emetteur_id' => $compte1->id,
                'type' => 'depot',
                'montant' => 50000.00,
                'frais' => 0.00,
                'montant_total' => 50000.00,
                'statut' => 'validee',
                'code_verification' => '9012',
                'code_verifie' => true,
            ]);

            // Transaction en attente
            if ($compte3) {
                \App\Models\Transaction::create([
                    'compte_emetteur_id' => $compte1->id,
                    'compte_destinataire_id' => $compte3->id,
                    'type' => 'transfert',
                    'montant' => 10000.00,
                    'frais' => 200.00,
                    'montant_total' => 10200.00,
                    'destinataire_numero' => '765432109',
                    'destinataire_nom' => 'Moussa Ndiaye',
                    'statut' => 'en_attente',
                    'code_verification' => '4321',
                    'code_verifie' => false,
                ]);
            }

            // Transaction échouée
            \App\Models\Transaction::create([
                'compte_emetteur_id' => $compte1->id,
                'type' => 'retrait',
                'montant' => 20000.00,
                'frais' => 400.00,
                'montant_total' => 20400.00,
                'statut' => 'echouee',
            ]);
        }
    }
}
