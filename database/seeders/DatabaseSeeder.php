<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User; // Ajouter cet import


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::withoutEvents(function () {
              $this->call( [
             RolePermissionSeeder::class,
             UserSeeder::class,
             CompteSeeder::class,
             ClientSeeder::class,
             MarchandSeeder::class,
             TransactionSeeder::class,
             VerificationCodeSeeder::class,
             PassportClientSeeder::class,
         ]);
        });

    }
}
