<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MarchandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer un compte existant pour les marchands
        $compte = \App\Models\Compte::first();

        if ($compte) {
            \App\Models\Marchand::create([
                'compte_id' => $compte->id,
                'nom_commercial' => 'Orange Shop Dakar',
                'code_marchand' => 'ORANGE001',
                'qr_code_marchand' => 'QR_ORANGE001',
                'secteur_activite' => 'Téléphonie',
                'adresse_boutique' => 'Plateau, Dakar',
                'ville' => 'Dakar',
                'telephone_professionnel' => '338001234',
                'statut' => 'actif',
                'commission_rate' => 0.02,
            ]);

            \App\Models\Marchand::create([
                'compte_id' => $compte->id,
                'nom_commercial' => 'Boutique Express',
                'code_marchand' => 'EXPRESS002',
                'qr_code_marchand' => 'QR_EXPRESS002',
                'secteur_activite' => 'Commerce général',
                'adresse_boutique' => 'Liberté 6, Dakar',
                'ville' => 'Dakar',
                'telephone_professionnel' => '338005678',
                'statut' => 'actif',
                'commission_rate' => 0.015,
            ]);
        }
    }
}
