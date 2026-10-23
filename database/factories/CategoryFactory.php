<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['Légumes', 'Fruits', 'Céréales', 'Olives', 'Dattes', 'Agrumes', 'Légumineuses', 'Plantes aromatiques']);
        $uniqueSuffix = fake()->unique()->randomNumber(3);
        $slug = strtolower(str_replace(' ', '-', $name)) . '-' . $uniqueSuffix;
        
        return [
            'name' => $name . ' ' . $uniqueSuffix,
            'slug' => $slug,
            'description' => fake()->sentence(),
            'icon' => fake()->randomElement(['fa-carrot', 'fa-apple-whole', 'fa-lemon', 'fa-wheat-awn', 'fa-seedling']),
            'color' => fake()->hexColor(),
            'is_active' => true,
        ];
    }
}
