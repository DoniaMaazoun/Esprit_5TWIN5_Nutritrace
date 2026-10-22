<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\ProductionLot;
use App\Models\RawMaterialReception;
use App\Models\Recipe;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProcessorProductionLotController extends Controller
{
    use AuthorizesRequests;

    /**
     * Show list of production lots
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $search = $request->query('q');

        // Build query with search
        $query = ProductionLot::where('processor_id', $user->id)
            ->with('recipe');

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('recipe', function ($recipeQuery) use ($search) {
                    $recipeQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhere('production_notes', 'like', "%{$search}%")
                ->orWhereRaw('DATE_FORMAT(production_date, "%d/%m/%Y") LIKE ?', ["%{$search}%"])
                ->orWhereRaw('DATE_FORMAT(expiry_date, "%d/%m/%Y") LIKE ?', ["%{$search}%"]);
            });
        }

        // Get all production lots for this processor with pagination (4 per page)
        $productionLots = $query
            ->orderBy('production_date', 'desc')
            ->paginate(4)
            ->appends($request->query());

        // Get summary stats (based on filtered results if search is applied)
        $baseQuery = ProductionLot::where('processor_id', $user->id);
        if ($search) {
            $baseQuery->where(function ($q) use ($search) {
                $q->whereHas('recipe', function ($recipeQuery) use ($search) {
                    $recipeQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhere('production_notes', 'like', "%{$search}%")
                ->orWhereRaw('DATE_FORMAT(production_date, "%d/%m/%Y") LIKE ?', ["%{$search}%"])
                ->orWhereRaw('DATE_FORMAT(expiry_date, "%d/%m/%Y") LIKE ?', ["%{$search}%"]);
            });
        }

        $stats = [
            'total_lots' => $baseQuery->count(),
            'total_produced' => (clone $baseQuery)->where('status', 'produced')->count(),
            'close_to_expiry' => (clone $baseQuery)
                ->whereDate('expiry_date', '>', now())
                ->whereDate('expiry_date', '<=', now()->addDays(7))
                ->count(),
            'expired' => (clone $baseQuery)
                ->whereDate('expiry_date', '<', now())
                ->count(),
        ];

        return view('pro.processor.production-lots.index', [
            'productionLots' => $productionLots,
            'stats' => $stats,
            'search' => $search,
        ]);
    }

    /**
     * Show form to create new production lot
     */
    public function create(): View
    {
        $user = auth()->user();

        // Get all active recipes for this processor (with or without items - let validation handle it)
        $recipes = Recipe::where('processor_id', $user->id)
            ->where('is_active', true)
            ->with('items')
            ->get();

        // Get all in-stock raw materials for this processor
        $rawMaterials = RawMaterialReception::where('processor_id', $user->id)
            ->where('status', 'received')
            ->with('harvest.category')
            ->get();

        return view('pro.processor.production-lots.create', [
            'recipes' => $recipes,
            'rawMaterials' => $rawMaterials,
        ]);
    }

    /**
     * Store new production lot (with automatic stock deduction)
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'quantity_produced' => 'required|numeric|min:0.01',
            'production_date' => 'required|date|before_or_equal:today',
            'expiry_date' => 'required|date|after_or_equal:production_date',
            'production_notes' => 'nullable|string|max:500',
        ], [
            'recipe_id.required' => 'Vous devez choisir une recette',
            'quantity_produced.required' => 'La quantité produite est obligatoire',
            'production_date.required' => 'La date de production est obligatoire',
            'expiry_date.required' => 'La date de péremption est obligatoire',
        ]);

        // Get recipe with items
        $recipe = Recipe::with('items')->findOrFail($validated['recipe_id']);

        // Check if recipe has ingredients
        if ($recipe->items->isEmpty()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'recipe_id' => 'La recette sélectionnée n\'a pas d\'ingrédients. Veuillez ajouter des ingrédients à la recette avant de créer un lot.',
                ]);
        }

        // Calculate required quantities for ingredients
        $quantityRatio = $validated['quantity_produced'] / $recipe->getYieldInKg();

        try {
            // Deduct stock from raw materials
            foreach ($recipe->items as $item) {
                // Find the raw material reception that has this product
                // Try to find by product_name match (case-insensitive)
                $reception = RawMaterialReception::where('processor_id', auth()->id())
                    ->where('status', 'received')
                    ->whereHas('harvest', function ($q) use ($item) {
                        $q->whereRaw('LOWER(product_name) = ?', [strtolower($item->raw_material_name)]);
                    })
                    ->first();

                // If no reception found, show list of available materials for debugging
                if (!$reception) {
                    $availableMaterials = RawMaterialReception::where('processor_id', auth()->id())
                        ->where('status', 'received')
                        ->with('harvest')
                        ->get()
                        ->map(fn($r) => $r->harvest->product_name)
                        ->unique()
                        ->implode(', ');

                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors([
                            'quantity_produced' => "Aucune réception trouvée pour '{$item->raw_material_name}'. Matières disponibles: " . ($availableMaterials ?: 'aucune'),
                        ]);
                }

                // Calculate quantity to deduct
                $requiredQuantity = $item->quantity_required * $quantityRatio;

                // Convert to kg for comparison
                $units = [
                    'kg' => 1,
                    'g' => 0.001,
                    'quintal' => 100,
                    'tonne' => 1000,
                    'L' => 1,
                    'mL' => 0.001,
                ];
                $conversionFactor = $units[$item->unit] ?? 1;
                $requiredQuantityInKg = $requiredQuantity * $conversionFactor;

                // Check if enough stock available
                if ($reception->quantity_received < $requiredQuantityInKg) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors([
                            'quantity_produced' => "Stock insuffisant pour {$item->raw_material_name}. Nécessaire: {$requiredQuantityInKg} kg, Disponible: {$reception->quantity_received} kg",
                        ]);
                }

                // Deduct stock
                $reception->quantity_received -= $requiredQuantityInKg;
                $reception->save();
            }

            // Generate unique QR code data
            $qrCodeData = 'LOT-' . auth()->id() . '-' . time() . '-' . rand(1000, 9999);

            // Create production lot
            $lot = ProductionLot::create([
                'recipe_id' => $validated['recipe_id'],
                'processor_id' => auth()->id(),
                'quantity_produced' => $validated['quantity_produced'],
                'unit' => $recipe->yield_unit,
                'production_date' => $validated['production_date'],
                'expiry_date' => $validated['expiry_date'],
                'status' => 'produced',
                'qr_code_data' => $qrCodeData,
                'production_notes' => $validated['production_notes'],
            ]);

            return redirect()
                ->route('pro.processor.production-lots.show', $lot)
                ->with('success', 'Lot de production créé avec succès et stock déduit! ✅');
        } catch (\Exception $e) {
            // Log error
            \Log::error('Production lot creation error: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'error' => 'Erreur lors de la création du lot: ' . $e->getMessage(),
                ]);
        }
    }

    /**
     * Show production lot details with QR code
     */
    public function show(ProductionLot $production_lot): View
    {
        // Verify user owns this lot - log for debugging
        $userId = auth()->id();
        $lotProcessorId = $production_lot->processor_id;
        
        if ($lotProcessorId != $userId) {
            \Log::warning('Unauthorized show attempt', [
                'user_id' => $userId,
                'lot_processor_id' => $lotProcessorId,
                'lot_id' => $production_lot->id,
            ]);
            abort(403, 'Unauthorized');
        }

        return view('pro.processor.production-lots.show', [
            'lot' => $production_lot->load('recipe.items', 'processor'),
        ]);
    }

    /**
     * Show form to edit production lot
     */
    public function edit(ProductionLot $production_lot): View
    {
        // Verify user owns this lot
        $userId = auth()->id();
        $lotProcessorId = $production_lot->processor_id;
        
        if ($lotProcessorId != $userId) {
            abort(403, 'Unauthorized');
        }

        $user = auth()->user();
        $recipes = Recipe::where('processor_id', $user->id)
            ->where('is_active', true)
            ->with('items')
            ->get();

        return view('pro.processor.production-lots.edit', [
            'lot' => $production_lot->load('recipe'),
            'recipes' => $recipes,
        ]);
    }

    /**
     * Update production lot (notes and status only - no stock changes)
     */
    public function update(Request $request, ProductionLot $production_lot): RedirectResponse
    {
        // Verify user owns this lot
        if ($production_lot->processor_id != auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'status' => 'nullable|in:produced,packaged,shipped,archived',
            'production_notes' => 'nullable|string|max:500',
        ]);

        // Update only provided fields
        if (isset($validated['status']) && $validated['status'] !== null) {
            $production_lot->status = $validated['status'];
        }
        if (isset($validated['production_notes']) && $validated['production_notes'] !== null) {
            $production_lot->production_notes = $validated['production_notes'];
        }
        $production_lot->save();

        return redirect()
            ->route('pro.processor.production-lots.show', $production_lot)
            ->with('success', 'Lot de production mis à jour avec succès!');
    }

    /**
     * Delete production lot
     */
    public function destroy(ProductionLot $production_lot): RedirectResponse
    {
        // Verify user owns this lot
        if ($production_lot->processor_id != auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $production_lot->delete();

        return redirect()
            ->route('pro.processor.production-lots.index')
            ->with('success', 'Lot de production supprimé.');
    }

    /**
     * Generate QR code image for a production lot
     * 
     * Uses Google Charts API to generate QR code (no PHP extension needed)
     */
    public function generateQrCode(ProductionLot $production_lot)
    {
        // Verify user owns this lot
        if ($production_lot->processor_id != auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Generate QR code with lot details using Google Charts API
        $qrContent = route('traceability.show', ['code' => $production_lot->qr_code_data]);
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrContent);

        return redirect($qrUrl);
    }
}
