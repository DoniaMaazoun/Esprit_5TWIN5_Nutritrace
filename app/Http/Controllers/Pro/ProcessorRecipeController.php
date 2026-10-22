<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ProcessorRecipeController extends Controller
{
    use AuthorizesRequests;

    /**
     * Affiche la liste des recettes du transformateur connecté
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        
        // Récupérer les paramètres de recherche et filtrage
        $search = $request->input('q', '');
        $sortBy = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $activeOnly = $request->input('active_only', '');
        
        // Construire la requête
        $query = Recipe::where('processor_id', $user->id)
            ->with('items', 'processor');
        
        // Appliquer la recherche
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Filtre actives seulement
        if ($activeOnly === '1') {
            $query->where('is_active', true);
        }
        
        // Trier
        $allowedSorts = ['name', 'created_at', 'yield_quantity'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        // Paginer
        $recipes = $query->paginate(4);
        
        // Statistiques
        $stats = [
            'total' => Recipe::where('processor_id', $user->id)->count(),
            'active' => Recipe::where('processor_id', $user->id)->where('is_active', true)->count(),
            'inactive' => Recipe::where('processor_id', $user->id)->where('is_active', false)->count(),
        ];
        
        return view('pro.processor.recipes.index', [
            'recipes' => $recipes,
            'stats' => $stats,
            'search' => $search,
        ]);
    }

    /**
     * Affiche le formulaire de création d'une recette
     */
    public function create(): View
    {
        // Récupérer les matières brutes reçues et approuvées par le processeur connecté
        $receivedMaterials = DB::table('raw_material_receptions')
            ->join('harvests', 'raw_material_receptions.harvest_id', '=', 'harvests.id')
            ->where('raw_material_receptions.processor_id', auth()->id())
            ->where('raw_material_receptions.status', 'received')
            ->select('harvests.product_name', 'harvests.id as harvest_id')
            ->distinct()
            ->orderBy('harvests.product_name')
            ->get();

        return view('pro.processor.recipes.create', [
            'receivedMaterials' => $receivedMaterials,
        ]);
    }

    /**
     * Sauvegarde une nouvelle recette et ses ingrédients
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'yield_quantity' => 'required|numeric|min:0.01',
            'yield_unit' => 'required|string|in:kg,g,quintal,tonne,L,mL,unités',
            'preparation_time_minutes' => 'required|integer|min:0',
            'instructions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.raw_material_name' => 'required|string|max:150',
            'items.*.quantity_required' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|in:kg,g,quintal,tonne,L,mL,unités',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Le nom de la recette est obligatoire',
            'yield_quantity.required' => 'La quantité produite est obligatoire',
            'yield_unit.required' => 'L\'unité de production est obligatoire',
            'items.required' => 'Au moins un ingrédient est obligatoire',
            'items.*.raw_material_name.required' => 'Le nom de l\'ingrédient est obligatoire',
            'items.*.quantity_required.required' => 'La quantité requise est obligatoire',
        ]);

        // Utiliser une transaction pour garantir l'intégrité
        DB::transaction(function () use ($validated) {
            $recipe = Recipe::create([
                'processor_id' => auth()->id(),
                'name' => $validated['name'],
                'description' => $validated['description'],
                'yield_quantity' => $validated['yield_quantity'],
                'yield_unit' => $validated['yield_unit'],
                'preparation_time_minutes' => $validated['preparation_time_minutes'],
                'instructions' => $validated['instructions'],
            ]);

            // Créer les ingrédients
            foreach ($validated['items'] as $item) {
                RecipeItem::create([
                    'recipe_id' => $recipe->id,
                    'raw_material_name' => $item['raw_material_name'],
                    'quantity_required' => $item['quantity_required'],
                    'unit' => $item['unit'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('pro.processor.recipes.index')
            ->with('success', 'Recette créée avec succès! ✅');
    }

    /**
     * Affiche les détails d'une recette avec ses ingrédients
     */
    public function show(Recipe $recipe): View
    {
        $this->authorize('view', $recipe);

        return view('pro.processor.recipes.show', [
            'recipe' => $recipe->load('items', 'processor'),
        ]);
    }

    /**
     * Affiche le formulaire d'édition d'une recette
     */
    public function edit(Recipe $recipe): View
    {
        $this->authorize('update', $recipe);

        // Récupérer les matières brutes reçues et approuvées par le processeur connecté
        $receivedMaterials = DB::table('raw_material_receptions')
            ->join('harvests', 'raw_material_receptions.harvest_id', '=', 'harvests.id')
            ->where('raw_material_receptions.processor_id', auth()->id())
            ->where('raw_material_receptions.status', 'received')
            ->select('harvests.product_name', 'harvests.id as harvest_id')
            ->distinct()
            ->orderBy('harvests.product_name')
            ->get();

        return view('pro.processor.recipes.edit', [
            'recipe' => $recipe->load('items'),
            'receivedMaterials' => $receivedMaterials,
        ]);
    }

    /**
     * Met à jour une recette et ses ingrédients
     */
    public function update(Request $request, Recipe $recipe): RedirectResponse
    {
        $this->authorize('update', $recipe);

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'yield_quantity' => 'nullable|numeric|min:0.01',
            'yield_unit' => 'nullable|string|in:kg,g,quintal,tonne,L,mL,unités',
            'preparation_time_minutes' => 'nullable|integer|min:0',
            'instructions' => 'nullable|string',
            'is_active' => 'nullable|in:0,1,on',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|exists:recipe_items,id',
            'items.*.raw_material_name' => 'nullable|string|max:150',
            'items.*.quantity_required' => 'nullable|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|in:kg,g,quintal,tonne,L,mL,unités',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($recipe, $validated) {
            // Mettre à jour seulement les champs fournis
            $updateData = [];
            if (isset($validated['name']) && $validated['name'] !== null) {
                $updateData['name'] = $validated['name'];
            }
            if (isset($validated['description']) && $validated['description'] !== null) {
                $updateData['description'] = $validated['description'];
            }
            if (isset($validated['yield_quantity']) && $validated['yield_quantity'] !== null) {
                $updateData['yield_quantity'] = $validated['yield_quantity'];
            }
            if (isset($validated['yield_unit']) && $validated['yield_unit'] !== null) {
                $updateData['yield_unit'] = $validated['yield_unit'];
            }
            if (isset($validated['preparation_time_minutes']) && $validated['preparation_time_minutes'] !== null) {
                $updateData['preparation_time_minutes'] = $validated['preparation_time_minutes'];
            }
            if (isset($validated['instructions']) && $validated['instructions'] !== null) {
                $updateData['instructions'] = $validated['instructions'];
            }
            if (isset($validated['is_active']) && $validated['is_active'] !== null) {
                $updateData['is_active'] = (bool)$validated['is_active'];
            }
            
            if (!empty($updateData)) {
                $recipe->update($updateData);
            }

            // Gérer les ingrédients seulement s'ils sont fournis
            if (isset($validated['items']) && is_array($validated['items']) && count($validated['items']) > 0) {
                $itemIdsToKeep = [];

                foreach ($validated['items'] as $item) {
                    if (isset($item['id']) && $item['id'] !== null) {
                        // Mettre à jour un ingrédient existant (seulement les champs fournis)
                        $itemUpdateData = [];
                        if (isset($item['raw_material_name']) && $item['raw_material_name'] !== null) {
                            $itemUpdateData['raw_material_name'] = $item['raw_material_name'];
                        }
                        if (isset($item['quantity_required']) && $item['quantity_required'] !== null) {
                            $itemUpdateData['quantity_required'] = $item['quantity_required'];
                        }
                        if (isset($item['unit']) && $item['unit'] !== null) {
                            $itemUpdateData['unit'] = $item['unit'];
                        }
                        if (isset($item['notes'])) {
                            $itemUpdateData['notes'] = $item['notes'];
                        }
                        
                        if (!empty($itemUpdateData)) {
                            RecipeItem::findOrFail($item['id'])->update($itemUpdateData);
                        }
                        $itemIdsToKeep[] = $item['id'];
                    } else {
                        // Créer un nouvel ingrédient
                        if (isset($item['raw_material_name']) && isset($item['quantity_required']) && isset($item['unit'])) {
                            $newItem = RecipeItem::create([
                                'recipe_id' => $recipe->id,
                                'raw_material_name' => $item['raw_material_name'],
                                'quantity_required' => $item['quantity_required'],
                                'unit' => $item['unit'],
                                'notes' => $item['notes'] ?? null,
                            ]);
                            $itemIdsToKeep[] = $newItem->id;
                        }
                    }
                }

                // Supprimer les ingrédients qui ne sont plus présents
                if (!empty($itemIdsToKeep)) {
                    RecipeItem::where('recipe_id', $recipe->id)
                        ->whereNotIn('id', $itemIdsToKeep)
                        ->delete();
                }
            }
        });

        return redirect()->route('pro.processor.recipes.show', $recipe)
            ->with('success', 'Recette mise à jour avec succès! ✅');
    }

    /**
     * Supprime une recette et ses ingrédients
     */
    public function destroy(Recipe $recipe): RedirectResponse
    {
        $this->authorize('delete', $recipe);

        $recipeName = $recipe->name;
        
        $recipe->delete(); // Les ingrédients sont supprimés en cascade

        return redirect()->route('pro.processor.recipes.index')
            ->with('success', "Recette '{$recipeName}' supprimée avec succès.");
    }
}
