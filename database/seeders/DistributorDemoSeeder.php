<?php

namespace Database\Seeders;

use App\Models\PointOfSale;
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

        if (!$distributor || $distributor->shipments()->exists()) {
            return;
        }

        $store = PointOfSale::create([
            'user_id' => $distributor->id,
            'name' => 'Épicerie Bio Lac 2',
            'type' => 'store',
            'address' => 'Rue du Lac Léman',
            'city' => 'Tunis',
        ]);

        PointOfSale::create([
            'user_id' => $distributor->id,
            'name' => 'Entrepôt Sfax',
            'type' => 'warehouse',
            'address' => 'Zone industrielle Poudrière',
            'city' => 'Sfax',
        ]);

        $shipment = Shipment::create([
            'reference' => Shipment::nextReference(),
            'user_id' => $distributor->id,
            'product_name' => 'Huile d\'olive extra vierge',
            'weight_kg' => 1200,
            'origin_address' => 'Huilerie, Sfax',
            'point_of_sale_id' => $store->id,
            'status' => 'delivered',
            'departed_at' => now()->subDays(3),
            'delivered_at' => now()->subDays(2),
        ]);

        $shipment->legs()->make([
            'transport_mode_id' => TransportMode::where('code', 'rail')->value('id'),
            'position' => 1,
            'from_location' => 'Gare de Sfax',
            'to_location' => 'Gare de Tunis',
            'distance_km' => 270,
            'weight_kg' => 1200,
        ])->calculateCo2()->save();

        $shipment->legs()->make([
            'transport_mode_id' => TransportMode::where('code', 'van')->value('id'),
            'position' => 2,
            'from_location' => 'Gare de Tunis',
            'to_location' => 'Épicerie Bio Lac 2',
            'distance_km' => 12,
            'weight_kg' => 1200,
        ])->calculateCo2()->save();

        $shipment->recalculateTotals();

        $shipment->events()->createMany([
            ['type' => 'loaded', 'location' => 'Huilerie, Sfax', 'occurred_at' => now()->subDays(3)],
            ['type' => 'arrived', 'location' => 'Épicerie Bio Lac 2', 'occurred_at' => now()->subDays(2)],
        ]);
    }
}
