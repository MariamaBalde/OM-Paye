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
        // Récupérer les comptes principaux
        $comptes = \App\Models\Compte::where('type', 'principal')->get();

        foreach ($comptes as $compte) {
            \App\Models\Client::create([
                'compte_id' => $compte->id,
                'type_client' => rand(0, 1) ? 'particulier' : 'professionnel',
                'date_naissance' => fake()->date('Y-m-d', '-18 years'),
                'adresse' => fake()->streetAddress(),
                'ville' => 'Dakar',
                'pays' => 'Sénégal',
                'piece_identite_type' => 'CNI',
                'piece_identite_numero' => strtoupper(fake()->bothify('##########')),
                'contacts_favoris' => json_encode([
                    fake()->phoneNumber(),
                    fake()->phoneNumber(),
                ]),
            ]);
        }
    }
}
