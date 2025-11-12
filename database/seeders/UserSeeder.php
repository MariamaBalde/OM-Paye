<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Utilisateur principal pour les tests
        \App\Models\User::create([
            'nom' => 'Diallo',
            'prenom' => 'Abdoulaye',
            'telephone' => '782917770',
            'password' => bcrypt('password123'),
            'role' => 'client',
            'statut' => 'actif',
            'langue' => 'français',
            'theme_sombre' => false,
            'scanner_actif' => true,
        ]);

        // Autres utilisateurs pour les transferts
        \App\Models\User::create([
            'nom' => 'Sarr',
            'prenom' => 'Fatou',
            'telephone' => '771234567',
            'password' => bcrypt('password123'),
            'role' => 'client',
            'statut' => 'actif',
            'langue' => 'français',
            'theme_sombre' => true,
            'scanner_actif' => true,
        ]);

        \App\Models\User::create([
            'nom' => 'Ndiaye',
            'prenom' => 'Moussa',
            'telephone' => '765432109',
            'password' => bcrypt('password123'),
            'role' => 'client',
            'statut' => 'actif',
            'langue' => 'français',
            'theme_sombre' => false,
            'scanner_actif' => false,
        ]);

        \App\Models\User::create([
            'nom' => 'Ba',
            'prenom' => 'Aminata',
            'telephone' => '783456789',
            'password' => bcrypt('password123'),
            'role' => 'client',
            'statut' => 'actif',
            'langue' => 'français',
            'theme_sombre' => true,
            'scanner_actif' => true,
        ]);

        // Utilisateur avec solde faible pour tests
        \App\Models\User::create([
            'nom' => 'Gaye',
            'prenom' => 'Ibrahima',
            'telephone' => '789012345',
            'password' => bcrypt('password123'),
            'role' => 'client',
            'statut' => 'actif',
            'langue' => 'français',
            'theme_sombre' => false,
            'scanner_actif' => true,
        ]);
    }
}
