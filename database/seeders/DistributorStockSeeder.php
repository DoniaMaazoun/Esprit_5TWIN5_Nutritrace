<?php

namespace Database\Seeders;

use App\Models\DistributorStock;
use App\Models\PointOfSale;
use App\Models\ProductionLot;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DistributorStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Crée des données de test pour le catalogue consommateur:
     * - 1 distributeur approuvé avec 2 points de vente
     * - 1 transformateur approuvé avec 1 recette et 2 lots de production
     * - 4 stocks distributeur (2 par point de vente)
     */
    public function run(): void
    {
        // Créer un transformateur si n'existe pas
        $processor = User::firstOrCreate(
            ['email' => 'transformateur@usine.com'],
            [
                'name' => 'Usine de Transformation Tunisie',
                'password' => Hash::make('password123'),
                'role' => 'pro',
                'pro_type' => 'processor',
                'company_name' => 'Transformateur SA',
                'tax_id' => 'PROC-001',
                'status' => 'approved',
            ]
        );

        // Créer une recette
        $recipe = Recipe::firstOrCreate(
            ['processor_id' => $processor->id, 'name' => 'Concentré de Tomates Premium'],
            [
                'description' => 'Concentré de tomates 100% naturel, produit à partir de tomates fraîches sélectionnées.',
                'yield_quantity' => 100,
                'yield_unit' => 'kg',
                'preparation_time_minutes' => 120,
                'instructions' => 'Lavage, triage, cuisson, concentration, mise en boîte.',
                'is_active' => true,
            ]
        );

        // Créer 2 lots de production
        $lot1 = ProductionLot::firstOrCreate(
            [
                'recipe_id' => $recipe->id,
                'processor_id' => $processor->id,
                'production_date' => now()->subDays(10),
                'qr_code_data' => 'LOT-PROC-001',
            ],
            [
                'quantity_produced' => 500,
                'unit' => 'kg',
                'expiry_date' => now()->addMonths(12),
                'status' => 'packaged',
                'production_notes' => 'Lot de haute qualité, tomates récoltées à maturité optimale.',
            ]
        );

        $lot2 = ProductionLot::firstOrCreate(
            [
                'recipe_id' => $recipe->id,
                'processor_id' => $processor->id,
                'production_date' => now()->subDays(5),
                'qr_code_data' => 'LOT-PROC-002',
            ],
            [
                'quantity_produced' => 750,
                'unit' => 'kg',
                'expiry_date' => now()->addMonths(12),
                'status' => 'packaged',
                'production_notes' => 'Lot standard, production régulière.',
            ]
        );

        // Créer un distributeur si n'existe pas
        $distributor = User::firstOrCreate(
            ['email' => 'distributeur@logistique.com'],
            [
                'name' => 'Logistique Distribution',
                'password' => Hash::make('password123'),
                'role' => 'pro',
                'pro_type' => 'distributor',
                'company_name' => 'Distributeur National',
                'tax_id' => 'DIST-001',
                'status' => 'approved',
            ]
        );

        // Créer 2 points de vente
        $pos1 = PointOfSale::firstOrCreate(
            ['user_id' => $distributor->id, 'name' => 'Supermarché Tunis Centre'],
            [
                'type' => 'store',
                'address' => '123 Avenue Habib Bourguiba',
                'city' => 'Tunis',
                'latitude' => 36.8065,
                'longitude' => 10.1815,
            ]
        );

        $pos2 = PointOfSale::firstOrCreate(
            ['user_id' => $distributor->id, 'name' => 'Entrepôt Sfax'],
            [
                'type' => 'warehouse',
                'address' => '45 Zone Industrielle',
                'city' => 'Sfax',
                'latitude' => 34.7406,
                'longitude' => 10.7603,
            ]
        );

        // Créer des stocks pour chaque point de vente
        // POS 1 - Tunis
        DistributorStock::firstOrCreate(
            ['point_of_sale_id' => $pos1->id, 'production_lot_id' => $lot1->id],
            [
                'quantity_available' => 200,
                'unit_price' => 5.50,
                'status' => 'available',
                'received_at' => now()->subDays(8),
            ]
        );

        DistributorStock::firstOrCreate(
            ['point_of_sale_id' => $pos1->id, 'production_lot_id' => $lot2->id],
            [
                'quantity_available' => 300,
                'unit_price' => 5.75,
                'status' => 'available',
                'received_at' => now()->subDays(3),
            ]
        );

        // POS 2 - Sfax
        DistributorStock::firstOrCreate(
            ['point_of_sale_id' => $pos2->id, 'production_lot_id' => $lot1->id],
            [
                'quantity_available' => 150,
                'unit_price' => 5.30,
                'status' => 'available',
                'received_at' => now()->subDays(7),
            ]
        );

        DistributorStock::firstOrCreate(
            ['point_of_sale_id' => $pos2->id, 'production_lot_id' => $lot2->id],
            [
                'quantity_available' => 400,
                'unit_price' => 5.60,
                'status' => 'available',
                'received_at' => now()->subDays(2),
            ]
        );

        $this->command->info('✅ Données de test du catalogue créées avec succès:');
        $this->command->info('   - 1 transformateur avec 1 recette et 2 lots de production');
        $this->command->info('   - 1 distributeur avec 2 points de vente');
        $this->command->info('   - 4 stocks distributeur créés');
    }
}
