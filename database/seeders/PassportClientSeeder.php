<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Client;

class PassportClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Supprimer les clients existants pour éviter les conflits
        Client::where('personal_access_client', 1)->delete();

        // Créer un client d'accès personnel avec ID 1
        Client::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Personal Access Client',
                'secret' => '4FDQ1LMUAMo1OwjNU2r8gwSRn8Jow2ww1827kNee',
                'redirect' => 'http://localhost',
                'personal_access_client' => 1,
                'password_client' => 0,
                'revoked' => 0,
            ]
        );
    }
}