<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecipeFactory extends Factory
{
    protected $model = Recipe::class;

    public function definition(): array
    {
        return [
            'processor_id' => User::factory()->create(['role' => 'pro', 'pro_type' => 'processor', 'status' => 'approved']),
            'name' => fake()->randomElement(['Sauce Tomate', 'Confiture Fraise', 'Jus Orange', 'Soupe Légumes']),
            'description' => fake()->paragraph(),
            'yield_quantity' => fake()->numberBetween(50, 200),
            'yield_unit' => 'kg',
            'preparation_time_minutes' => fake()->numberBetween(30, 180),
            'instructions' => fake()->paragraphs(3, true),
            'is_active' => true,
        ];
    }
}
