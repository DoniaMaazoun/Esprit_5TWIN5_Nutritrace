<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use App\Models\ProductionLot;
use App\Models\Shipment;
use App\Models\TransportMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Formulaire en une page : infos de l'expédition, étapes de transport et recommandation IA
     */
    public function create(): View
    {
        return view('pro.distributor.shipments.create', $this->formData(new Shipment(['status' => 'planned'])));
    }

    /**
     * Enregistre l'expédition et toutes ses étapes en une seule transaction
     */
    public function store(Request $request): RedirectResponse
    {
        [$data, $legs] = $this->validateData($request);

        $shipment = DB::transaction(function () use ($data, $legs) {
            $shipment = auth()->user()->shipments()->create($data + ['reference' => Shipment::nextReference()]);
            $this->syncLegs($shipment, $legs);

            return $shipment;
        });

        return redirect()->route('pro.distributor.shipments.show', $shipment)
            ->with('success', "Expédition {$shipment->reference} créée avec ".count($legs).' étape(s) : '
                .number_format($shipment->total_co2_kg, 2, ',', ' ').' kg CO2e.');
    }

    /**
     * Fiche de consultation et de suivi : traçabilité, carte, étapes, journal et statut
     */
    public function show(Shipment $shipment): View
    {
        $this->authorizeOwner($shipment);

        $shipment->load(['productionLot.recipe.items', 'productionLot.processor', 'pointOfSale', 'legs.transportMode', 'events']);

        return view('pro.distributor.shipments.show', [
            'shipment' => $shipment,
            'originHarvests' => $shipment->originHarvests(),
        ]);
    }

    public function edit(Shipment $shipment): View
    {
        $this->authorizeOwner($shipment);

        $shipment->load('legs');

        return view('pro.distributor.shipments.edit', $this->formData($shipment));
    }

    /**
     * Met à jour l'expédition et ses étapes (ajoutées, modifiées ou supprimées) en une seule transaction
     */
    public function update(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorizeOwner($shipment);

        [$data, $legs] = $this->validateData($request, $shipment);

        DB::transaction(function () use ($shipment, $data, $legs) {
            $shipment->update($data);
            $this->syncLegs($shipment, $legs);
        });

        return redirect()->route('pro.distributor.shipments.show', $shipment)
            ->with('success', 'Expédition modifiée, CO2 recalculé : '.number_format($shipment->total_co2_kg, 2, ',', ' ').' kg CO2e.');
    }

    /**
     * Changement de statut depuis la fiche de suivi (chargée, partie, livrée...)
     */
    public function updateStatus(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorizeOwner($shipment);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Shipment::STATUSES))],
        ]);

        // Les dates de départ et de livraison sont complétées si elles ne sont pas encore connues
        if ($data['status'] === 'in_transit' && ! $shipment->departed_at) {
            $data['departed_at'] = now();
        }
        if ($data['status'] === 'delivered') {
            $data['departed_at'] = $shipment->departed_at ?? now();
            $data['delivered_at'] = $shipment->delivered_at ?? now();
        }

        $shipment->update($data);

        return redirect()->route('pro.distributor.shipments.show', $shipment)
            ->with('success', 'Statut mis à jour : '.$shipment->getStatusLabel().'.');
    }

    public function destroy(Shipment $shipment): RedirectResponse
    {
        $this->authorizeOwner($shipment);

        // Les étapes et événements sont supprimés en cascade
        $shipment->delete();

        return redirect()->route('pro.distributor.shipments.index')
            ->with('success', "Expédition {$shipment->reference} supprimée.");
    }

    private function formData(Shipment $shipment): array
    {
        return [
            'shipment' => $shipment,
            'pointsOfSale' => auth()->user()->pointsOfSale()->orderBy('name')->get(),
            'productionLots' => $this->availableProductionLots($shipment),
            'transportModes' => TransportMode::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    /**
     * @return array{0: array, 1: array<int, array>} données de l'expédition, puis étapes dans l'ordre
     */
    private function validateData(Request $request, ?Shipment $shipment = null): array
    {
        // Une ligne d'étape laissée entièrement vide est ignorée
        $request->merge(['legs' => collect($request->input('legs', []))
            ->filter(fn ($leg) => is_array($leg) && collect($leg)->only(['transport_mode_id', 'from_location', 'to_location', 'distance_km'])->filter(fn ($v) => filled($v))->isNotEmpty())
            ->values()->all()]);

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

            'legs' => ['array', 'max:20'],
            // Une étape existante ne peut appartenir qu'à cette expédition
            'legs.*.id' => ['nullable', 'integer', Rule::exists('shipment_legs', 'id')->where('shipment_id', $shipment?->id ?? 0)],
            'legs.*.transport_mode_id' => ['required', Rule::exists('transport_modes', 'id')->where('is_active', true)],
            'legs.*.from_location' => ['required', 'string', 'max:255'],
            'legs.*.to_location' => ['required', 'string', 'max:255'],
            'legs.*.distance_km' => ['required', 'numeric', 'min:0.1', 'max:50000'],
            'legs.*.to_latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:legs.*.to_longitude'],
            'legs.*.to_longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:legs.*.to_latitude'],
            'legs.*.from_recommendation' => ['nullable', 'boolean'],
        ], [], [
            'legs.*.transport_mode_id' => 'mode de transport (étape :position)',
            'legs.*.from_location' => 'départ (étape :position)',
            'legs.*.to_location' => 'arrivée (étape :position)',
            'legs.*.distance_km' => 'distance (étape :position)',
            'legs.*.to_latitude' => 'latitude (étape :position)',
            'legs.*.to_longitude' => 'longitude (étape :position)',
        ]);

        $legs = collect($data['legs'] ?? [])->map(fn ($leg) => [
            'id' => $leg['id'] ?? null,
            'transport_mode_id' => $leg['transport_mode_id'],
            'from_location' => $leg['from_location'],
            'to_location' => $leg['to_location'],
            'distance_km' => $leg['distance_km'],
            'to_latitude' => $leg['to_latitude'] ?? null,
            'to_longitude' => $leg['to_longitude'] ?? null,
            // Étape issue de la recommandation IA : sert au calcul du CO2 économisé du dashboard
            'from_recommendation' => filter_var($leg['from_recommendation'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ])->all();
        unset($data['legs']);

        // Sans nom saisi, on reprend celui du produit fini (recette) du lot choisi
        if (empty($data['product_name']) && ! empty($data['production_lot_id'])) {
            $data['product_name'] = ProductionLot::with('recipe')->find($data['production_lot_id'])?->getProductName();
        }

        return [$data, $legs];
    }

    /**
     * Remplace les étapes de l'expédition par celles du formulaire : le CO2 de chaque étape
     * est recalculé côté serveur (ShipmentLeg::calculateCo2), avec le poids de l'expédition
     */
    private function syncLegs(Shipment $shipment, array $legs): void
    {
        $keptIds = array_filter(array_column($legs, 'id'));
        $shipment->legs()->whereNotIn('id', $keptIds)->delete();

        foreach ($legs as $index => $row) {
            $leg = $row['id'] ? $shipment->legs()->findOrFail($row['id']) : $shipment->legs()->make();
            unset($row['id']);

            $leg->fill($row + [
                'position' => $index + 1,
                'weight_kg' => $shipment->weight_kg,
            ])->calculateCo2()->save();
        }

        $shipment->recalculateTotals();
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
