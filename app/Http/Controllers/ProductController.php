<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Farm;
use App\Models\Harvest;
use App\Services\ProductIntelligence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Catalogue public des produits : les lots récoltés par tous les producteurs.
 * Chacun peut consulter ; seuls le propriétaire et l'admin voient les actions de gestion.
 */
class ProductController extends Controller
{
    private const SORTS = [
        'recent' => ['harvest_date', 'desc'],
        'oldest' => ['harvest_date', 'asc'],
        'name' => ['product_name', 'asc'],
        'quantity' => ['quantity', 'desc'],
    ];

    public function index(Request $request, ProductIntelligence $intel): View
    {
        $filters = $request->only(['q', 'category', 'governorate', 'farming_type', 'availability', 'mine', 'quality', 'fresh']);

        // Recherche en langage naturel : « tomates bio de Nabeul en stock » → filtres
        $ask = trim((string) $request->input('ask'));
        $understood = [];
        if ($ask !== '') {
            $request->validate(['ask' => ['string', 'max:150']], ['ask.max' => 'Votre recherche ne doit pas dépasser 150 caractères.']);
            $parsed = $intel->parseSearch($ask);
            $filters = array_merge($filters, $parsed['filters']);
            $understood = $parsed['chips'];
        }
        [$column, $direction] = self::SORTS[$request->input('sort')] ?? self::SORTS['recent'];
        $user = $request->user();

        $products = $this->visible()
            ->with(['farm.user', 'category'])
            ->when($filters['q'] ?? null, fn (Builder $q, $term) => $q->where(fn (Builder $w) => $w
                ->where('product_name', 'like', "%{$term}%")
                ->orWhere('variety', 'like', "%{$term}%")
                ->orWhere('batch_code', 'like', "%{$term}%")
                ->orWhereHas('farm', fn (Builder $f) => $f->where('name', 'like', "%{$term}%"))))
            ->when($filters['category'] ?? null, fn (Builder $q, $v) => $q->where('category_id', $v))
            ->when($filters['governorate'] ?? null, fn (Builder $q, $v) => $q->whereHas('farm', fn (Builder $f) => $f->where('governorate', $v)))
            ->when($filters['farming_type'] ?? null, fn (Builder $q, $v) => $q->whereHas('farm', fn (Builder $f) => $f->where('farming_type', $v)))
            ->when(($filters['availability'] ?? null) === 'in_stock', fn (Builder $q) => $q->where('status', 'in_stock'))
            ->when($filters['quality'] ?? null, fn (Builder $q, $v) => $q->where('quality_grade', $v))
            ->when(!empty($filters['fresh']), fn (Builder $q) => $q->whereDate('harvest_date', '>=', now()->subDays(30)))
            ->when(!empty($filters['mine']) && $user?->isProducer(), fn (Builder $q) => $q->ownedBy($user))
            ->orderBy($column, $direction)
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'products' => $this->visible()->count(),
            'producers' => Farm::whereHas('harvests')->whereHas('user', fn (Builder $u) => $u->where('status', 'approved'))->distinct('user_id')->count('user_id'),
            'farms' => Farm::whereHas('harvests')->count(),
            'in_stock' => $this->visible()->where('status', 'in_stock')->count(),
        ];

        // Catégories actives qui contiennent au moins un produit visible, avec leur nombre de produits
        $categories = Category::active()
            ->withCount(['harvests' => fn (Builder $q) => $q->whereHas('farm.user', fn (Builder $u) => $u->where('status', 'approved'))])
            ->orderBy('name')
            ->get()
            ->where('harvests_count', '>', 0);

        return view('frontend.pages.products', compact('products', 'filters', 'stats', 'categories', 'ask', 'understood'));
    }

    /**
     * Seuls les lots des producteurs approuvés apparaissent au catalogue.
     */
    private function visible(): Builder
    {
        return Harvest::query()->whereHas('farm.user', fn (Builder $u) => $u->where('status', 'approved'));
    }
}
