<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentLeg;
use App\Models\TransportMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShipmentLegController extends Controller
{
    /**
     * Ajoute une étape de transport et calcule son empreinte carbone
     */
    public function store(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorizeOwner($shipment);

        $leg = $shipment->legs()->make($this->validateData($request));
        $leg->position = ($shipment->legs()->max('position') ?? 0) + 1;
        $leg->calculateCo2()->save();

        $shipment->recalculateTotals();

        return redirect()->route('pro.distributor.shipments.show', $shipment)
            ->with('success', 'Étape ajoutée : '.number_format($leg->co2_kg, 2, ',', ' ').' kg CO2e.');
    }

    public function edit(Shipment $shipment, ShipmentLeg $leg): View
    {
        $this->authorizeOwner($shipment);

        return view('pro.distributor.legs.edit', [
            'shipment' => $shipment,
            'leg' => $leg,
            'transportModes' => TransportMode::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Shipment $shipment, ShipmentLeg $leg): RedirectResponse
    {
        $this->authorizeOwner($shipment);

        $leg->fill($this->validateData($request))->calculateCo2()->save();

        $shipment->recalculateTotals();

        return redirect()->route('pro.distributor.shipments.show', $shipment)
            ->with('success', 'Étape modifiée, CO2 recalculé.');
    }

    public function destroy(Shipment $shipment, ShipmentLeg $leg): RedirectResponse
    {
        $this->authorizeOwner($shipment);

        $leg->delete();

        $shipment->recalculateTotals();

        return redirect()->route('pro.distributor.shipments.show', $shipment)
            ->with('success', 'Étape supprimée.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'transport_mode_id' => [
                'required',
                Rule::exists('transport_modes', 'id')->where('is_active', true),
            ],
            'from_location' => ['required', 'string', 'max:255'],
            'to_location' => ['required', 'string', 'max:255'],
            'to_latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:to_longitude'],
            'to_longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:to_latitude'],
            'distance_km' => ['required', 'numeric', 'min:0.1'],
            'weight_kg' => ['required', 'numeric', 'min:0.01'],
            'from_recommendation' => ['nullable', 'boolean'],
        ]);

        // Étape créée en appliquant la recommandation IA : sert au calcul du CO2 économisé
        $data['from_recommendation'] = $request->boolean('from_recommendation');

        return $data;
    }

    private function authorizeOwner(Shipment $shipment): void
    {
        abort_if($shipment->user_id !== auth()->id(), 403);
    }
}
