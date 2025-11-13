<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les utilisateurs existants
        $users = \App\Models\User::all();

        foreach ($users as $user) {
            // Compte unique pour chaque utilisateur
            \App\Models\Compte::create([
                'user_id' => $user->id,
                'numero_compte' => 'OMCPT' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                'solde' => $this->getInitialSolde($user->telephone),
                'qr_code' => 'QR_' . $user->telephone,
                'code_secret' => bcrypt('1234'), // Code secret par défaut
                'plafond_journalier' => 500000.00,
                'statut' => 'actif',
                'date_ouverture' => now()->subDays(rand(1, 365)),
            ]);
        }
    }

    private function getInitialSolde($telephone)
    {
        // Soldes basés sur les utilisateurs du seeder
        $soldes = [
            '782917770' => 150000.00, // Abdoulaye
            '771234567' => 75000.50,  // Fatou
            '765432109' => 250000.75, // Moussa
            '783456789' => 50000.00,  // Aminata
            '789012345' => 5000.25,   // Ibrahima
        ];

        return $soldes[$telephone] ?? rand(10000, 100000);
    }
}
