<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProcessorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a processor user (Transformateur)
        $processor = User::create([
            'name' => 'Usine de Transformation Provence',
            'email' => 'processor@usine.com',
            'password' => Hash::make('password123'),
            'role' => 'pro',
            'pro_type' => 'processor',
            'company_name' => 'Transformation Provence SARL',
            'tax_id' => 'FR98765432101',
            'status' => 'approved', // Already approved so can access raw materials
        ]);

        echo "\n✅ Processor created: {$processor->email}\n";
        echo "📊 Processor info:\n";
        echo "   - Company: {$processor->company_name}\n";
        echo "   - Role: Transformateur (Processor)\n";
        echo "   - Status: Approved\n";
        echo "\n✅ You can now test raw material reception!\n";
    }
}

