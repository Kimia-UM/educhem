<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Memanggil seeder Role dan Admin
        $this->call([
            RoleAndAdminSeeder::class,
        ]);
    }
}