<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les rôles
        $clientRole = Role::firstOrCreate([
            'name' => 'client'
        ], [
            'label' => 'Client',
            'description' => 'Utilisateur standard Orange Money'
        ]);

        $adminRole = Role::firstOrCreate([
            'name' => 'admin'
        ], [
            'label' => 'Administrateur',
            'description' => 'Administrateur système'
        ]);

        $marchandRole = Role::firstOrCreate([
            'name' => 'marchand'
        ], [
            'label' => 'Marchand',
            'description' => 'Compte professionnel marchand'
        ]);

        // Créer les permissions
        $permissions = [
            // Permissions Client
            ['name' => 'view-own-profile', 'label' => 'Voir son profil', 'description' => 'Consulter ses propres informations'],
            ['name' => 'manage-own-account', 'label' => 'Gérer son compte', 'description' => 'Modifier ses paramètres personnels'],
            ['name' => 'transfer-money', 'label' => 'Transférer de l\'argent', 'description' => 'Effectuer des transferts'],
            ['name' => 'pay-merchant', 'label' => 'Payer un marchand', 'description' => 'Effectuer des paiements marchands'],
            ['name' => 'deposit-money', 'label' => 'Déposer de l\'argent', 'description' => 'Effectuer des dépôts'],
            ['name' => 'withdraw-money', 'label' => 'Retirer de l\'argent', 'description' => 'Effectuer des retraits'],
            ['name' => 'view-own-history', 'label' => 'Voir son historique', 'description' => 'Consulter ses transactions'],
            ['name' => 'manage-contacts', 'label' => 'Gérer les contacts', 'description' => 'Ajouter/modifier des contacts'],

            // Permissions Marchand
            ['name' => 'receive-payments', 'label' => 'Recevoir des paiements', 'description' => 'Accepter les paiements clients'],
            ['name' => 'view-received-transactions', 'label' => 'Voir les paiements reçus', 'description' => 'Consulter les transactions reçues'],
            ['name' => 'manage-merchant-profile', 'label' => 'Gérer le profil marchand', 'description' => 'Modifier les informations commerciales'],
            ['name' => 'generate-qr-codes', 'label' => 'Générer QR codes', 'description' => 'Créer des codes QR de paiement'],

            // Permissions Admin
            ['name' => 'manage-all-users', 'label' => 'Gérer tous les utilisateurs', 'description' => 'CRUD complet sur les utilisateurs'],
            ['name' => 'create-user-accounts', 'label' => 'Créer des comptes utilisateurs', 'description' => 'Créer de nouveaux comptes client'],
            ['name' => 'view-all-transactions', 'label' => 'Voir toutes les transactions', 'description' => 'Accès à toutes les transactions'],
            ['name' => 'manage-merchants', 'label' => 'Gérer les marchands', 'description' => 'Administrer les comptes marchands'],
            ['name' => 'system-administration', 'label' => 'Administration système', 'description' => 'Accès aux fonctionnalités d\'administration'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate([
                'name' => $permissionData['name']
            ], $permissionData);
        }

        // Assigner les permissions aux rôles

        // Permissions du Client
        $clientPermissions = [
            'view-own-profile',
            'manage-own-account',
            'transfer-money',
            'pay-merchant',
            'deposit-money',
            'withdraw-money',
            'view-own-history',
            'manage-contacts'
        ];

        foreach ($clientPermissions as $permName) {
            $permission = Permission::where('name', $permName)->first();
            if ($permission) {
                $clientRole->permissions()->syncWithoutDetaching($permission);
            }
        }

        // Permissions de l'Admin (hérite de toutes les permissions client + admin)
        $adminPermissions = array_merge($clientPermissions, [
            'manage-all-users',
            'create-user-accounts',
            'view-all-transactions',
            'manage-merchants',
            'system-administration'
        ]);

        foreach ($adminPermissions as $permName) {
            $permission = Permission::where('name', $permName)->first();
            if ($permission) {
                $adminRole->permissions()->syncWithoutDetaching($permission);
            }
        }

        // Permissions du Marchand
        $marchandPermissions = [
            'receive-payments',
            'view-received-transactions',
            'manage-merchant-profile',
            'generate-qr-codes'
        ];

        foreach ($marchandPermissions as $permName) {
            $permission = Permission::where('name', $permName)->first();
            if ($permission) {
                $marchandRole->permissions()->syncWithoutDetaching($permission);
            }
        }
    }
}
