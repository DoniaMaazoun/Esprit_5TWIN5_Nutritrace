<?php

namespace Database\Factories;

use App\Models\PointOfSale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointOfSaleFactory extends Factory
{
    protected $model = PointOfSale::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => 'pro', 'pro_type' => 'distributor', 'status' => 'approved']),
            'name' => fake()->company() . ' Store',
            'type' => fake()->randomElement(['store', 'warehouse', 'market']),
            'address' => fake()->address(),
            'city' => fake()->randomElement(['Tunis', 'Sfax', 'Sousse', 'Bizerte', 'Nabeul', 'Gabès', 'Ariana']),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ];
    }
}
