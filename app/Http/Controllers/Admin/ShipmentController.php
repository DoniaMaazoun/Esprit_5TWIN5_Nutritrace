<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    /**
     * Module 3 - Supervision de toutes les expéditions (tous distributeurs)
     */
    public function index(Request $request): View
    {
        $shipments = Shipment::with(['user', 'pointOfSale'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('backend.pages.logistics.shipments.index', compact('shipments'));
    }

    public function show(Shipment $shipment): View
    {
        $shipment->load(['user', 'productionLot.recipe.items', 'productionLot.processor', 'pointOfSale', 'legs.transportMode', 'events']);

        return view('backend.pages.logistics.shipments.show', [
            'shipment' => $shipment,
            'originHarvests' => $shipment->originHarvests(),
        ]);
    }

    public function destroy(Shipment $shipment): RedirectResponse
    {
        $shipment->delete();

        return redirect()->route('backend.logistics.shipments.index')
            ->with('success', "Expédition {$shipment->reference} supprimée.");
    }
}
