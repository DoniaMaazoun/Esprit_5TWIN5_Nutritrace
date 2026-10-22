<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Models\Harvest;
use App\Models\ProductionLot;
use App\Services\ProductIntelligence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Pages publiques de traçabilité : origine d'un lot (cible du QR code) et vitrine d'une exploitation.
 */
class TraceabilityController extends Controller
{
    public function show(string $code, ProductIntelligence $intel): View
    {
        // Try to find as a ProductionLot first (production lot QR codes start with "LOT-")
        if (Str::startsWith($code, 'LOT-')) {
            $productionLot = ProductionLot::where('qr_code_data', $code)
                ->with(['recipe', 'processor', 'recipe.items'])
                ->first();
            
            if ($productionLot) {
                return view('frontend.pages.traceability-production-lot', [
                    'lot' => $productionLot,
                ]);
            }
        }

        // Fall back to Harvest (original code)
        $harvest = Harvest::where('batch_code', $code)->with(['farm.user', 'category'])->firstOrFail();

        $related = $harvest->farm->harvests()->with(['farm.user', 'category'])
            ->whereKeyNot($harvest->id)->latest('harvest_date')->take(3)->get();

        return view('frontend.pages.traceability', [
            'harvest' => $harvest,
            'related' => $related,
            'similar' => $intel->similar($harvest),
            'fresh' => $intel->freshness($harvest),
        ]);
    }

    /**
     * « Tracer un lot » : recherche par code saisi (navbar, accueil).
     */
    public function lookup(Request $request): RedirectResponse
    {
        $request->merge(['code' => Str::upper(trim((string) $request->input('code')))]);

        $data = $request->validateWithBag('lookup', [
            'code' => ['required', 'regex:/^LOT-\d{8}-[A-Z0-9]{5}$/'],
        ], [
            'code.required' => 'Saisissez le code du lot (inscrit sous le QR code).',
            'code.regex' => 'Format attendu : LOT-AAAAMMJJ-XXXXX (ex. : LOT-20261015-KFYVO).',
        ]);

        if (!Harvest::where('batch_code', $data['code'])->exists()) {
            return back()->withErrors(['code' => "Aucun lot ne porte le code {$data['code']}."], 'lookup')->withInput();
        }

        return redirect()->route('traceability.show', $data['code']);
    }

    /**
     * Annuaire public des exploitations, avec carte.
     */
    public function farms(Request $request): View
    {
        $governorate = $request->input('governorate');

        $farms = Farm::whereHas('user', fn ($u) => $u->where('status', 'approved'))
            ->when($governorate, fn ($q) => $q->where('governorate', $governorate))
            ->with(['user', 'coverHarvest', 'harvests' => fn ($q) => $q->with('category')])
            ->withCount('harvests')
            ->orderByDesc('harvests_count')
            ->get();

        return view('frontend.pages.farms', compact('farms', 'governorate'));
    }

    /**
     * Vitrine publique d'une exploitation (producteur approuvé uniquement).
     */
    public function farm(Farm $farm): View
    {
        abort_unless($farm->user->status === 'approved', 404);

        $farm->load(['user', 'harvests' => fn ($q) => $q->with(['farm.user', 'category'])->latest('harvest_date')]);
        $harvests = $farm->harvests;

        $stats = [
            'products' => $harvests->count(),
            'kg' => $harvests->sum(fn (Harvest $h) => $h->quantityInKg()),
            'grade_a' => $harvests->count() ? round($harvests->where('quality_grade', 'A')->count() / $harvests->count() * 100) : null,
            'categories' => $harvests->pluck('category')->filter()->unique('id')->values(),
        ];
        $otherFarms = $farm->user->farms()->whereKeyNot($farm->id)->withCount('harvests')->get();

        return view('frontend.pages.farm', compact('farm', 'harvests', 'stats', 'otherFarms'));
    }
}
