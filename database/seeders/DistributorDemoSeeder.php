<?php

namespace Database\Seeders;

use App\Models\Harvest;
use App\Models\PointOfSale;
use App\Models\ProductionLot;
use App\Models\RawMaterialReception;
use App\Models\Recipe;
use App\Models\Shipment;
use App\Models\TransportMode;
use App\Models\User;
use Illuminate\Database\Seeder;

class DistributorDemoSeeder extends Seeder
{
    /**
     * Module 3 - Données de démonstration pour le distributeur de test
     */
    public function run(): void
    {
        $distributor = User::where('email', 'pro-approved@test.tn')->first();

        if (! $distributor || $distributor->shipments()->exists()) {
            return;
        }

        $store = PointOfSale::create([
            'user_id' => $distributor->id,
            'name' => 'Épicerie Bio Lac 2',
            'type' => 'store',
            'address' => 'Rue du Lac Léman',
            'city' => 'Tunis',
            'latitude' => 36.8437,
            'longitude' => 10.2733,
        ]);

        PointOfSale::create([
            'user_id' => $distributor->id,
            'name' => 'Entrepôt Sfax',
            'type' => 'warehouse',
            'address' => 'Zone industrielle Poudrière',
            'city' => 'Sfax',
            'latitude' => 34.7406,
            'longitude' => 10.7603,
        ]);

        $lot = $this->demoProductionLot();

        $shipment = Shipment::create([
            'reference' => Shipment::nextReference(),
            'user_id' => $distributor->id,
            'production_lot_id' => $lot?->id,
            'product_name' => $lot?->getProductName() ?? 'Sauce Tomate Bio 1L',
            // 120 L de sauce en bocaux en verre
            'weight_kg' => 150,
            'origin_address' => 'Usine Transformation Provence, Sousse',
            'point_of_sale_id' => $store->id,
            'status' => 'delivered',
            'departed_at' => now()->subDays(1)->setTime(7, 0),
            'delivered_at' => now()->subDays(1)->setTime(16, 0),
        ]);

        $shipment->legs()->make([
            'transport_mode_id' => TransportMode::where('code', 'rail')->value('id'),
            'position' => 1,
            'from_location' => 'Gare de Sousse',
            'to_location' => 'Gare de Tunis',
            'to_latitude' => 36.7948,
            'to_longitude' => 10.1802,
            'distance_km' => 140,
            'weight_kg' => 150,
        ])->calculateCo2()->save();

        $shipment->legs()->make([
            'transport_mode_id' => TransportMode::where('code', 'van')->value('id'),
            'position' => 2,
            'from_location' => 'Gare de Tunis',
            'to_location' => 'Épicerie Bio Lac 2',
            'to_latitude' => 36.8437,
            'to_longitude' => 10.2733,
            'distance_km' => 12,
            'weight_kg' => 150,
        ])->calculateCo2()->save();

        $shipment->recalculateTotals();

        $shipment->events()->createMany([
            ['type' => 'loaded', 'location' => 'Usine Transformation Provence, Sousse', 'occurred_at' => now()->subDays(1)->setTime(7, 0)],
            ['type' => 'arrived', 'location' => 'Épicerie Bio Lac 2', 'occurred_at' => now()->subDays(1)->setTime(16, 0)],
        ]);
    }

    /**
     * Chaîne de démo Producteur → Transformateur : les seeders du module 2 créent les récoltes
     * (ProducerSeeder) et les recettes (ProcessorRecipeSeeder) mais aucune réception ni aucun lot.
     * On crée ici la réception des tomates et un lot de sauce tomate, sans modifier leurs seeders.
     */
    private function demoProductionLot(): ?ProductionLot
    {
        $recipe = Recipe::with('processor')->where('name', 'Sauce Tomate Bio 1L')->first();
        $harvest = Harvest::where('product_name', 'Tomates Bio Provence')->first();

        if (! $recipe) {
            return null;
        }

        if ($harvest) {
            RawMaterialReception::firstOrCreate(
                ['harvest_id' => $harvest->id, 'processor_id' => $recipe->processor_id],
                [
                    'quantity_received' => 300,
                    'unit' => 'kg',
                    'reception_date' => now()->subDays(2),
                    'status' => 'received',
                    'quality_status' => 'good',
                    'storage_location' => 'Chambre froide A',
                    'verified_at' => now()->subDays(2),
                ]
            );
        }

        return ProductionLot::firstOrCreate(
            ['qr_code_data' => "LOT-{$recipe->processor_id}-DEMO-SAUCE"],
            [
                'recipe_id' => $recipe->id,
                'processor_id' => $recipe->processor_id,
                'quantity_produced' => 120,
                'unit' => $recipe->yield_unit,
                'production_date' => now()->subDays(2)->toDateString(),
                'expiry_date' => now()->addMonths(6)->toDateString(),
                'status' => 'shipped',
                'production_notes' => 'Lot de démonstration expédié au distributeur (module 3).',
            ]
        );
    }
}
