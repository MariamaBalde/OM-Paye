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
                'statut' => 'actif',
            ]);

            \App\Models\Marchand::create([
                'compte_id' => $compte->id,
                'nom_commercial' => 'Boutique Express',
                'code_marchand' => 'EXPRESS002',
                'statut' => 'actif',
            ]);
        }
    }
}
