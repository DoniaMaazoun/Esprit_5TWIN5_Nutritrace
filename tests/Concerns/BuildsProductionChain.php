<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Farm;
use App\Models\Harvest;
use App\Models\ProductionLot;
use App\Models\RawMaterialReception;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Module 3 - Chaîne Producteur → Transformateur utilisée par les tests du distributeur :
 * récolte (module 1), réception de la matière première, recette et lot de production (module 2).
 */
trait BuildsProductionChain
{
    /**
     * @param  string  $product  nom de la recette (produit fini)
     * @param  string  $ingredient  ingrédient de la recette, et nom de la récolte d'origine
     * @param  array  $lot  attributs du lot (production_date, expiry_date...)
     * @param  bool  $withReception  false : la récolte d'origine ne peut pas être retrouvée
     */
    protected function productionLot(
        string $product = 'Sauce Tomate Bio',
        string $ingredient = 'Tomates Bio',
        array $lot = [],
        array $farm = [],
        ?string $categorySlug = null,
        bool $withReception = true,
        array $processor = [],
    ): ProductionLot {
        $producedOn = Carbon::parse($lot['production_date'] ?? now()->subDay());

        $producer = User::factory()->create(['role' => 'pro', 'pro_type' => 'producer', 'status' => 'approved']);
        $farm = Farm::forceCreate($farm + [
            'user_id' => $producer->id, 'name' => 'Ferme '.uniqid(), 'governorate' => 'Sousse', 'city' => 'Sousse',
            'total_area' => 10, 'farming_type' => 'organic', 'irrigation_type' => 'drip',
        ]);
        $harvest = Harvest::create([
            'farm_id' => $farm->id, 'product_name' => $ingredient,
            'category_id' => $categorySlug ? Category::where('slug', $categorySlug)->value('id') : null,
            'harvest_date' => $producedOn->copy()->subDays(3), 'quantity' => 2, 'unit' => 'tonne', 'quality_grade' => 'A',
        ]);

        $processor = User::factory()->create($processor + [
            'role' => 'pro', 'pro_type' => 'processor', 'company_name' => 'Usine Test', 'status' => 'approved',
        ]);
        $recipe = Recipe::create([
            'processor_id' => $processor->id, 'name' => $product, 'yield_quantity' => 1, 'yield_unit' => 'kg',
        ]);
        RecipeItem::create(['recipe_id' => $recipe->id, 'raw_material_name' => $ingredient, 'quantity_required' => 2, 'unit' => 'kg']);

        if ($withReception) {
            RawMaterialReception::create([
                'harvest_id' => $harvest->id, 'processor_id' => $processor->id, 'quantity_received' => 500, 'unit' => 'kg',
                'reception_date' => $producedOn->copy()->subDay(), 'status' => 'received',
            ]);
        }

        return ProductionLot::create($lot + [
            'recipe_id' => $recipe->id,
            'processor_id' => $processor->id,
            'quantity_produced' => 100,
            'unit' => 'kg',
            'production_date' => $producedOn->toDateString(),
            'expiry_date' => $producedOn->copy()->addMonths(6)->toDateString(),
            'status' => 'packaged',
            'qr_code_data' => 'LOT-'.$processor->id.'-'.uniqid(),
        ]);
    }
}
