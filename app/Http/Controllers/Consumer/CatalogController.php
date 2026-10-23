<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\DistributorStock;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Catalogue des produits finis disponibles à la vente chez les distributeurs
 * Accessible aux consommateurs (et aux visiteurs en lecture seule)
 */
class CatalogController extends Controller
{
    private const SORTS = [
        'price_asc' => ['unit_price', 'asc'],
        'price_desc' => ['unit_price', 'desc'],
        'name' => ['production_lot_id', 'asc'],
        'recent' => ['received_at', 'desc'],
        'quantity' => ['quantity_available', 'desc'],
    ];

    /**
     * Affiche le catalogue des produits finis disponibles
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'city', 'min_price', 'max_price', 'category', 'status']);

        [$column, $direction] = self::SORTS[$request->input('sort')] ?? self::SORTS['recent'];

        // Construire la requête avec les filtres
        $stocks = DistributorStock::query()
            ->withTraceability()
            ->when($filters['q'] ?? null, function (Builder $query, string $term) {
                $query->where(function (Builder $q) use ($term) {
                    $q->whereHas('productionLot.recipe', fn (Builder $r) => $r->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('pointOfSale', fn (Builder $p) => $p->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('pointOfSale.user', fn (Builder $u) => $u->where('company_name', 'like', "%{$term}%"));
                });
            })
            ->when($filters['city'] ?? null, fn (Builder $q, $v) => $q->whereHas('pointOfSale', fn (Builder $p) => $p->where('city', $v)))
            ->when($filters['min_price'] ?? null, fn (Builder $q, $v) => $q->where('unit_price', '>=', $v))
            ->when($filters['max_price'] ?? null, fn (Builder $q, $v) => $q->where('unit_price', '<=', $v))
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->available() // Uniquement les stocks disponibles
            ->orderBy($column, $direction)
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        // Statistiques pour le catalogue
        $stats = [
            'total_products' => DistributorStock::available()->count(),
            'total_distributors' => User::where('role', 'pro')
                ->where('pro_type', 'distributor')
                ->where('status', 'approved')
                ->whereHas('pointsOfSale.stocks')
                ->count(),
            'total_points_of_sale' => DistributorStock::available()
                ->distinct('point_of_sale_id')
                ->count('point_of_sale_id'),
            'avg_price' => DistributorStock::available()
                ->whereNotNull('unit_price')
                ->avg('unit_price'),
        ];

        // Liste des villes disponibles (pour le filtre)
        $cities = DistributorStock::available()
            ->whereHas('pointOfSale')
            ->with('pointOfSale')
            ->get()
            ->pluck('pointOfSale.city')
            ->unique()
            ->sort()
            ->filter()
            ->values();

        return view('consumer.catalog.index', compact('stocks', 'filters', 'stats', 'cities'));
    }

    /**
     * Affiche le détail d'un produit avec sa traçabilité complète
     */
    public function show(DistributorStock $stock): View
    {
        $stock->load([
            'productionLot.recipe',
            'productionLot.processor',
            'productionLot.recipe.items',
            'pointOfSale.user',
        ]);

        return view('consumer.catalog.show', compact('stock'));
    }
}
