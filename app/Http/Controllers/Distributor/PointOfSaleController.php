<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use App\Models\PointOfSale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PointOfSaleController extends Controller
{
    /**
     * Liste des points de vente du distributeur connecté
     */
    public function index(): View
    {
        $pointsOfSale = auth()->user()->pointsOfSale()
            ->withCount('shipments')
            ->latest()
            ->paginate(10);

        return view('pro.distributor.points-of-sale.index', compact('pointsOfSale'));
    }

    public function create(): View
    {
        return view('pro.distributor.points-of-sale.create', [
            'pointOfSale' => new PointOfSale(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        auth()->user()->pointsOfSale()->create($this->validateData($request));

        return redirect()->route('pro.distributor.points-of-sale.index')
            ->with('success', 'Point de vente créé avec succès.');
    }

    /**
     * Détail d'un point de vente avec les expéditions reçues
     */
    public function show(PointOfSale $pointOfSale): View
    {
        $this->authorizeOwner($pointOfSale);

        $shipments = $pointOfSale->shipments()->latest()->paginate(10);

        return view('pro.distributor.points-of-sale.show', compact('pointOfSale', 'shipments'));
    }

    public function edit(PointOfSale $pointOfSale): View
    {
        $this->authorizeOwner($pointOfSale);

        return view('pro.distributor.points-of-sale.edit', compact('pointOfSale'));
    }

    public function update(Request $request, PointOfSale $pointOfSale): RedirectResponse
    {
        $this->authorizeOwner($pointOfSale);

        $pointOfSale->update($this->validateData($request));

        return redirect()->route('pro.distributor.points-of-sale.index')
            ->with('success', 'Point de vente modifié avec succès.');
    }

    public function destroy(PointOfSale $pointOfSale): RedirectResponse
    {
        $this->authorizeOwner($pointOfSale);

        // Les expéditions liées gardent leur historique (point_of_sale_id passe à NULL)
        $pointOfSale->delete();

        return redirect()->route('pro.distributor.points-of-sale.index')
            ->with('success', 'Point de vente supprimé.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(PointOfSale::TYPES))],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
    }

    /**
     * Un distributeur ne peut gérer que ses propres points de vente
     */
    private function authorizeOwner(PointOfSale $pointOfSale): void
    {
        abort_if($pointOfSale->user_id !== auth()->id(), 403);
    }
}
