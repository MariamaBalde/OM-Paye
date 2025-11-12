<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CompteSeeder::class,
            ClientSeeder::class,
            MarchandSeeder::class,
            TransactionSeeder::class,
            VerificationCodeSeeder::class,
        ]);
    }
}
