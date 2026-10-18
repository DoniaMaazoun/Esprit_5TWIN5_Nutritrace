<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HarvestRequest;
use App\Models\Category;
use App\Models\Farm;
use App\Models\Harvest;
use App\Models\User;
use App\Services\ProductIntelligence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Back-office : contrôle de tous les lots (produits récoltés) de tous les producteurs.
 */
class HarvestManagementController extends Controller
{
    public function index(Request $request, ProductIntelligence $intel): View
    {
        $filters = $request->only(['q', 'user_id', 'farm_id', 'category', 'status', 'from', 'to', 'quality', 'photo', 'risk']);
        $view = $request->input('view') === 'grid' ? 'grid' : 'table';
        $sortByRisk = $request->input('sort') === 'risk';

        $query = Harvest::query()
            ->filter($filters)
            ->when($filters['user_id'] ?? null, fn (Builder $q, $v) => $q->whereHas('farm', fn (Builder $f) => $f->where('user_id', $v)))
            ->when($filters['quality'] ?? null, fn (Builder $q, $v) => $q->where('quality_grade', $v))
            ->when(($filters['photo'] ?? null) === 'with', fn (Builder $q) => $q->whereNotNull('image_path'))
            ->when(($filters['photo'] ?? null) === 'without', fn (Builder $q) => $q->whereNull('image_path'))
            ->with(['farm.user', 'category'])
            ->orderByDesc('harvest_date')
            ->orderByDesc('id');
        $perPage = $view === 'grid' ? 12 : 15;

        if (!empty($filters['risk']) || $sortByRisk) {
            // Le score est calculé en PHP : filtrage / tri sur la collection puis pagination manuelle
            $items = $query->get()->each(fn (Harvest $h) => $h->setAttribute('risk', $intel->risk($h)));
            if (!empty($filters['risk'])) {
                $items = $items->filter(fn (Harvest $h) => $h->risk['level'] === $filters['risk']);
            }
            if ($sortByRisk) {
                $items = $items->sortByDesc(fn (Harvest $h) => $h->risk['score']);
            }
            $page = LengthAwarePaginator::resolveCurrentPage();
            $harvests = new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, [
                'path' => $request->url(), 'query' => $request->query(),
            ]);
        } else {
            $harvests = $query->paginate($perPage)->withQueryString();
            $harvests->getCollection()->each(fn (Harvest $h) => $h->setAttribute('risk', $intel->risk($h)));
        }

        $all = Harvest::with(['farm', 'category'])->get();
        $stats = [
            'count' => $all->count(),
            'kg' => $all->sum(fn (Harvest $h) => $h->quantityInKg()),
            'in_stock' => $all->where('status', 'in_stock')->count(),
            'grade_a' => $all->count() ? round($all->where('quality_grade', 'A')->count() / $all->count() * 100) : 0,
            'without_photo' => $all->whereNull('image_path')->count(),
            'producers' => Farm::whereHas('harvests')->distinct('user_id')->count('user_id'),
            'high_risk' => $all->filter(fn (Harvest $h) => $intel->risk($h)['level'] === 'high')->count(),
        ];
        $byStatus = collect(Harvest::STATUSES)->map(fn ($label, $key) => $all->where('status', $key)->count());

        $producers = User::where('role', 'pro')->where('pro_type', 'producer')->orderBy('company_name')->get(['id', 'name', 'company_name']);
        $farms = Farm::orderBy('name')->get(['id', 'name']);
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('backend.pages.harvests.index', compact('harvests', 'filters', 'stats', 'byStatus', 'producers', 'farms', 'categories', 'view'));
    }

    public function edit(Harvest $harvest, ProductIntelligence $intel): View
    {
        $harvest->load(['farm.user', 'category']);
        // Le lot ne peut être rattaché qu'à une exploitation de son producteur
        $farms = Farm::where('user_id', $harvest->farm->user_id)->orderBy('name')->get();
        $categories = Category::forSelect($harvest->category_id);
        $risk = $intel->risk($harvest);
        $fresh = $intel->freshness($harvest);
        $price = $intel->price($harvest);

        return view('backend.pages.harvests.edit', compact('harvest', 'farms', 'categories', 'risk', 'fresh', 'price'));
    }

    /**
     * Explication du score de conformité (IA si configurée, sinon synthèse locale).
     */
    public function explainRisk(Harvest $harvest, ProductIntelligence $intel): JsonResponse
    {
        $harvest->load(['farm', 'category']);

        return response()->json($intel->explainRisk($harvest));
    }

    public function update(HarvestRequest $request, Harvest $harvest): RedirectResponse
    {
        $harvest->fill($request->harvestData());
        $harvest->syncImage($request->file('image'), $request->boolean('remove_image'));

        return redirect()->route('backend.products.index')
            ->with('success', "Le produit {$harvest->product_name} (lot {$harvest->batch_code}) a été mis à jour.");
    }

    public function updateStatus(Request $request, Harvest $harvest): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Harvest::STATUSES))]]);
        $harvest->update($data);

        return back()->with('success', "Lot {$harvest->batch_code} : {$harvest->statusLabel()}.");
    }

    public function destroy(Harvest $harvest): RedirectResponse
    {
        $code = $harvest->batch_code;
        $harvest->delete();

        return back()->with('success', "Le lot {$code} a été supprimé.");
    }
}
