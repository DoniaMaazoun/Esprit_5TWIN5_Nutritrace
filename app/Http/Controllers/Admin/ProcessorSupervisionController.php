<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductionLot;
use App\Models\RawMaterialReception;
use App\Models\Recipe;
use Illuminate\View\View;

class ProcessorSupervisionController extends Controller
{
    /**
     * Display comprehensive processor supervision dashboard
     * Shows all raw material receptions, recipes, and production lots
     */
    public function index(): View
    {
        // Get all raw material receptions
        $receptions = RawMaterialReception::with(['harvest.farm.user', 'processor'])
            ->latest('reception_date')
            ->paginate(15, ['*'], 'receptions_page');

        // Get all recipes
        $recipes = Recipe::with(['processor', 'items'])
            ->latest('created_at')
            ->paginate(15, ['*'], 'recipes_page');

        // Get all production lots
        $productionLots = ProductionLot::with(['recipe', 'processor'])
            ->latest('production_date')
            ->paginate(15, ['*'], 'production_page');

        // Calculate statistics
        $stats = [
            'total_receptions' => RawMaterialReception::count(),
            'total_received' => RawMaterialReception::where('status', 'received')->count(),
            'total_rejected' => RawMaterialReception::where('status', 'rejected')->count(),
            'total_pending' => RawMaterialReception::where('status', 'pending')->count(),
            
            'total_recipes' => Recipe::count(),
            'active_recipes' => Recipe::where('is_active', true)->count(),
            'inactive_recipes' => Recipe::where('is_active', false)->count(),
            
            'total_production_lots' => ProductionLot::count(),
            'total_quantity_produced' => ProductionLot::sum('quantity_produced'),
            'production_this_month' => ProductionLot::whereMonth('production_date', now()->month)
                ->whereYear('production_date', now()->year)
                ->count(),
        ];

        return view('backend.pages.processor.supervision', [
            'receptions' => $receptions,
            'recipes' => $recipes,
            'productionLots' => $productionLots,
            'stats' => $stats,
        ]);
    }
}
