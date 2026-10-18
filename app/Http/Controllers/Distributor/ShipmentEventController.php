<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShipmentEventController extends Controller
{
    /**
     * Ajoute un événement au journal de traçabilité
     */
    public function store(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorizeOwner($shipment);

        $shipment->events()->create($this->validateData($request));

        return redirect()->route('pro.distributor.shipments.show', $shipment)
            ->with('success', 'Événement ajouté au journal.');
    }

    public function edit(Shipment $shipment, ShipmentEvent $event): View
    {
        $this->authorizeOwner($shipment);

        return view('pro.distributor.events.edit', compact('shipment', 'event'));
    }

    public function update(Request $request, Shipment $shipment, ShipmentEvent $event): RedirectResponse
    {
        $this->authorizeOwner($shipment);

        $event->update($this->validateData($request));

        return redirect()->route('pro.distributor.shipments.show', $shipment)
            ->with('success', 'Événement modifié.');
    }

    public function destroy(Shipment $shipment, ShipmentEvent $event): RedirectResponse
    {
        $this->authorizeOwner($shipment);

        $event->delete();

        return redirect()->route('pro.distributor.shipments.show', $shipment)
            ->with('success', 'Événement supprimé.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys(ShipmentEvent::TYPES))],
            'location' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'occurred_at' => ['required', 'date'],
        ]);
    }

    private function authorizeOwner(Shipment $shipment): void
    {
        abort_if($shipment->user_id !== auth()->id(), 403);
    }
}
