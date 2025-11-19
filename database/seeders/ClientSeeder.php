<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer tous les comptes (maintenant un seul par utilisateur)
        $comptes = \App\Models\Compte::all();

        foreach ($comptes as $compte) {
            \App\Models\Client::create([
                'compte_id' => $compte->id,
            ]);
        }
    }
}
