<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FarmFactory extends Factory
{
    protected $model = Farm::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => 'pro', 'pro_type' => 'producer', 'status' => 'approved']),
            'name' => fake()->company() . ' Farm',
            'description' => fake()->paragraph(),
            'governorate' => fake()->randomElement(['Ariana', 'Béja', 'Ben Arous', 'Bizerte', 'Gabès', 'Gafsa', 'Jendouba', 'Kairouan', 'Kasserine', 'Kébili', 'Le Kef', 'Mahdia', 'La Manouba', 'Médenine', 'Monastir', 'Nabeul', 'Sfax', 'Sidi Bouzid', 'Siliana', 'Sousse', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan']),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'total_area' => fake()->numberBetween(1, 50),
            'farming_type' => fake()->randomElement(['organic', 'conventional', 'reasoned', 'permaculture', 'agroforestry']),
            'irrigation_type' => fake()->randomElement(['drip', 'sprinkler', 'gravity', 'rainfed', 'mixed']),
            'soil_type' => fake()->randomElement(['clay', 'sandy', 'loamy', 'limestone', 'silty']),
            'main_crops' => fake()->words(3, true),
            'certification' => fake()->optional()->randomElement(['Bio Tunisie', 'Ecocert', 'CNAB']),
            'established_year' => fake()->numberBetween(1980, 2020),
            'status' => 'active',
        ];
    }
}
