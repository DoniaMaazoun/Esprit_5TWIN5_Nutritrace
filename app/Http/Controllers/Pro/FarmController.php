<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Http\Requests\FarmRequest;
use App\Models\Farm;
use App\Models\Harvest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Module 1 (Producteur) : gestion des exploitations.
 */
class FarmController extends Controller
{
    private const SORTS = [
        'recent' => ['created_at', 'desc'],
        'name' => ['name', 'asc'],
        'area_desc' => ['total_area', 'desc'],
        'area_asc' => ['total_area', 'asc'],
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $filters = $request->only(['q', 'governorate', 'farming_type', 'status']);
        [$column, $direction] = self::SORTS[$request->input('sort')] ?? self::SORTS['recent'];

        $farms = $user->farms()
            ->filter($filters)
            ->with('coverHarvest')
            ->withCount('harvests')
            ->orderBy($column, $direction)
            ->paginate(9)
            ->withQueryString();

        $allFarms = $user->farms()->with('harvests')->get();
        $stats = [
            'farms' => $allFarms->count(),
            'area' => $allFarms->sum('total_area'),
            'harvests' => $allFarms->sum(fn (Farm $f) => $f->harvests->count()),
            'kg' => $allFarms->sum(fn (Farm $f) => $f->totalHarvestedKg()),
        ];

        $mapPoints = $allFarms->filter->hasLocation()->map(fn (Farm $f) => [
            'name' => $f->name,
            'lat' => $f->latitude,
            'lng' => $f->longitude,
            'url' => route('pro.farms.show', $f),
            'info' => "{$f->city} · {$f->total_area} ha · {$f->farmingTypeLabel()}",
        ])->values();

        return view('pro.farms.index', compact('farms', 'filters', 'stats', 'mapPoints'));
    }

    public function create(): View
    {
        return view('pro.farms.create', ['farm' => new Farm(['status' => 'active'])]);
    }

    public function store(FarmRequest $request): RedirectResponse
    {
        $farm = $request->user()->farms()->create($request->validated());

        return redirect()->route('pro.farms.show', $farm)
            ->with('success', "L'exploitation « {$farm->name} » a été créée avec succès.");
    }

    public function show(Farm $farm): View
    {
        Gate::authorize('view', $farm);

        $farm->load(['harvests' => fn ($q) => $q->orderByDesc('harvest_date')]);

        // Production mensuelle (kg) sur les 12 derniers mois
        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $monthly = $months->map(fn ($m) => round($farm->harvests
            ->filter(fn (Harvest $h) => $h->harvest_date->isSameMonth($m))
            ->sum(fn (Harvest $h) => $h->quantityInKg()), 1));

        $byProduct = $farm->harvests->groupBy('product_name')
            ->map(fn ($g) => round($g->sum(fn (Harvest $h) => $h->quantityInKg()), 1))
            ->sortDesc()
            ->take(6);

        $chart = [
            'months' => $months->map(fn ($m) => $m->translatedFormat('M Y'))->values(),
            'monthly' => $monthly->values(),
            'products' => $byProduct->keys(),
            'productKg' => $byProduct->values(),
        ];

        return view('pro.farms.show', compact('farm', 'chart'));
    }

    public function edit(Farm $farm): View
    {
        Gate::authorize('update', $farm);

        return view('pro.farms.edit', compact('farm'));
    }

    public function update(FarmRequest $request, Farm $farm): RedirectResponse
    {
        $farm->update($request->validated());

        return redirect()->route('pro.farms.show', $farm)
            ->with('success', "L'exploitation « {$farm->name} » a été mise à jour.");
    }

    public function destroy(Farm $farm): RedirectResponse
    {
        Gate::authorize('delete', $farm);

        $count = $farm->harvests()->count();
        $name = $farm->name;
        $farm->delete();

        return redirect()->route('pro.farms.index')
            ->with('success', "L'exploitation « {$name} » a été supprimée" . ($count ? " ainsi que ses {$count} récolte(s)." : '.'));
    }

    /**
     * Export CSV (compatible Excel) des exploitations filtrées.
     */
    public function export(Request $request): StreamedResponse
    {
        $farms = $request->user()->farms()
            ->filter($request->only(['q', 'governorate', 'farming_type', 'status']))
            ->with('harvests')
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($farms) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nom', 'Gouvernorat', 'Ville', 'Surface (ha)', 'Agriculture', 'Irrigation', 'Sol', 'Cultures', 'Certification', 'Statut', 'Récoltes', 'Production (kg)', 'Rendement (kg/ha)'], ';');
            foreach ($farms as $f) {
                fputcsv($out, [
                    $f->name, $f->governorate, $f->city, $f->total_area, $f->farmingTypeLabel(), $f->irrigationTypeLabel(),
                    $f->soilTypeLabel(), $f->main_crops, $f->certification, $f->statusLabel(), $f->harvests->count(),
                    round($f->totalHarvestedKg(), 1), $f->yieldPerHectare(),
                ], ';');
            }
            fclose($out);
        }, 'exploitations-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
