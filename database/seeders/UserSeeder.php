<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin User
        User::firstOrCreate(
            ['email' => 'admin@test.tn'],
            [
                'name' => 'Admin Farm to Fork',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        // Consumer User
        User::firstOrCreate(
            ['email' => 'consumer@test.tn'],
            [
                'name' => 'Consumer Test',
                'password' => Hash::make('password123'),
                'role' => 'consumer',
                'status' => 'active',
            ]
        );

        // Professional User (Pending)
        User::firstOrCreate(
            ['email' => 'pro@test.tn'],
            [
                'name' => 'Producteur Test',
                'password' => Hash::make('password123'),
                'role' => 'pro',
                'pro_type' => 'producer',
                'company_name' => 'Ferme du Soleil',
                'tax_id' => '12345678901',
                'status' => 'pending',
            ]
        );

        // Professional User (Approved)
        User::firstOrCreate(
            ['email' => 'pro-approved@test.tn'],
            [
                'name' => 'Distributeur Test',
                'password' => Hash::make('password123'),
                'role' => 'pro',
                'pro_type' => 'distributor',
                'company_name' => 'Distribution Fresh',
                'tax_id' => '98765432109',
                'status' => 'approved',
            ]
        );
    }
}

