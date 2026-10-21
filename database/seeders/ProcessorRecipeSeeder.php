<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProcessorRecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer le compte processeur de test
        $processor = User::where('email', 'processor@usine.com')->first();

        if (!$processor) {
            echo "❌ Processor not found. Please seed ProcessorSeeder first.\n";
            return;
        }

        echo "\n✅ Creating demo recipes for processor: {$processor->name}\n";

        // Recipe 1: Sauce Tomate Bio
        $recipe1 = Recipe::create([
            'processor_id' => $processor->id,
            'name' => 'Sauce Tomate Bio 1L',
            'description' => 'Sauce tomate biologique pure, sans additifs, idéale pour les pâtes et pizzas.',
            'yield_quantity' => 1,
            'yield_unit' => 'L',
            'preparation_time_minutes' => 45,
            'instructions' => <<<'INSTRUCTIONS'
1. Sélectionner les tomates bio mûres
2. Laver et couper les tomates en quartiers
3. Cuire à feu moyen dans une casserole pendant 30 minutes
4. Passer au mixeur pour obtenir une texture lisse
5. Laisser réduire pendant 10 minutes
6. Mettre en bocaux stérilisés
INSTRUCTIONS,
            'is_active' => true,
        ]);

        // Ingrédients pour Sauce Tomate
        RecipeItem::create([
            'recipe_id' => $recipe1->id,
            'raw_material_name' => 'Tomates Bio Provence',
            'quantity_required' => 2,
            'unit' => 'kg',
            'notes' => 'Sélectionner les tomates mûres et fermes',
        ]);

        RecipeItem::create([
            'recipe_id' => $recipe1->id,
            'raw_material_name' => 'Huile d\'olive',
            'quantity_required' => 0.05,
            'unit' => 'L',
            'notes' => 'Huile d\'olive vierge extra',
        ]);

        RecipeItem::create([
            'recipe_id' => $recipe1->id,
            'raw_material_name' => 'Sel marin',
            'quantity_required' => 0.01,
            'unit' => 'kg',
            'notes' => 'Au goût',
        ]);

        echo "✅ Recipe 1 created: {$recipe1->name}\n";
        echo "   - Rendement: {$recipe1->getYieldDisplay()}\n";
        echo "   - Durée: {$recipe1->preparation_time_minutes} min\n";
        echo "   - Ingrédients: {$recipe1->itemsCount()}\n";

        // Recipe 2: Purée de Pommes de Terre
        $recipe2 = Recipe::create([
            'processor_id' => $processor->id,
            'name' => 'Purée de Pommes de Terre Premium',
            'description' => 'Purée lisse et crémeuse, préparée à partir de pommes de terre Charlotte biologiques.',
            'yield_quantity' => 2,
            'yield_unit' => 'kg',
            'preparation_time_minutes' => 30,
            'instructions' => <<<'INSTRUCTIONS'
1. Éplucher et découper les pommes de terre en cubes
2. Cuire à l'eau bouillante salée pendant 20 minutes
3. Égoutter et laisser sécher à la vapeur
4. Écraser à l'aide d'un presse-purée
5. Ajouter le beurre et le lait tiède progressivement
6. Mélanger jusqu'à obtenir la consistance désirée
7. Ajuster l'assaisonnement
8. Mettre en contenants réfrigérés
INSTRUCTIONS,
            'is_active' => true,
        ]);

        // Ingrédients pour Purée
        RecipeItem::create([
            'recipe_id' => $recipe2->id,
            'raw_material_name' => 'Pommes de terre Charlotte',
            'quantity_required' => 2.5,
            'unit' => 'kg',
            'notes' => 'Préférer les pommes de terre farineuses',
        ]);

        RecipeItem::create([
            'recipe_id' => $recipe2->id,
            'raw_material_name' => 'Beurre fermier',
            'quantity_required' => 0.2,
            'unit' => 'kg',
            'notes' => 'Beurre demi-sel de qualité',
        ]);

        RecipeItem::create([
            'recipe_id' => $recipe2->id,
            'raw_material_name' => 'Lait entier',
            'quantity_required' => 0.3,
            'unit' => 'L',
            'notes' => 'Lait tiède',
        ]);

        RecipeItem::create([
            'recipe_id' => $recipe2->id,
            'raw_material_name' => 'Sel',
            'quantity_required' => 0.015,
            'unit' => 'kg',
            'notes' => 'Au goût',
        ]);

        RecipeItem::create([
            'recipe_id' => $recipe2->id,
            'raw_material_name' => 'Poivre',
            'quantity_required' => 0.005,
            'unit' => 'kg',
            'notes' => 'Au goût',
        ]);

        echo "✅ Recipe 2 created: {$recipe2->name}\n";
        echo "   - Rendement: {$recipe2->getYieldDisplay()}\n";
        echo "   - Durée: {$recipe2->preparation_time_minutes} min\n";
        echo "   - Ingrédients: {$recipe2->itemsCount()}\n";

        echo "\n📊 Recipes Summary:\n";
        echo "   - Total recipes: " . Recipe::where('processor_id', $processor->id)->count() . "\n";
        echo "   - Total ingredients: " . RecipeItem::whereIn('recipe_id', 
            Recipe::where('processor_id', $processor->id)->pluck('id')
        )->count() . "\n";
        echo "\n✅ All recipe demo data seeded successfully!\n";
    }
}
