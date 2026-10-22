<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Farm;
use App\Models\Harvest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Fabriques simples pour les tests du Module Produit (exploitations, récoltes, catégories).
 * Les 8 catégories par défaut sont créées par la migration des catégories.
 */
trait BuildsModuleProduitData
{
    protected function makeUser(string $role = 'pro', array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Utilisateur '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@test.tn',
            'password' => Hash::make('password123'),
            'role' => $role,
            'status' => $role === 'pro' ? 'approved' : 'active',
            'pro_type' => $role === 'pro' ? 'producer' : null,
            'company_name' => $role === 'pro' ? 'Ferme '.Str::random(4) : null,
            'tax_id' => $role === 'pro' ? (string) random_int(10000000, 99999999) : null,
        ], $attributes));
    }

    protected function makeProducer(array $attributes = []): User
    {
        return $this->makeUser('pro', $attributes);
    }

    protected function makeAdmin(): User
    {
        return $this->makeUser('admin');
    }

    protected function makeFarm(User $owner, array $attributes = []): Farm
    {
        return $owner->farms()->create(array_merge([
            'name' => 'Ferme '.Str::random(6),
            'governorate' => 'Nabeul',
            'city' => 'Korba',
            'total_area' => 10,
            'farming_type' => 'conventional',
            'irrigation_type' => 'drip',
            'status' => 'active',
            'latitude' => 36.57,
            'longitude' => 10.86,
        ], $attributes));
    }

    protected function category(string $slug = 'legumes'): Category
    {
        return Category::where('slug', $slug)->firstOrFail();
    }

    protected function makeHarvest(Farm $farm, array $attributes = []): Harvest
    {
        return Harvest::create(array_merge([
            'farm_id' => $farm->id,
            'product_name' => 'Tomate',
            'category_id' => $this->category()->id,
            'harvest_date' => now()->subDay(),
            'quantity' => 100,
            'unit' => 'kg',
            'quality_grade' => 'A',
            'status' => 'in_stock',
        ], $attributes));
    }

    /**
     * Données valides pour le formulaire d'exploitation.
     */
    protected function farmPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Domaine des Tests',
            'governorate' => 'Sfax',
            'city' => 'Agareb',
            'total_area' => 12.5,
            'farming_type' => 'conventional',
            'irrigation_type' => 'drip',
            'status' => 'active',
        ], $overrides);
    }

    /**
     * Données valides pour le formulaire de récolte.
     */
    protected function harvestPayload(Farm $farm, array $overrides = []): array
    {
        return array_merge([
            'farm_id' => $farm->id,
            'product_name' => 'Piment',
            'category_id' => $this->category()->id,
            'harvest_date' => now()->subDays(2)->format('Y-m-d'),
            'quantity' => 250,
            'unit' => 'kg',
            'quality_grade' => 'B',
            'status' => 'in_stock',
        ], $overrides);
    }
}
