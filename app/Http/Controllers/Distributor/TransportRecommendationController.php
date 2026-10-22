<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\TransportRecommender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransportRecommendationController extends Controller
{
    /**
     * Recommandation IA du mode de transport le moins polluant, appelée depuis le formulaire
     * d'expédition avant l'enregistrement : elle part des valeurs saisies (lot, poids, distance totale)
     */
    public function __invoke(Request $request, TransportRecommender $recommender): JsonResponse
    {
        $data = $request->validate([
            'distance_km' => ['required', 'numeric', 'min:0.1', 'max:50000'],
            'weight_kg' => ['required', 'numeric', 'min:0.01'],
            'max_hours' => ['nullable', 'numeric', 'min:1', 'max:2000'],
            'production_lot_id' => ['nullable', 'integer', Rule::exists('production_lots', 'id')],
            'product_name' => ['nullable', 'string', 'max:255'],
            'point_of_sale_id' => ['nullable', Rule::exists('points_of_sale', 'id')->where('user_id', auth()->id())],
            // Expédition en cours de modification : exclue des conseils de regroupement
            'shipment_id' => ['nullable', 'integer', Rule::exists('shipments', 'id')->where('user_id', auth()->id())],
        ]);

        // Expédition « brouillon » non enregistrée, construite à partir du formulaire
        $draft = isset($data['shipment_id']) ? Shipment::findOrFail($data['shipment_id']) : new Shipment;
        $draft->fill([
            'user_id' => auth()->id(),
            'production_lot_id' => $data['production_lot_id'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'weight_kg' => $data['weight_kg'],
            'point_of_sale_id' => $data['point_of_sale_id'] ?? null,
        ]);
        $draft->load('productionLot.recipe.items', 'productionLot.processor', 'pointOfSale');

        $result = $recommender->recommend(
            (float) $data['distance_km'],
            (float) $data['weight_kg'],
            isset($data['max_hours']) ? (float) $data['max_hours'] : null,
            $draft,
        );

        return $result
            ? response()->json($result)
            : response()->json(['message' => 'Aucun mode de transport actif ne permet de livrer ce trajet.'], 422);
    }
}
