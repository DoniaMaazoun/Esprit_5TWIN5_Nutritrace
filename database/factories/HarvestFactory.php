<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\Harvest;
use Illuminate\Database\Eloquent\Factories\Factory;

class HarvestFactory extends Factory
{
    protected $model = Harvest::class;

    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'product_name' => fake()->randomElement(['Tomate', 'Pomme', 'Orange', 'Carotte', 'Courgette']),
            'variety' => fake()->optional()->randomElement(['Roma', 'Golden', 'Navel', 'Nantaise', 'Verte']),
            'plot_name' => fake()->word(),
            'plot_area' => fake()->numberBetween(0.5, 5),
            'planting_date' => fake()->optional()->dateTimeBetween('-6 months', '-3 months'),
            'harvest_date' => fake()->dateTimeBetween('-2 months', 'now'),
            'quantity' => fake()->numberBetween(50, 500),
            'unit' => fake()->randomElement(['kg', 'quintal', 'tonne']),
            'quality_grade' => fake()->randomElement(['A', 'B', 'C']),
            'treatments' => fake()->optional()->sentence(),
            'storage_conditions' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->paragraph(),
            'public_description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(['in_stock', 'shipped', 'processed', 'sold']),
        ];
    }
}
