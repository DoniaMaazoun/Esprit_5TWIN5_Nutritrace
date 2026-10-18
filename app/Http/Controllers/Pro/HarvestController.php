<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Http\Requests\HarvestRequest;
use App\Models\Category;
use App\Models\Harvest;
use App\Services\ProductIntelligence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Module 1 (Producteur) : origine des récoltes (lots traçables).
 */
class HarvestController extends Controller
{
    private const FILTERS = ['q', 'farm_id', 'category', 'status', 'from', 'to'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $filters = $request->only(self::FILTERS);

        $harvests = Harvest::ownedBy($user)
            ->filter($filters)
            ->with(['farm', 'category'])
            ->orderByDesc('harvest_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $all = Harvest::ownedBy($user)->get();
        $stats = [
            'count' => $all->count(),
            'kg' => $all->sum(fn (Harvest $h) => $h->quantityInKg()),
            'in_stock_kg' => $all->where('status', 'in_stock')->sum(fn (Harvest $h) => $h->quantityInKg()),
            'grade_a' => $all->count() ? round($all->where('quality_grade', 'A')->count() / $all->count() * 100) : 0,
        ];

        $farms = $user->farms()->orderBy('name')->get(['id', 'name']);
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('pro.harvests.index', compact('harvests', 'filters', 'stats', 'farms', 'categories'));
    }

    public function create(Request $request): View
    {
        $farms = $request->user()->farms()->orderBy('name')->get();

        if ($farms->isEmpty()) {
            return view('pro.harvests.no-farm');
        }

        $harvest = new Harvest([
            'farm_id' => $request->integer('farm') ?: $farms->firstWhere('status', 'active')?->id,
            'harvest_date' => now(),
            'unit' => 'kg',
            'quality_grade' => 'A',
            'status' => 'in_stock',
        ]);
        $categories = Category::forSelect();

        return view('pro.harvests.create', compact('harvest', 'farms', 'categories'));
    }

    public function store(HarvestRequest $request): RedirectResponse
    {
        $harvest = Harvest::create($request->harvestData());
        $harvest->syncImage($request->file('image'));

        return redirect()->route('pro.harvests.show', $harvest)
            ->with('success', "Récolte enregistrée. Code de lot : {$harvest->batch_code}");
    }

    public function show(Harvest $harvest, ProductIntelligence $intel): View
    {
        Gate::authorize('view', $harvest);

        $harvest->load(['farm.user', 'category']);

        return view('pro.harvests.show', [
            'harvest' => $harvest,
            'fresh' => $intel->freshness($harvest),
            'price' => $intel->price($harvest),
            'risk' => $intel->risk($harvest),
        ]);
    }

    /**
     * Étiquette imprimable (QR code + origine) à coller sur l'emballage.
     */
    public function label(Harvest $harvest, ProductIntelligence $intel): View
    {
        Gate::authorize('update', $harvest);

        $harvest->load(['farm.user', 'category']);

        return view('pro.harvests.label', ['harvest' => $harvest, 'fresh' => $intel->freshness($harvest)]);
    }

    public function edit(Request $request, Harvest $harvest): View
    {
        Gate::authorize('update', $harvest);

        $farms = $request->user()->farms()->orderBy('name')->get();
        $categories = Category::forSelect($harvest->category_id);

        return view('pro.harvests.edit', compact('harvest', 'farms', 'categories'));
    }

    public function update(HarvestRequest $request, Harvest $harvest): RedirectResponse
    {
        $harvest->fill($request->harvestData());
        $harvest->syncImage($request->file('image'), $request->boolean('remove_image'));

        return redirect()->route('pro.harvests.show', $harvest)
            ->with('success', "Le lot {$harvest->batch_code} a été mis à jour.");
    }

    public function destroy(Harvest $harvest): RedirectResponse
    {
        Gate::authorize('delete', $harvest);

        $code = $harvest->batch_code;
        $harvest->delete();

        return redirect()->route('pro.harvests.index')
            ->with('success', "Le lot {$code} a été supprimé.");
    }

    public function export(Request $request): StreamedResponse
    {
        $harvests = Harvest::ownedBy($request->user())
            ->filter($request->only(self::FILTERS))
            ->with(['farm', 'category'])
            ->orderByDesc('harvest_date')
            ->get();

        return response()->streamDownload(function () use ($harvests) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Code lot', 'Produit', 'Variété', 'Catégorie', 'Exploitation', 'Parcelle', 'Surface (ha)', 'Plantation', 'Récolte', 'Quantité', 'Unité', 'Quantité (kg)', 'Qualité', 'Statut'], ';');
            foreach ($harvests as $h) {
                fputcsv($out, [
                    $h->batch_code, $h->product_name, $h->variety, $h->categoryLabel(), $h->farm->name, $h->plot_name, $h->plot_area,
                    $h->planting_date?->format('d/m/Y'), $h->harvest_date->format('d/m/Y'), $h->quantity, $h->unitShort(),
                    $h->quantityInKg(), $h->quality_grade, $h->statusLabel(),
                ], ';');
            }
            fclose($out);
        }, 'recoltes-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
