<?php

namespace Database\Factories;

use App\Models\DistributorStock;
use App\Models\PointOfSale;
use App\Models\ProductionLot;
use Illuminate\Database\Eloquent\Factories\Factory;

class DistributorStockFactory extends Factory
{
    protected $model = DistributorStock::class;

    public function definition(): array
    {
        return [
            'point_of_sale_id' => PointOfSale::factory(),
            'production_lot_id' => ProductionLot::factory(),
            'quantity_available' => fake()->numberBetween(10, 100),
            'quantity_reserved' => fake()->numberBetween(0, 10),
            'unit_price' => fake()->numberBetween(5, 50),
            'status' => fake()->randomElement(['available', 'reserved', 'out_of_stock']),
            'received_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
