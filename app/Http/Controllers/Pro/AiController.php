<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Services\AiAssistant;
use App\Services\ProcessorAiAssistant;
use App\Services\ProductIntelligence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Fonctionnalités IA du Module 1 : description générée, analyse d'exploitation, chatbot.
 * Fonctionnalités IA du Module 3 : génération de recettes, prévention du gaspillage.
 */
class AiController extends Controller
{
    public function __construct(private AiAssistant $ai, private ProcessorAiAssistant $processorAi)
    {
    }

    public function farmDescription(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'governorate' => ['nullable', Rule::in(Farm::GOVERNORATES)],
            'city' => ['nullable', 'string', 'max:80'],
            'total_area' => ['nullable', 'numeric', 'min:0'],
            'farming_type' => ['nullable', Rule::in(array_keys(Farm::FARMING_TYPES))],
            'irrigation_type' => ['nullable', Rule::in(array_keys(Farm::IRRIGATION_TYPES))],
            'soil_type' => ['nullable', Rule::in(array_keys(Farm::SOIL_TYPES))],
            'main_crops' => ['nullable', 'string', 'max:255'],
            'certification' => ['nullable', 'string', 'max:100'],
            'established_year' => ['nullable', 'integer'],
        ], [
            'name.required' => 'Saisissez d\'abord le nom de l\'exploitation pour générer la description.',
        ]);

        return response()->json($this->ai->farmDescription($data));
    }

    /**
     * Fiche consommateur d'un lot, rédigée à partir des champs du formulaire.
     */
    public function harvestDescription(Request $request, ProductIntelligence $intel): JsonResponse
    {
        $data = $request->validate([
            'product_name' => ['required', 'string', 'max:100'],
            'variety' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'farm_id' => ['required', Rule::exists('farms', 'id')->where('user_id', $request->user()->id)],
            'harvest_date' => ['nullable', 'date'],
            'quality_grade' => ['nullable', Rule::in(['A', 'B', 'C'])],
            'storage_conditions' => ['nullable', 'string', 'max:255'],
            'treatments' => ['nullable', 'string', 'max:1000'],
        ], [
            'product_name.required' => 'Saisissez d\'abord le nom du produit.',
            'farm_id.required' => 'Choisissez d\'abord l\'exploitation.',
            'farm_id.exists' => 'Exploitation invalide.',
        ]);

        return response()->json($intel->consumerDescription($data));
    }

    public function farmInsights(Farm $farm): JsonResponse
    {
        // L'analyse est une donnée privée : réservée au propriétaire (et à l'admin)
        Gate::authorize('update', $farm);

        return response()->json($this->ai->farmInsights($farm));
    }

    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'history' => ['nullable', 'array', 'max:20'],
            'history.*.role' => ['required', Rule::in(['user', 'assistant'])],
            'history.*.content' => ['required', 'string', 'max:2000'],
        ]);

        return response()->json($this->ai->chat($request->user(), $data['message'], $data['history'] ?? []));
    }

    /**
     * Generate recipe description and instructions using AI
     */
    public function generateRecipeContent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipe_name' => ['required', 'string', 'max:150'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.name' => ['required', 'string', 'max:100'],
            'ingredients.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'ingredients.*.unit' => ['required', 'string', 'max:20'],
        ]);

        $content = $this->processorAi->generateRecipeContent($data);

        return response()->json($content);
    }
}
