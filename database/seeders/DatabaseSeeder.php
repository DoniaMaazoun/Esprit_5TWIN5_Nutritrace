<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the UserSeeder
        $this->call(UserSeeder::class);

        // Module 1 (Producteur) : compte producteur + exploitations & récoltes de démo
        $this->call(FarmSeeder::class);

        // Module 3 - Distributeur (après le module 1 : les expéditions sont liées aux récoltes)
        $this->call(TransportModeSeeder::class);
        $this->call(DistributorDemoSeeder::class);
    }
}
