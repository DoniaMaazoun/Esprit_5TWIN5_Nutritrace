<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ShipmentPdfController extends Controller
{
    /**
     * Rapport de traçabilité d'une expédition en PDF (lot de production, origine, étapes, journal, CO2)
     */
    public function __invoke(Shipment $shipment): Response
    {
        abort_if($shipment->user_id !== auth()->id(), 403);

        $shipment->load(['user', 'productionLot.recipe.items', 'productionLot.processor', 'pointOfSale', 'legs.transportMode', 'events']);

        return Pdf::loadView('pro.distributor.shipments.pdf', [
            'shipment' => $shipment,
            'originHarvests' => $shipment->originHarvests(),
        ])
            ->setPaper('a4')
            ->download("rapport-tracabilite-{$shipment->reference}.pdf");
    }
}
