<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecipeItemFactory extends Factory
{
    protected $model = RecipeItem::class;

    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'raw_material_name' => fake()->randomElement(['Tomate', 'Fraise', 'Orange', 'Carotte', 'Oignon', 'Ail']),
            'quantity_required' => fake()->numberBetween(1, 10),
            'unit' => fake()->randomElement(['kg', 'g', 'L', 'unité']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
