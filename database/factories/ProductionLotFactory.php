<?php

namespace Database\Factories;

use App\Models\ProductionLot;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductionLotFactory extends Factory
{
    protected $model = ProductionLot::class;

    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'processor_id' => User::factory()->create(['role' => 'pro', 'pro_type' => 'processor', 'status' => 'approved']),
            'quantity_produced' => fake()->numberBetween(50, 500),
            'unit' => fake()->randomElement(['kg', 'L', 'unité']),
            'production_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'expiry_date' => fake()->dateTimeBetween('now', '+1 year'),
            'status' => fake()->randomElement(['produced', 'packaged', 'shipped', 'archived']),
            'qr_code_data' => fake()->optional()->uuid(),
            'production_notes' => fake()->optional()->paragraph(),
        ];
    }
}
