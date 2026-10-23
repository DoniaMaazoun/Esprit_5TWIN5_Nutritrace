<?php

namespace App\Services;

use App\Models\DistributorStock;
use App\Models\Harvest;
use App\Models\User;
use App\Services\AI\AIClientInterface;
use App\Services\AI\GroqClient;
use App\Services\AI\LocalFallbackClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service NutriAI pour l'assistant conversationnel consommateur
 * Récupère les données de manière sécurisée et interagit avec le fournisseur AI
 */
class NutriAIService
{
    private AIClientInterface $aiClient;

    public function __construct(?AIClientInterface $aiClient = null)
    {
        // Utilise GroqClient par défaut, fallback sur LocalFallbackClient
        $this->aiClient = $aiClient ?? (new GroqClient());
    }

    /**
     * Change le client AI (pour tests ou changement de fournisseur)
     */
    public function setAiClient(AIClientInterface $client): void
    {
        $this->aiClient = $client;
    }

    // ==================== RÉCUPÉRATION DONNÉES CATALOGUE ====================

    /**
     * Recherche des produits dans le catalogue
     *
     * @param  string  $query Terme de recherche
     * @param  User|null  $user Utilisateur (pour permissions)
     * @return array{results: array, sources: array, missing: array}
     */
    public function searchProducts(string $query, ?User $user = null): array
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return [
                'results' => [],
                'sources' => [],
                'missing' => ['La recherche doit contenir au moins 2 caractères'],
            ];
        }

        try {
            $stocks = DistributorStock::query()
                ->withTraceability()
                ->available()
                ->where(function (Builder $q) use ($query) {
                    $q->whereHas('productionLot.recipe', fn (Builder $r) => $r->where('name', 'like', "%{$query}%"))
                        ->orWhereHas('pointOfSale', fn (Builder $p) => $p->where('name', 'like', "%{$query}%"))
                        ->orWhereHas('pointOfSale.user', fn (Builder $u) => $u->where('company_name', 'like', "%{$query}%"));
                })
                ->limit(10)
                ->get();

            $results = [];
            $sources = [];
            $missing = [];

            foreach ($stocks as $stock) {
                $results[] = [
                    'id' => $stock->id,
                    'product_name' => $stock->getProductName(),
                    'price' => $stock->unit_price ? number_format($stock->unit_price, 2, ',', ' ') . ' DT' : 'Non disponible',
                    'quantity' => $stock->quantity_available,
                    'unit' => $stock->productionLot->unit ?? 'kg',
                    'point_of_sale' => $stock->getPointOfSaleName(),
                    'city' => $stock->getCity(),
                    'distributor' => $stock->getDistributorName(),
                    'production_date' => $stock->productionLot->production_date?->format('d/m/Y'),
                    'expiry_date' => $stock->productionLot->expiry_date?->format('d/m/Y'),
                ];
                $sources[] = [
                    'type' => 'distributor_stock',
                    'id' => $stock->id,
                    'model' => 'DistributorStock',
                ];
            }

            if (empty($results)) {
                $missing[] = "Aucun produit trouvé pour « {$query} »";
            }

            return [
                'results' => $results,
                'sources' => $sources,
                'missing' => $missing,
            ];
        } catch (\Throwable $e) {
            Log::error('NutriAI search error: ' . $e->getMessage());
            return [
                'results' => [],
                'sources' => [],
                'missing' => ['Erreur lors de la recherche'],
            ];
        }
    }

    /**
     * Récupère les détails d'un produit spécifique
     *
     * @param  int  $stockId ID du stock
     * @param  User|null  $user Utilisateur (pour permissions)
     * @return array{product: array|null, sources: array, missing: array}
     */
    public function getProductDetails(int $stockId, ?User $user = null): array
    {
        try {
            $stock = DistributorStock::query()
                ->withTraceability()
                ->available()
                ->find($stockId);

            if (!$stock) {
                return [
                    'product' => null,
                    'sources' => [],
                    'missing' => ["Produit #{$stockId} non trouvé ou non disponible"],
                ];
            }

            $product = [
                'id' => $stock->id,
                'product_name' => $stock->getProductName(),
                'recipe_name' => $stock->productionLot->recipe->name ?? 'Non spécifié',
                'recipe_description' => $stock->productionLot->recipe->description ?? null,
                'price' => $stock->unit_price ? number_format($stock->unit_price, 2, ',', ' ') . ' DT' : 'Non disponible',
                'quantity_available' => $stock->quantity_available,
                'unit' => $stock->productionLot->unit ?? 'kg',
                'point_of_sale' => $stock->getPointOfSaleName(),
                'point_of_sale_address' => $stock->pointOfSale->address ?? null,
                'city' => $stock->getCity(),
                'distributor' => $stock->getDistributorName(),
                'processor' => $stock->productionLot->processor->name ?? 'Non spécifié',
                'production_date' => $stock->productionLot->production_date?->format('d/m/Y'),
                'expiry_date' => $stock->productionLot->expiry_date?->format('d/m/Y'),
                'is_expired' => $stock->productionLot->isExpired(),
                'is_close_to_expiry' => $stock->productionLot->isCloseToExpiry(),
            ];

            // Ingrédients de la recette
            $ingredients = [];
            if ($stock->productionLot->recipe && $stock->productionLot->recipe->items) {
                foreach ($stock->productionLot->recipe->items as $item) {
                    $ingredients[] = [
                        'name' => $item->raw_material_name,
                        'quantity' => $item->quantity_required,
                        'unit' => $item->unit,
                    ];
                }
            }
            $product['ingredients'] = $ingredients;

            $missing = [];
            if (!$stock->unit_price) {
                $missing[] = 'Prix non disponible';
            }
            if (empty($ingredients)) {
                $missing[] = 'Ingrédients non spécifiés';
            }

            return [
                'product' => $product,
                'sources' => [
                    [
                        'type' => 'distributor_stock',
                        'id' => $stock->id,
                        'model' => 'DistributorStock',
                    ],
                    [
                        'type' => 'production_lot',
                        'id' => $stock->productionLot->id,
                        'model' => 'ProductionLot',
                    ],
                ],
                'missing' => $missing,
            ];
        } catch (\Throwable $e) {
            Log::error('NutriAI product details error: ' . $e->getMessage());
            return [
                'product' => null,
                'sources' => [],
                'missing' => ['Erreur lors de la récupération des détails'],
            ];
        }
    }

    /**
     * Compare plusieurs produits
     *
     * @param  array<int>  $stockIds IDs des stocks à comparer
     * @param  User|null  $user Utilisateur (pour permissions)
     * @return array{products: array, comparison: array, sources: array, missing: array}
     */
    public function compareProducts(array $stockIds, ?User $user = null): array
    {
        if (count($stockIds) < 2) {
            return [
                'products' => [],
                'comparison' => [],
                'sources' => [],
                'missing' => ['Au moins 2 produits sont nécessaires pour la comparaison'],
            ];
        }

        try {
            $stocks = DistributorStock::query()
                ->withTraceability()
                ->available()
                ->whereIn('id', $stockIds)
                ->get();

            if ($stocks->count() < 2) {
                return [
                    'products' => [],
                    'comparison' => [],
                    'sources' => [],
                    'missing' => ['Moins de 2 produits disponibles pour la comparaison'],
                ];
            }

            $products = [];
            $sources = [];
            $missing = [];

            foreach ($stocks as $stock) {
                $products[] = [
                    'id' => $stock->id,
                    'product_name' => $stock->getProductName(),
                    'price' => $stock->unit_price,
                    'quantity' => $stock->quantity_available,
                    'unit' => $stock->productionLot->unit ?? 'kg',
                    'city' => $stock->getCity(),
                    'distributor' => $stock->getDistributorName(),
                    'production_date' => $stock->productionLot->production_date?->format('d/m/Y'),
                    'expiry_date' => $stock->productionLot->expiry_date?->format('d/m/Y'),
                ];
                $sources[] = [
                    'type' => 'distributor_stock',
                    'id' => $stock->id,
                    'model' => 'DistributorStock',
                ];

                if (!$stock->unit_price) {
                    $missing[] = "Prix non disponible pour {$stock->getProductName()}";
                }
            }

            // Comparaison basée sur les données récupérées
            $comparison = [
                'price_range' => $this->calculatePriceRange($products),
                'cheapest' => $this->findCheapest($products),
                'most_available' => $this->findMostAvailable($products),
            ];

            return [
                'products' => $products,
                'comparison' => $comparison,
                'sources' => $sources,
                'missing' => $missing,
            ];
        } catch (\Throwable $e) {
            Log::error('NutriAI compare error: ' . $e->getMessage());
            return [
                'products' => [],
                'comparison' => [],
                'sources' => [],
                'missing' => ['Erreur lors de la comparaison'],
            ];
        }
    }

    // ==================== RÉCUPÉRATION DONNÉES TRAÇABILITÉ ====================

    /**
     * Explique la traçabilité d'un lot par son code batch
     *
     * @param  string  $batchCode Code du lot (ex: LOT-20261022-ABCDE)
     * @param  User|null  $user Utilisateur (pour permissions)
     * @return array{traceability: array|null, sources: array, missing: array}
     */
    public function explainTraceability(string $batchCode, ?User $user = null): array
    {
        try {
            $harvest = Harvest::where('batch_code', $batchCode)
                ->with(['farm', 'category'])
                ->first();

            if (!$harvest) {
                return [
                    'traceability' => null,
                    'sources' => [],
                    'missing' => ["Lot « {$batchCode} » non trouvé"],
                ];
            }

            $traceability = [
                'batch_code' => $harvest->batch_code,
                'product_name' => $harvest->product_name,
                'variety' => $harvest->variety,
                'category' => $harvest->category?->name,
                'quantity' => $harvest->quantityInKg() . ' kg',
                'harvest_date' => $harvest->harvest_date->format('d/m/Y'),
                'planting_date' => $harvest->planting_date?->format('d/m/Y'),
                'quality_grade' => $harvest->quality_grade,
                'status' => $harvest->statusLabel(),
                'farm' => [
                    'name' => $harvest->farm->name,
                    'city' => $harvest->farm->city,
                    'governorate' => $harvest->farm->governorate,
                    'farming_type' => $harvest->farm->farmingTypeLabel(),
                    'certification' => $harvest->farm->certification,
                ],
                'has_photo' => $harvest->hasPhoto(),
            ];

            $missing = [];
            if (!$harvest->variety) {
                $missing[] = 'Variété non spécifiée';
            }
            if (!$harvest->planting_date) {
                $missing[] = 'Date de plantation non spécifiée';
            }
            if (!$harvest->hasPhoto()) {
                $missing[] = 'Photo non disponible';
            }

            return [
                'traceability' => $traceability,
                'sources' => [
                    [
                        'type' => 'harvest',
                        'id' => $harvest->id,
                        'model' => 'Harvest',
                    ],
                    [
                        'type' => 'farm',
                        'id' => $harvest->farm->id,
                        'model' => 'Farm',
                    ],
                ],
                'missing' => $missing,
            ];
        } catch (\Throwable $e) {
            Log::error('NutriAI traceability error: ' . $e->getMessage());
            return [
                'traceability' => null,
                'sources' => [],
                'missing' => ['Erreur lors de la récupération de la traçabilité'],
            ];
        }
    }

    // ==================== QUESTION LIBRE ====================

    /**
     * Répond à une question libre en utilisant l'AI avec architecture RAG
     *
     * @param  string  $question Question de l'utilisateur
     * @param  User|null  $user Utilisateur (pour permissions)
     * @param  array  $history Historique de conversation
     * @return array{answer: string, sources: array, missing: array, source: string}
     */
    public function answerQuestion(string $question, ?User $user = null, array $history = []): array
    {
        $question = trim($question);
        if (strlen($question) < 2) {
            return [
                'answer' => 'Votre question est trop courte. Posez une question plus détaillée.',
                'sources' => [],
                'missing' => [],
                'source' => 'local',
            ];
        }

        if (strlen($question) > 500) {
            return [
                'answer' => 'Votre question est trop longue. Limitez-la à 500 caractères maximum.',
                'sources' => [],
                'missing' => [],
                'source' => 'local',
            ];
        }

        // Vérifier si le client AI est disponible
        if (!$this->aiClient->isEnabled()) {
            return [
                'answer' => 'NutriAI est temporairement indisponible. La clé API n\'est pas configurée. Veuillez contacter l\'administrateur.',
                'sources' => [],
                'missing' => [],
                'source' => 'error',
            ];
        }

        // Récupérer les données pertinentes depuis MySQL (RAG)
        $context = $this->retrieveContextForQuestion($question, $user);

        // Construire le prompt avec les données récupérées
        $systemPrompt = $this->buildSystemPrompt($context);
        $userPrompt = $this->buildUserPrompt($question, $context);

        // Appeler l'API
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        // Ajouter l'historique limité (derniers 5 messages)
        $history = array_slice($history, -10);
        foreach ($history as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }
        }

        try {
            $aiResponse = $this->aiClient->complete($messages, 600);

            if ($aiResponse === null) {
                return [
                    'answer' => 'NutriAI est temporairement indisponible. L\'appel API a échoué. Veuillez réessayer plus tard.',
                    'sources' => [],
                    'missing' => [],
                    'source' => 'error',
                ];
            }

            return [
                'answer' => $aiResponse,
                'sources' => $context['sources'] ?? [],
                'missing' => $context['missing'] ?? [],
                'source' => 'groq',
            ];
        } catch (\Throwable $e) {
            Log::error('NutriAI API error: ' . $e->getMessage());
            return [
                'answer' => 'NutriAI est temporairement indisponible. Une erreur est survenue lors du traitement de votre demande.',
                'sources' => [],
                'missing' => [],
                'source' => 'error',
            ];
        }
    }

    // ==================== MÉTHODES PRIVÉES ====================

    /**
     * Récupère le contexte pertinent depuis MySQL pour une question (RAG)
     */
    private function retrieveContextForQuestion(string $question, ?User $user): array
    {
        $q = Str::lower(Str::ascii($question));
        $context = [
            'products' => [],
            'traceability' => null,
            'sources' => [],
            'missing' => [],
        ];

        // Détection d'intention et récupération de données
        $hasProductKeywords = preg_match('/\b(produit|article|sauce|confiture|jus|soupe|tomate|fraise|orange|légume|fruit|prix|coût|combien|cher|dt|stock|disponible|acheter|commander)\b/', $q);
        $hasTraceabilityKeywords = preg_match('/\b(tracabilit|origine|provenance|ferme|producteur|lot|batch|qr code|scan)\b/', $q);
        $hasBatchCode = preg_match('/LOT-\d{8}-[A-Z0-9]{5}/', $question, $matches);

        // Récupérer les produits si pertinents
        if ($hasProductKeywords) {
            $searchResult = $this->searchProducts($question, $user);
            $context['products'] = $searchResult['results'];
            $context['sources'] = array_merge($context['sources'], $searchResult['sources']);
            $context['missing'] = array_merge($context['missing'], $searchResult['missing']);
        }

        // Récupérer la traçabilité si code de lot détecté
        if ($hasBatchCode) {
            $batchCode = $matches[0];
            $traceResult = $this->explainTraceability($batchCode, $user);
            $context['traceability'] = $traceResult['traceability'];
            $context['sources'] = array_merge($context['sources'], $traceResult['sources']);
            $context['missing'] = array_merge($context['missing'], $traceResult['missing']);
        } elseif ($hasTraceabilityKeywords) {
            $context['missing'][] = 'Code de lot non fourni pour la traçabilité';
        }

        return $context;
    }

    /**
     * Construit le prompt système pour l'IA
     */
    private function buildSystemPrompt(array $context): string
    {
        $prompt = "Tu es NutriAI, un assistant conversationnel pour Nutritrace, une plateforme de traçabilité alimentaire tunisienne.\n\n";
        $prompt .= "Règles importantes:\n";
        $prompt .= "- Réponds en français ou en arabe tunisien selon la langue de l'utilisateur.\n";
        $prompt .= "- Utilise UNIQUEMENT les données fournies dans le contexte. N'invente JAMAIS de produit, prix, stock, origine ou information de traçabilité.\n";
        $prompt .= "- Si une information n'est pas dans le contexte, dis-le explicitement.\n";
        $prompt .= "- Ne révèle JAMAIS de données privées (mots de passe, tokens, données personnelles).\n";
        $prompt .= "- Les prix sont en DT (dinars tunisiens).\n";
        $prompt .= "- Les gouvernorats tunisiens sont: Ariana, Béja, Ben Arous, Bizerte, Gabès, Gafsa, Jendouba, Kairouan, Kasserine, Kébili, Le Kef, Mahdia, La Manouba, Médenine, Monastir, Nabeul, Sfax, Sidi Bouzid, Siliana, Sousse, Tataouine, Tozeur, Tunis, Zaghouan.\n";
        $prompt .= "- Les types d'agriculture sont: organic (bio), conventional (conventionnelle), reasoned (raisonnée), permaculture, agroforestry.\n";
        $prompt .= "- Les grades de qualité sont: A (Extra), B (Catégorie I), C (Catégorie II).\n";

        if (!empty($context['missing'])) {
            $prompt .= "\nInformations manquantes: " . implode(', ', $context['missing']) . "\n";
        }

        return $prompt;
    }

    /**
     * Construit le prompt utilisateur avec les données récupérées
     */
    private function buildUserPrompt(string $question, array $context): string
    {
        $prompt = "Question de l'utilisateur: " . $question . "\n\n";

        if (!empty($context['products'])) {
            $prompt .= "Produits disponibles dans la base de données:\n";
            foreach ($context['products'] as $product) {
                $prompt .= "- {$product['product_name']}: ";
                $prompt .= "Prix: {$product['price']}, ";
                $prompt .= "Stock: {$product['quantity']} {$product['unit']}, ";
                $prompt .= "Point de vente: {$product['point_of_sale']} ({$product['city']}), ";
                $prompt .= "Distributeur: {$product['distributor']}\n";
            }
        }

        if ($context['traceability']) {
            $t = $context['traceability'];
            $prompt .= "\nInformations de traçabilité pour le lot {$t['batch_code']}:\n";
            $prompt .= "- Produit: {$t['product_name']}";
            if ($t['variety']) $prompt .= " (variété: {$t['variety']})";
            $prompt .= "\n";
            $prompt .= "- Quantité: {$t['quantity']}\n";
            $prompt .= "- Date de récolte: {$t['harvest_date']}\n";
            if ($t['planting_date']) $prompt .= "- Date de plantation: {$t['planting_date']}\n";
            $prompt .= "- Grade de qualité: {$t['quality_grade']}\n";
            $prompt .= "- Ferme: {$t['farm']['name']} ({$t['farm']['city']}, {$t['farm']['governorate']})\n";
            $prompt .= "- Type d'agriculture: {$t['farm']['farming_type']}\n";
            if ($t['farm']['certification']) $prompt .= "- Certification: {$t['farm']['certification']}\n";
        }

        if (empty($context['products']) && empty($context['traceability'])) {
            $prompt .= "\nAucune donnée spécifique n'a été trouvée dans la base de données pour cette question.\n";
            $prompt .= "Tu peux expliquer les capacités générales de Nutritrace, mais ne présente pas ces informations comme des faits vérifiés concernant notre base.\n";
        }

        return $prompt;
    }

    /**
     * Calcule la plage de prix pour la comparaison
     */
    private function calculatePriceRange(array $products): array
    {
        $prices = array_filter(array_column($products, 'price'), fn ($p) => $p !== null);

        if (empty($prices)) {
            return ['min' => null, 'max' => null, 'avg' => null];
        }

        return [
            'min' => min($prices),
            'max' => max($prices),
            'avg' => array_sum($prices) / count($prices),
        ];
    }

    /**
     * Trouve le produit le moins cher
     */
    private function findCheapest(array $products): ?array
    {
        $withPrice = array_filter($products, fn ($p) => $p['price'] !== null);

        if (empty($withPrice)) {
            return null;
        }

        return collect($withPrice)->sortBy('price')->first();
    }

    /**
     * Trouve le produit avec le plus de stock
     */
    private function findMostAvailable(array $products): ?array
    {
        return collect($products)->sortByDesc('quantity')->first();
    }
}
