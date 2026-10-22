<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use App\Models\ProductionLot;
use App\Models\Shipment;
use App\Models\TransportMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    /**
     * Liste des expéditions du distributeur connecté
     */
    public function index(Request $request): View
    {
        $shipments = auth()->user()->shipments()
            ->with('pointOfSale')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pro.distributor.shipments.index', compact('shipments'));
    }

    public function create(): View
    {
        return view('pro.distributor.shipments.create', [
            'shipment' => new Shipment(['status' => 'planned']),
            'pointsOfSale' => auth()->user()->pointsOfSale()->orderBy('name')->get(),
            'productionLots' => $this->availableProductionLots(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shipment = auth()->user()->shipments()->create(
            $this->validateData($request) + ['reference' => Shipment::nextReference()]
        );

        return redirect()->route('pro.distributor.shipments.show', $shipment)
            ->with('success', "Expédition {$shipment->reference} créée. Ajoutez maintenant ses étapes de transport.");
    }

    /**
     * Fiche expédition : étapes de transport, CO2 et journal de traçabilité
     */
    public function show(Shipment $shipment): View
    {
        $this->authorizeOwner($shipment);

        $shipment->load(['productionLot.recipe.items', 'productionLot.processor', 'pointOfSale', 'legs.transportMode', 'events']);

        return view('pro.distributor.shipments.show', [
            'shipment' => $shipment,
            'originHarvests' => $shipment->originHarvests(),
            'transportModes' => TransportMode::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Shipment $shipment): View
    {
        $this->authorizeOwner($shipment);

        return view('pro.distributor.shipments.edit', [
            'shipment' => $shipment,
            'pointsOfSale' => auth()->user()->pointsOfSale()->orderBy('name')->get(),
            'productionLots' => $this->availableProductionLots($shipment),
        ]);
    }

    public function update(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorizeOwner($shipment);

        $shipment->update($this->validateData($request));

        return redirect()->route('pro.distributor.shipments.show', $shipment)
            ->with('success', 'Expédition modifiée avec succès.');
    }

    public function destroy(Shipment $shipment): RedirectResponse
    {
        $this->authorizeOwner($shipment);

        // Les étapes et événements sont supprimés en cascade
        $shipment->delete();

        return redirect()->route('pro.distributor.shipments.index')
            ->with('success', "Expédition {$shipment->reference} supprimée.");
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'production_lot_id' => ['nullable', 'integer', Rule::exists('production_lots', 'id')],
            'product_name' => ['required_without:production_lot_id', 'nullable', 'string', 'max:255'],
            'weight_kg' => ['required', 'numeric', 'min:0.01'],
            'origin_address' => ['required', 'string', 'max:255'],
            'point_of_sale_id' => [
                'nullable',
                Rule::exists('points_of_sale', 'id')->where('user_id', auth()->id()),
            ],
            'status' => ['required', Rule::in(array_keys(Shipment::STATUSES))],
            'departed_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date', 'after_or_equal:departed_at'],
        ]);

        // Sans nom saisi, on reprend celui du produit fini (recette) du lot choisi
        if (empty($data['product_name']) && ! empty($data['production_lot_id'])) {
            $data['product_name'] = ProductionLot::with('recipe')->find($data['production_lot_id'])?->getProductName();
        }

        return $data;
    }

    /**
     * Lots de production du module 2 qu'un distributeur peut expédier (produits, emballés
     * ou déjà expédiés, pas archivés), plus le lot déjà relié à l'expédition modifiée
     */
    private function availableProductionLots(?Shipment $shipment = null)
    {
        return ProductionLot::with(['recipe', 'processor'])
            ->where(fn ($query) => $query
                ->whereIn('status', ['produced', 'packaged', 'shipped'])
                ->when($shipment?->production_lot_id, fn ($q, $id) => $q->orWhere('id', $id)))
            ->orderByDesc('production_date')
            ->get();
    }

    /**
     * Un distributeur ne peut gérer que ses propres expéditions
     */
    private function authorizeOwner(Shipment $shipment): void
    {
        abort_if($shipment->user_id !== auth()->id(), 403);
    }
}
