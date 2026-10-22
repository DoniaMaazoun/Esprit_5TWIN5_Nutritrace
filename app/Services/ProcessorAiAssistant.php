<?php

namespace App\Services;

use App\Models\ProductionLot;
use App\Models\RawMaterialReception;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * IA Assistant for Processor (Transformateur)
 * - Generate recipe descriptions and instructions
 * - Predict recipes to prioritize based on expiring materials
 * - Calculate eco-score
 */
class ProcessorAiAssistant
{
    /**
     * Generate recipe description and instructions using AI based on recipe name and ingredients
     * Uses configured AI service (e.g., Groq, OpenAI) to generate realistic content
     */
    public function generateRecipeContent(array $data): array
    {
        $recipeName = $data['recipe_name'] ?? 'Recette';
        $ingredients = $data['ingredients'] ?? [];
        
        $apiKey = config('services.ai.key');
        $baseUrl = config('services.ai.base_url', 'https://api.groq.com/openai/v1');
        $model = config('services.ai.model', 'llama-3.3-70b-versatile');

        if (!$apiKey) {
            // Use smart fallback based on recipe name and ingredients
            return $this->generateSmartFallback($recipeName, $ingredients);
        }

        try {
            // Build ingredient list
            $ingredientList = collect($ingredients)
                ->map(fn ($ing) => "{$ing['quantity']} {$ing['unit']} de {$ing['name']}")
                ->join(", ");

            $prompt = "Tu es un chef cuisinier expert en transformation agroalimentaire avec 25 ans d'expérience.

Je crée une nouvelle recette appelée: \"$recipeName\"
Avec ces ingrédients: $ingredientList

Génère une vraie recette professionnelle réaliste. Fournis en JSON valide (aucun texte avant/après, UNIQUEMENT le JSON):

{
  \"description\": \"Une description commerciale attrayante (3-4 phrases) qui décrit le produit final, son goût, ses utilisations, ses bénéfices. Sois appétissant et détaillé. Mentionne les ingrédients clés et le nom de la recette.\",
  \"instructions\": \"Instructions DÉTAILLÉES numérotées (6-8 étapes) qui expliquent comment transformer ces ingrédients pas-à-pas. Inclus les temps de cuisson/repos, les températures, les techniques. Sois très spécifique et réaliste pour un professionnel.\",
  \"eco_score\": \"A, B ou C basé sur: ingrédients naturels/locaux, peu de transformation chimique, respect environnemental\"
}";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un expert culinaire qui génère des recettes réalistes et détaillées. Tu réponds TOUJOURS et UNIQUEMENT en JSON valide, sans aucun texte avant ou après.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 800,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                
                // Clean up response (remove markdown code blocks if present)
                $content = preg_replace('/```json\s*/i', '', $content);
                $content = preg_replace('/```\s*/i', '', $content);
                $content = trim($content);
                
                $data = json_decode($content, true);

                if (is_array($data) && isset($data['description']) && isset($data['instructions'])) {
                    return [
                        'description' => $data['description'] ?? '',
                        'instructions' => $data['instructions'] ?? '',
                        'eco_score' => in_array($data['eco_score'] ?? 'B', ['A', 'B', 'C']) ? $data['eco_score'] : 'B',
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::error('AI Recipe Generation Error: ' . $e->getMessage());
        }

        // Use smart fallback if API fails
        return $this->generateSmartFallback($recipeName, $ingredients);
    }

    /**
     * Generate intelligent fallback content based on recipe name and ingredients
     * Used when API is not configured or fails
     */
    private function generateSmartFallback(string $recipeName, array $ingredients): array
    {
        // Extract recipe type from name
        $lowerName = strtolower($recipeName);
        
        // Determine recipe category
        $isSauce = str_contains($lowerName, 'sauce') || str_contains($lowerName, 'coulis');
        $isJam = str_contains($lowerName, 'confiture') || str_contains($lowerName, 'compote') || str_contains($lowerName, 'gelée');
        $isJuice = str_contains($lowerName, 'jus') || str_contains($lowerName, 'nectar');
        $isPreserve = str_contains($lowerName, 'conserve') || str_contains($lowerName, 'mise en conserve');
        $isDried = str_contains($lowerName, 'séché') || str_contains($lowerName, 'déshydraté');
        $isSoup = str_contains($lowerName, 'soupe') || str_contains($lowerName, 'potage');
        
        // Get main ingredient
        $mainIngredient = $ingredients[0]['name'] ?? 'produit';
        $ingredientList = collect($ingredients)
            ->pluck('name')
            ->join(', ');
        
        $description = match(true) {
            $isSauce => "Notre $recipeName est une sauce artisanale préparée à partir de $ingredientList. Riche en saveurs naturelles, elle offre une texture onctueuse et homogène. Parfaite pour rehausser vos plats préférés, cette sauce de qualité premium offre une excellente conservation grâce aux méthodes de transformation traditionnelles.",
            $isJam => "La $recipeName est une confiture délicieuse élaborée à partir de $ingredientList. Préparée selon des méthodes ancestrales, elle conserve le goût authentique des fruits frais. Idéale pour vos tartines du matin, sa texture fruit confite vous séduira à chaque bouchée.",
            $isJuice => "Découvrez notre $recipeName, un jus frais et naturel extrait de $ingredientList. Concentré en vitamines et nutriments essentiels, il offre une saveur intense et authentique. Sans additifs artificiels, ce jus de qualité supérieure conserve tous les bénéfices nutritionnels des fruits et légumes frais.",
            $isPreserve => "La $recipeName est une mise en conserve traditionnelle à base de $ingredientList. Préservée selon les normes de qualité les plus strictes, elle garantit une conservation optimale sans conservateurs chimiques. Un produit d'excellence pour votre consommation toute l'année.",
            $isDried => "Le $recipeName est un produit déshydraté préparé à partir de $ingredientList. Par séchage lent et maîtrisé, nous concentrons les saveurs et prolongeons la conservation. Léger, savoureux et nutritif, il s'intègre facilement dans tous vos plats.",
            $isSoup => "Notre $recipeName est une soupe délicieuse et réconfortante élaborée à partir de $ingredientList. Préparée avec soin, elle combine les meilleurs saveurs pour un repas équilibré et nourrissant. Facile à préparer, elle offre un moment de détente à table.",
            default => "Notre $recipeName est une recette artisanale préparée à partir de $ingredientList. Élaborée selon nos standards de qualité les plus exigeants, elle respecte les traditions de transformation agroalimentaire. Un produit savoureux et de qualité premium pour tous vos besoins."
        };
        
        $instructions = match(true) {
            $isSauce => 
                "1. Préparer et nettoyer les ingrédients ($ingredientList)\n" .
                "2. Chauffer à feu moyen jusqu'à 85-90°C\n" .
                "3. Maintenir la température 45-60 min selon la texture désirée\n" .
                "4. Vérifier la consistance et rectifier l'assaisonnement\n" .
                "5. Verser dans les récipients de stockage à chaud\n" .
                "6. Refroidir lentement pour éviter les chocs thermiques\n" .
                "7. Stocker au frais (4-8°C) pendant 3-4 semaines ou congeler",
            $isJam =>
                "1. Trier et nettoyer les fruits ($ingredientList)\n" .
                "2. Mélanger avec le sucre dans un ratio 1:0.8 selon goût\n" .
                "3. Chauffer progressivement jusqu'à 104-105°C\n" .
                "4. Laisser mijoter 30-45 min en remuant régulièrement\n" .
                "5. Tester le point de gélification (goutte sur assiette froide)\n" .
                "6. Verser rapidement en pots stérilisés\n" .
                "7. Laisser refroidir avant fermeture hermétique",
            $isJuice =>
                "1. Laver et préparer les ingrédients ($ingredientList)\n" .
                "2. Presser ou centrifuger pour extraire le jus\n" .
                "3. Filtrer pour obtenir une texture lisse\n" .
                "4. Pasteuriser à 75-80°C pendant 20-30 secondes\n" .
                "5. Embouteiller immédiatement en récipients stériles\n" .
                "6. Refroidir rapidement à 4°C\n" .
                "7. Conserver au réfrigérateur ou congeler",
            $isDried =>
                "1. Préparer les ingrédients ($ingredientList) en tranches régulières\n" .
                "2. Précuire légèrement si nécessaire (blanchir 2-3 min)\n" .
                "3. Disposer sur grilles sans superposition\n" .
                "4. Déshydrater à 60-70°C pendant 8-12 heures\n" .
                "5. Contrôler l'humidité résiduelle (< 12%)\n" .
                "6. Conditionner en conteneurs hermétiques\n" .
                "7. Stocker au sec et à température ambiante",
            $isSoup =>
                "1. Préparer et découper les ingrédients ($ingredientList)\n" .
                "2. Faire revenir à feu moyen 5-10 minutes\n" .
                "3. Ajouter du bouillon et porter à ébullition\n" .
                "4. Réduire le feu et laisser mijoter 30-45 min\n" .
                "5. Vérifier la cuisson des légumes\n" .
                "6. Assaisonner selon le goût\n" .
                "7. Verser dans conteneurs et conserver au froid",
            default =>
                "1. Préparer tous les ingrédients ($ingredientList)\n" .
                "2. Suivre les étapes de transformation adaptées au produit\n" .
                "3. Maintenir l'hygiène et la propreté des outils\n" .
                "4. Contrôler la température et la durée de traitement\n" .
                "5. Conditionner dans des récipients appropriés\n" .
                "6. Refroidir progressivement selon les besoins\n" .
                "7. Stocker en respectant les conditions de conservation recommandées"
        };
        
        // Determine eco-score based on ingredients
        $ecoFactors = 0;
        $hasOrganic = count($ingredients) >= 2; // Plus d'ingrédients = plus transformé = score bas
        if ($hasOrganic) $ecoFactors++;
        
        $hasLocalIngredients = true; // Assume local by default
        if ($hasLocalIngredients) $ecoFactors++;
        
        $ecoScore = $ecoFactors >= 2 ? 'A' : ($ecoFactors === 1 ? 'B' : 'C');
        
        return [
            'description' => $description,
            'instructions' => $instructions,
            'eco_score' => $ecoScore,
        ];
    }

    /**
     * Recommend waste prevention recipes using OpenAI
     * Analyzes expiring materials and generates recipe recommendations
     */
    public function recommendWastePreventionRecipes(array $expiringMaterials): ?array
    {
        if (empty($expiringMaterials)) {
            return null;
        }

        $apiKey = config('services.openai.key');
        if (!$apiKey) {
            return $this->generateFallbackWastePreventionRecipe($expiringMaterials);
        }

        try {
            // Format materials for the prompt
            $materialsDescription = collect($expiringMaterials)
                ->map(fn ($mat) => "{$mat['name']}: {$mat['quantity']} kg (expiration dans {$mat['days_until_expiry']} jours)")
                ->join("\n- ");

            $prompt = "Tu es un expert en transformation agroalimentaire spécialisé dans la prévention du gaspillage alimentaire.

Voici des matières premières qui vont bientôt expirer:
- $materialsDescription

Propose UNE recette prioritaire pour utiliser ces matières avant expiration. Retourne UNIQUEMENT un JSON valide, sans aucun texte avant ou après:

{
  \"recipe_name\": \"Nom de la recette (ex: Sauce Tomate Concentrée)\",
  \"justification\": \"Raison écologique et économique de cette recette (1 phrase)\",
  \"ingredients_to_prioritize\": [
    \"Ingrédient 1 de la liste fournie\",
    \"Ingrédient 2 de la liste fournie\"
  ],
  \"urgency\": \"critical|high|medium\",
  \"estimated_quantity\": \"Quantité estimée du produit fini (ex: 50 kg de sauce)\",
  \"processing_time_hours\": 4,
  \"estimated_shelf_life_days\": 90
}";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un expert en agroalimentaire et prévention du gaspillage. Tu réponds TOUJOURS et UNIQUEMENT en JSON valide, sans aucun texte avant ou après.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 600,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                
                // Clean up response
                $content = preg_replace('/```json\s*/i', '', $content);
                $content = preg_replace('/```\s*/i', '', $content);
                $content = trim($content);
                
                $data = json_decode($content, true);

                if (is_array($data) && isset($data['recipe_name']) && isset($data['justification'])) {
                    return [
                        'recipe_name' => $data['recipe_name'],
                        'justification' => $data['justification'],
                        'ingredients_to_prioritize' => $data['ingredients_to_prioritize'] ?? [],
                        'urgency' => $data['urgency'] ?? 'high',
                        'estimated_quantity' => $data['estimated_quantity'] ?? 'À définir',
                        'processing_time_hours' => $data['processing_time_hours'] ?? 4,
                        'estimated_shelf_life_days' => $data['estimated_shelf_life_days'] ?? 90,
                        'from_ai' => true,
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::error('AI Waste Prevention Recommendation Error: ' . $e->getMessage());
        }

        // Fallback if API fails
        return $this->generateFallbackWastePreventionRecipe($expiringMaterials);
    }

    /**
     * Generate fallback waste prevention recipe recommendation
     */
    private function generateFallbackWastePreventionRecipe(array $expiringMaterials): ?array
    {
        if (empty($expiringMaterials)) {
            return null;
        }

        // Sort by urgency
        usort($expiringMaterials, fn ($a, $b) => $a['days_until_expiry'] <=> $b['days_until_expiry']);

        $urgentMaterial = $expiringMaterials[0];
        $daysUrgent = $urgentMaterial['days_until_expiry'];
        $urgency = $daysUrgent <= 2 ? 'critical' : ($daysUrgent <= 5 ? 'high' : 'medium');

        // Generate recipe suggestions based on material type
        $materialName = strtolower($urgentMaterial['name']);
        
        if (str_contains($materialName, 'tomate')) {
            return [
                'recipe_name' => 'Sauce Tomate Concentrée',
                'justification' => 'Utilise efficacement les tomates périssables et crée un produit avec longue durée de conservation.',
                'ingredients_to_prioritize' => [$urgentMaterial['name']],
                'urgency' => $urgency,
                'estimated_quantity' => round($urgentMaterial['quantity'] * 0.8) . ' kg de sauce',
                'processing_time_hours' => 4,
                'estimated_shelf_life_days' => 120,
            ];
        } elseif (str_contains($materialName, 'fruit') || str_contains($materialName, 'pomme') || str_contains($materialName, 'poire')) {
            return [
                'recipe_name' => 'Compote de Fruits Naturelle',
                'justification' => 'Transforme les fruits périssables en produit stable avec excellente conservation et valeur ajoutée.',
                'ingredients_to_prioritize' => [$urgentMaterial['name']],
                'urgency' => $urgency,
                'estimated_quantity' => round($urgentMaterial['quantity'] * 0.9) . ' kg de compote',
                'processing_time_hours' => 3,
                'estimated_shelf_life_days' => 90,
            ];
        } elseif (str_contains($materialName, 'légume') || str_contains($materialName, 'carotte') || str_contains($materialName, 'courge')) {
            return [
                'recipe_name' => 'Velouté ou Purée de Légumes',
                'justification' => 'Valorise les légumes en fin de cycle et crée un ingrédient polyvalent pour transformations ultérieures.',
                'ingredients_to_prioritize' => [$urgentMaterial['name']],
                'urgency' => $urgency,
                'estimated_quantity' => round($urgentMaterial['quantity'] * 0.85) . ' kg de velouté',
                'processing_time_hours' => 3,
                'estimated_shelf_life_days' => 60,
            ];
        }

        // Default fallback
        return [
            'recipe_name' => 'Produit Transformé Premium',
            'justification' => 'Maximise la valorisation de vos matières premières avec une transformation de qualité.',
            'ingredients_to_prioritize' => [$urgentMaterial['name']],
            'urgency' => $urgency,
            'estimated_quantity' => 'À calculer',
            'processing_time_hours' => 4,
            'estimated_shelf_life_days' => 90,
        ];
    }

    /**
     * Get AI recommendation to prevent waste
     * Analyzes expiring raw materials and suggests recipes
     */
    public function getWastePreventionRecommendation(User $processor): ?array
    {
        // Find materials expiring soon (< 7 days)
        // Since RawMaterialReception doesn't have expiry_date, we get it from the related Harvest
        $expiringMaterials = RawMaterialReception::where('processor_id', $processor->id)
            ->where('status', 'received')
            ->with('harvest')
            ->get()
            ->filter(function ($reception) {
                if (!$reception->harvest) {
                    return false;
                }
                
                // Get expiry date from harvest (if available)
                $expiryDate = $reception->harvest->harvest_date?->addDays(30); // Assume 30 days shelf life if not specified
                
                if (!$expiryDate) {
                    return false;
                }
                
                $daysUntilExpiry = now()->diffInDays($expiryDate);
                return $daysUntilExpiry <= 7 && $daysUntilExpiry > 0;
            })
            ->values();

        if ($expiringMaterials->isEmpty()) {
            return null;
        }

        // Get all recipes with items
        $recipes = Recipe::where('processor_id', $processor->id)
            ->where('is_active', true)
            ->with('items')
            ->get();

        $bestRecipes = [];

        foreach ($recipes as $recipe) {
            if ($recipe->items->isEmpty()) {
                continue;
            }

            $matchingIngredients = 0;
            $totalIngredients = $recipe->items->count();
            $totalExpiringSoon = 0;

            foreach ($recipe->items as $item) {
                $expiringMatch = $expiringMaterials->first(function ($mat) use ($item) {
                    return $mat->harvest && strtolower($mat->harvest->product_name) === strtolower($item->raw_material_name);
                });

                if ($expiringMatch) {
                    $matchingIngredients++;
                    $totalExpiringSoon += $expiringMatch->quantity_received;
                }
            }

            if ($matchingIngredients > 0) {
                $matchPercentage = ($matchingIngredients / $totalIngredients) * 100;
                $bestRecipes[] = [
                    'recipe' => $recipe,
                    'match_percentage' => $matchPercentage,
                    'matching_ingredients_count' => $matchingIngredients,
                    'total_available' => $totalExpiringSoon,
                ];
            }
        }

        if (empty($bestRecipes)) {
            return null;
        }

        // Sort by match percentage
        usort($bestRecipes, fn ($a, $b) => $b['match_percentage'] <=> $a['match_percentage']);
        $topRecipe = $bestRecipes[0];

        // Find expiring materials used in this recipe
        $expiringInRecipe = [];
        foreach ($topRecipe['recipe']->items as $item) {
            $expiringMatch = $expiringMaterials->first(function ($mat) use ($item) {
                return $mat->harvest && strtolower($mat->harvest->product_name) === strtolower($item->raw_material_name);
            });

            if ($expiringMatch) {
                $expiryDate = $expiringMatch->harvest->harvest_date?->addDays(30);
                $daysUntilExpiry = $expiryDate ? now()->diffInDays($expiryDate) : 0;
                
                $expiringInRecipe[] = [
                    'name' => $expiringMatch->harvest->product_name,
                    'quantity' => $expiringMatch->quantity_received,
                    'unit' => 'kg',
                    'days_until_expiry' => $daysUntilExpiry,
                ];
            }
        }

        if (empty($expiringInRecipe)) {
            return null;
        }

        // Sort by urgency
        usort($expiringInRecipe, fn ($a, $b) => $a['days_until_expiry'] <=> $b['days_until_expiry']);

        return [
            'recipe_id' => $topRecipe['recipe']->id,
            'recipe_name' => $topRecipe['recipe']->name,
            'match_percentage' => round($topRecipe['match_percentage']),
            'matching_ingredients_count' => $topRecipe['matching_ingredients_count'],
            'total_ingredients' => count($topRecipe['recipe']->items),
            'expiring_materials' => $expiringInRecipe,
            'urgency' => $expiringInRecipe[0]['days_until_expiry'] <= 3 ? 'critical' : 'high',
        ];
    }

    /**
     * Calculate eco-score based on multiple factors
     */
    public function calculateEcoScore(Recipe $recipe, ProductionLot $lot = null): string
    {
        $score = 0;

        // Factor 1: Number of local ingredients (assume all are local for now)
        $localIngredientRatio = $recipe->items->count() > 0 ? 1.0 : 0.5;
        $score += $localIngredientRatio * 30;

        // Factor 2: Batch size (larger batches = more efficient)
        if ($lot) {
            $score += ($lot->quantity_produced > 100) ? 25 : 15;
        } else {
            $score += 20;
        }

        // Factor 3: Ingredient diversity (less = more focused)
        $diversityScore = 100 - (($recipe->items->count() / 10) * 100);
        $score += ($diversityScore / 100) * 25;

        // Factor 4: Processing efficiency (based on time)
        $timeScore = max(0, 100 - ($recipe->preparation_time_minutes / 2));
        $score += ($timeScore / 100) * 20;

        $avgScore = $score / 100;

        if ($avgScore >= 75) {
            return 'A';
        } elseif ($avgScore >= 50) {
            return 'B';
        } else {
            return 'C';
        }
    }
}
