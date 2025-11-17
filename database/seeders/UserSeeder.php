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
    $clientRole = \App\Models\Role::where('name', 'client')->first();
    $adminRole = \App\Models\Role::where('name', 'admin')->first();

    // Utilisateur pour les tests Swagger
    $userTest = \App\Models\User::create([
        'nom' => 'Test',
        'prenom' => 'Swagger',
        'telephone' => '770129911',
        'password' => bcrypt('default_password'),
        'statut' => 'actif',
        'langue' => 'français',
        'theme_sombre' => false,
        'scanner_actif' => true,
    ]);
    $userTest->assignRole('client');

    $user1 = \App\Models\User::create([
        'nom' => 'Diallo',
        'prenom' => 'Abdoulaye',
        'telephone' => '782917770',
        'password' => bcrypt('default_password'),
        'statut' => 'actif',
        'langue' => 'français',
        'theme_sombre' => false,
        'scanner_actif' => true,
    ]);
    $user1->assignRole('client');

    $user2 = \App\Models\User::create([
        'nom' => 'Sarr',
        'prenom' => 'Fatou',
        'telephone' => '771234567',
        'password' => bcrypt('default_password'),
        'statut' => 'actif',
        'langue' => 'français',
        'theme_sombre' => true,
        'scanner_actif' => true,
    ]);
    $user2->assignRole('client');

    $user3 = \App\Models\User::create([
        'nom' => 'Ndiaye',
        'prenom' => 'Moussa',
        'telephone' => '765432109',
        'password' => bcrypt('default_password'),
        'statut' => 'actif',
        'langue' => 'français',
        'theme_sombre' => false,
        'scanner_actif' => false,
    ]);
    $user3->assignRole('client');

    $user4 = \App\Models\User::create([
        'nom' => 'Ba',
        'prenom' => 'Aminata',
        'telephone' => '783456789',
        'password' => bcrypt('default_password'),
        'statut' => 'actif',
        'langue' => 'français',
        'theme_sombre' => true,
        'scanner_actif' => true,
    ]);
    $user4->assignRole('client');

    $user5 = \App\Models\User::create([
        'nom' => 'Gaye',
        'prenom' => 'Ibrahima',
        'telephone' => '789012345',
        'password' => bcrypt('default_password'),
        'statut' => 'actif',
        'langue' => 'français',
        'theme_sombre' => false,
        'scanner_actif' => true,
    ]);
    $user5->assignRole('client');


    $admin = \App\Models\User::create([
        'nom' => 'Admin',
        'prenom' => 'Orange',
        'telephone' => '700000000',
        'password' => bcrypt('admin_password'),
        'statut' => 'actif',
        'langue' => 'français',
        'theme_sombre' => false,
        'scanner_actif' => true,
    ]);
    $admin->assignRole('admin');
}

    // public function run(): void
    // {
    //     // Single Responsibility: Créer seulement les utilisateurs
    //     // Les comptes et profils seront créés automatiquement par les Observers

    //     $clientRole = \App\Models\Role::where('name', 'client')->first();
    //     $adminRole = \App\Models\Role::where('name', 'admin')->first();

    //     $user1 = \App\Models\User::create([
    //         'nom' => 'Diallo',
    //         'prenom' => 'Abdoulaye',
    //         'telephone' => '782917770',
    //         'statut' => 'actif',
    //         'langue' => 'français',
    //         'theme_sombre' => false,
    //         'scanner_actif' => true,
    //     ]);
    //     $user1->assignRole('client');

    //     $user2 = \App\Models\User::create([
    //         'nom' => 'Sarr',
    //         'prenom' => 'Fatou',
    //         'telephone' => '771234567',
    //         'statut' => 'actif',
    //         'langue' => 'français',
    //         'theme_sombre' => true,
    //         'scanner_actif' => true,
    //     ]);
    //     $user2->assignRole('client');

    //     $user3 = \App\Models\User::create([
    //         'nom' => 'Ndiaye',
    //         'prenom' => 'Moussa',
    //         'telephone' => '765432109',
    //         'statut' => 'actif',
    //         'langue' => 'français',
    //         'theme_sombre' => false,
    //         'scanner_actif' => false,
    //     ]);
    //     $user3->assignRole('client');

    //     $user4 = \App\Models\User::create([
    //         'nom' => 'Ba',
    //         'prenom' => 'Aminata',
    //         'telephone' => '783456789',
    //         'statut' => 'actif',
    //         'langue' => 'français',
    //         'theme_sombre' => true,
    //         'scanner_actif' => true,
    //     ]);
    //     $user4->assignRole('client');

    //     $user5 = \App\Models\User::create([
    //         'nom' => 'Gaye',
    //         'prenom' => 'Ibrahima',
    //         'telephone' => '789012345',
    //         'statut' => 'actif',
    //         'langue' => 'français',
    //         'theme_sombre' => false,
    //         'scanner_actif' => true,
    //     ]);
    //     $user5->assignRole('client');

    //     // Créer un admin pour les tests
    //     $admin = \App\Models\User::create([
    //         'nom' => 'Admin',
    //         'prenom' => 'Orange',
    //         'telephone' => '700000000',
    //         'statut' => 'actif',
    //         'langue' => 'français',
    //         'theme_sombre' => false,
    //         'scanner_actif' => true,
    //     ]);
    //     $admin->assignRole('admin');
    // }
}
