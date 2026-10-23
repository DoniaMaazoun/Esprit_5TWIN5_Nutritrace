<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Services\NutriAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Contrôleur NutriAI pour l'assistant conversationnel consommateur
 * Accès réservé aux consommateurs authentifiés
 */
class NutriAIController extends Controller
{
    private NutriAIService $nutriAIService;

    public function __construct(NutriAIService $nutriAIService)
    {
        $this->nutriAIService = $nutriAIService;
    }

    /**
     * Affiche l'interface de chat NutriAI
     */
    public function index()
    {
        return view('consumer.nutri-ai.index');
    }

    /**
     * Traite un message de l'utilisateur via le chat
     * Rate limiting: 10 requêtes/minute
     */
    public function chat(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'message' => ['required', 'string', 'min:2', 'max:500'],
                'history' => ['nullable', 'array'],
            ]);

            $user = Auth::user();
            $message = trim($validated['message']);
            $history = $validated['history'] ?? [];

            // Vérifier que l'utilisateur est un consumer
            if (!$user || $user->role !== 'consumer') {
                return response()->json([
                    'error' => 'Accès non autorisé. Cette fonctionnalité est réservée aux consommateurs.',
                ], 403);
            }

            // Appeler le service NutriAI
            $response = $this->nutriAIService->answerQuestion($message, $user, $history);

            return response()->json([
                'answer' => $response['answer'],
                'sources' => $response['sources'] ?? [],
                'missing' => $response['missing'] ?? [],
                'source' => $response['source'] ?? 'local',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Message invalide. Le message doit contenir entre 2 et 500 caractères.',
            ], 422);
        } catch (\Throwable $e) {
            Log::error('NutriAI chat error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'message' => $request->input('message'),
            ]);

            return response()->json([
                'error' => 'Une erreur est survenue lors du traitement de votre message. Veuillez réessayer.',
            ], 500);
        }
    }

    /**
     * Recherche des produits (endpoint public pour suggestions)
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'query' => ['required', 'string', 'min:2', 'max:100'],
            ]);

            $query = trim($validated['query']);
            $user = Auth::user();

            $result = $this->nutriAIService->searchProducts($query, $user);

            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Recherche invalide. Le terme doit contenir entre 2 et 100 caractères.',
            ], 422);
        } catch (\Throwable $e) {
            Log::error('NutriAI search error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Une erreur est survenue lors de la recherche.',
            ], 500);
        }
    }

    /**
     * Récupère les détails d'un produit
     */
    public function getProduct(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'stock_id' => ['required', 'integer', 'min:1'],
            ]);

            $stockId = $validated['stock_id'];
            $user = Auth::user();

            $result = $this->nutriAIService->getProductDetails($stockId, $user);

            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'ID de produit invalide.',
            ], 422);
        } catch (\Throwable $e) {
            Log::error('NutriAI get product error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Une erreur est survenue lors de la récupération du produit.',
            ], 500);
        }
    }

    /**
     * Explique la traçabilité d'un lot
     */
    public function traceability(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'batch_code' => ['required', 'string', 'min:5', 'max:50'],
            ]);

            $batchCode = trim($validated['batch_code']);
            $user = Auth::user();

            $result = $this->nutriAIService->explainTraceability($batchCode, $user);

            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Code de lot invalide.',
            ], 422);
        } catch (\Throwable $e) {
            Log::error('NutriAI traceability error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Une erreur est survenue lors de la récupération de la traçabilité.',
            ], 500);
        }
    }
}
