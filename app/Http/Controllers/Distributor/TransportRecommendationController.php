<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\TransportRecommender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransportRecommendationController extends Controller
{
    /**
     * Recommandation IA du mode de transport le moins polluant pour une étape
     */
    public function __invoke(Request $request, Shipment $shipment, TransportRecommender $recommender): JsonResponse
    {
        abort_if($shipment->user_id !== auth()->id(), 403);

        $data = $request->validate([
            'distance_km' => ['required', 'numeric', 'min:0.1', 'max:50000'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.01'],
            'max_hours' => ['nullable', 'numeric', 'min:1', 'max:2000'],
        ]);

        return response()->json($recommender->recommend(
            (float) $data['distance_km'],
            (float) ($data['weight_kg'] ?? $shipment->weight_kg),
            isset($data['max_hours']) ? (float) $data['max_hours'] : null,
        ));
    }
}
