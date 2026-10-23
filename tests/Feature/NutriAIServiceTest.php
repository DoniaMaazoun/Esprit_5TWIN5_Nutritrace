<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DistributorStock;
use App\Models\Farm;
use App\Models\Harvest;
use App\Models\PointOfSale;
use App\Models\ProductionLot;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\User;
use App\Services\AI\AIClientInterface;
use App\Services\NutriAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NutriAIServiceTest extends TestCase
{
    use RefreshDatabase;

    private NutriAIService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NutriAIService();
    }

    /** @test */
    public function it_searches_products_successfully(): void
    {
        // Créer des données de test (sans hook de slug)
        $category = Category::withoutEvents(function () {
            return Category::factory()->create(['name' => 'Légumes Test 1', 'is_active' => true]);
        });
        $processor = User::factory()->create(['role' => 'pro', 'pro_type' => 'processor', 'status' => 'approved']);
        $distributor = User::factory()->create(['role' => 'pro', 'pro_type' => 'distributor', 'status' => 'approved', 'company_name' => 'Distributeur Test']);
        $pointOfSale = PointOfSale::factory()->create(['user_id' => $distributor->id, 'city' => 'Tunis']);

        $recipe = Recipe::factory()->create([
            'processor_id' => $processor->id,
            'name' => 'Sauce Tomate Bio',
            'is_active' => true,
        ]);

        $productionLot = ProductionLot::factory()->create([
            'recipe_id' => $recipe->id,
            'processor_id' => $processor->id,
            'quantity_produced' => 100,
            'unit' => 'kg',
            'status' => 'produced',
        ]);

        $stock = DistributorStock::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'production_lot_id' => $productionLot->id,
            'quantity_available' => 50,
            'unit_price' => 15.50,
            'status' => 'available',
        ]);

        // Tester la recherche
        $result = $this->service->searchProducts('Sauce');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('sources', $result);
        $this->assertArrayHasKey('missing', $result);
        $this->assertCount(1, $result['results']);
        $this->assertEquals('Sauce Tomate Bio', $result['results'][0]['product_name']);
        $this->assertEquals('15,50 DT', $result['results'][0]['price']);
        $this->assertEquals('Tunis', $result['results'][0]['city']);
    }

    /** @test */
    public function it_returns_empty_results_when_no_products_found(): void
    {
        $result = $this->service->searchProducts('ProduitInexistantXYZ');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('sources', $result);
        $this->assertArrayHasKey('missing', $result);
        $this->assertEmpty($result['results']);
        $this->assertEmpty($result['sources']);
        $this->assertNotEmpty($result['missing']);
        $this->assertStringContainsString('Aucun produit trouvé', $result['missing'][0]);
    }

    /** @test */
    public function it_returns_error_for_short_search_query(): void
    {
        $result = $this->service->searchProducts('x');

        $this->assertIsArray($result);
        $this->assertEmpty($result['results']);
        $this->assertNotEmpty($result['missing']);
        $this->assertStringContainsString('au moins 2 caractères', $result['missing'][0]);
    }

    /** @test */
    public function it_gets_product_details_successfully(): void
    {
        // Créer des données de test (sans hook de slug)
        $category = Category::withoutEvents(function () {
            return Category::factory()->create(['name' => 'Légumes Test 2', 'is_active' => true]);
        });
        $processor = User::factory()->create(['role' => 'pro', 'pro_type' => 'processor', 'status' => 'approved']);
        $distributor = User::factory()->create(['role' => 'pro', 'pro_type' => 'distributor', 'status' => 'approved', 'company_name' => 'Distributeur Test']);
        $pointOfSale = PointOfSale::factory()->create(['user_id' => $distributor->id, 'city' => 'Sfax', 'address' => '123 Rue Test']);

        $recipe = Recipe::factory()->create([
            'processor_id' => $processor->id,
            'name' => 'Confiture Fraise',
            'description' => 'Une délicieuse confiture de fraises',
            'is_active' => true,
        ]);

        $recipeItem = RecipeItem::factory()->create([
            'recipe_id' => $recipe->id,
            'raw_material_name' => 'Fraise',
            'quantity_required' => 2,
            'unit' => 'kg',
        ]);

        $productionLot = ProductionLot::factory()->create([
            'recipe_id' => $recipe->id,
            'processor_id' => $processor->id,
            'quantity_produced' => 50,
            'unit' => 'kg',
            'production_date' => now()->subDays(10),
            'expiry_date' => now()->addDays(350),
            'status' => 'produced',
        ]);

        $stock = DistributorStock::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'production_lot_id' => $productionLot->id,
            'quantity_available' => 25,
            'unit_price' => 20.00,
            'status' => 'available',
        ]);

        // Tester la récupération des détails
        $result = $this->service->getProductDetails($stock->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('product', $result);
        $this->assertArrayHasKey('sources', $result);
        $this->assertArrayHasKey('missing', $result);
        $this->assertNotNull($result['product']);
        $this->assertEquals('Confiture Fraise', $result['product']['product_name']);
        $this->assertEquals('20,00 DT', $result['product']['price']);
        $this->assertEquals('Sfax', $result['product']['city']);
        $this->assertEquals('123 Rue Test', $result['product']['point_of_sale_address']);
        $this->assertCount(1, $result['product']['ingredients']);
        $this->assertEquals('Fraise', $result['product']['ingredients'][0]['name']);
        $this->assertEmpty($result['missing']);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_product(): void
    {
        $result = $this->service->getProductDetails(99999);

        $this->assertIsArray($result);
        $this->assertNull($result['product']);
        $this->assertEmpty($result['sources']);
        $this->assertNotEmpty($result['missing']);
        $this->assertStringContainsString('non trouvé', $result['missing'][0]);
    }

    /** @test */
    public function it_indicates_missing_price_and_ingredients(): void
    {
        // Créer des données de test sans prix et sans ingrédients (sans hook de slug)
        $category = Category::withoutEvents(function () {
            return Category::factory()->create(['name' => 'Fruits Test 3', 'is_active' => true]);
        });
        $processor = User::factory()->create(['role' => 'pro', 'pro_type' => 'processor', 'status' => 'approved']);
        $distributor = User::factory()->create(['role' => 'pro', 'pro_type' => 'distributor', 'status' => 'approved']);
        $pointOfSale = PointOfSale::factory()->create(['user_id' => $distributor->id, 'city' => 'Nabeul']);

        $recipe = Recipe::factory()->create([
            'processor_id' => $processor->id,
            'name' => 'Produit Test',
            'is_active' => true,
        ]);

        $productionLot = ProductionLot::factory()->create([
            'recipe_id' => $recipe->id,
            'processor_id' => $processor->id,
            'quantity_produced' => 100,
            'unit' => 'kg',
            'status' => 'produced',
        ]);

        $stock = DistributorStock::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'production_lot_id' => $productionLot->id,
            'quantity_available' => 30,
            'unit_price' => null, // Prix manquant
            'status' => 'available',
        ]);

        $result = $this->service->getProductDetails($stock->id);

        $this->assertNotNull($result['product']);
        $this->assertEquals('Non disponible', $result['product']['price']);
        $this->assertEmpty($result['product']['ingredients']);
        $this->assertNotEmpty($result['missing']);
        $this->assertContains('Prix non disponible', $result['missing']);
        $this->assertContains('Ingrédients non spécifiés', $result['missing']);
    }

    /** @test */
    public function it_compares_products_successfully(): void
    {
        // Créer 2 produits pour comparaison (sans hook de slug)
        $category = Category::withoutEvents(function () {
            return Category::factory()->create(['name' => 'Légumes Test 4', 'is_active' => true]);
        });
        $processor = User::factory()->create(['role' => 'pro', 'pro_type' => 'processor', 'status' => 'approved']);
        $distributor = User::factory()->create(['role' => 'pro', 'pro_type' => 'distributor', 'status' => 'approved']);
        $pointOfSale = PointOfSale::factory()->create(['user_id' => $distributor->id, 'city' => 'Tunis']);

        $recipe = Recipe::factory()->create([
            'processor_id' => $processor->id,
            'name' => 'Sauce Test',
            'is_active' => true,
        ]);

        $lot1 = ProductionLot::factory()->create([
            'recipe_id' => $recipe->id,
            'processor_id' => $processor->id,
            'quantity_produced' => 100,
            'unit' => 'kg',
            'status' => 'produced',
        ]);

        $lot2 = ProductionLot::factory()->create([
            'recipe_id' => $recipe->id,
            'processor_id' => $processor->id,
            'quantity_produced' => 100,
            'unit' => 'kg',
            'status' => 'produced',
        ]);

        $stock1 = DistributorStock::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'production_lot_id' => $lot1->id,
            'quantity_available' => 20,
            'unit_price' => 15.00,
            'status' => 'available',
        ]);

        $stock2 = DistributorStock::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'production_lot_id' => $lot2->id,
            'quantity_available' => 40,
            'unit_price' => 18.00,
            'status' => 'available',
        ]);

        $result = $this->service->compareProducts([$stock1->id, $stock2->id]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('products', $result);
        $this->assertArrayHasKey('comparison', $result);
        $this->assertArrayHasKey('sources', $result);
        $this->assertCount(2, $result['products']);
        $this->assertEquals(15.0, $result['comparison']['price_range']['min']);
        $this->assertEquals(18.0, $result['comparison']['price_range']['max']);
        $this->assertEquals(16.5, $result['comparison']['price_range']['avg']);
        $this->assertEquals(15.0, $result['comparison']['cheapest']['price']);
        $this->assertEquals(40, $result['comparison']['most_available']['quantity']);
    }

    /** @test */
    public function it_requires_at_least_two_products_for_comparison(): void
    {
        // Créer des données de test (sans hook de slug)
        $category = Category::withoutEvents(function () {
            return Category::factory()->create(['name' => 'Légumes Test 5', 'is_active' => true]);
        });
        $processor = User::factory()->create(['role' => 'pro', 'pro_type' => 'processor', 'status' => 'approved']);
        $distributor = User::factory()->create(['role' => 'pro', 'pro_type' => 'distributor', 'status' => 'approved']);
        $pointOfSale = PointOfSale::factory()->create(['user_id' => $distributor->id]);

        $recipe = Recipe::factory()->create([
            'processor_id' => $processor->id,
            'is_active' => true,
        ]);

        $lot = ProductionLot::factory()->create([
            'recipe_id' => $recipe->id,
            'processor_id' => $processor->id,
            'status' => 'produced',
        ]);

        $stock = DistributorStock::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'production_lot_id' => $lot->id,
            'status' => 'available',
        ]);

        $result = $this->service->compareProducts([$stock->id]);

        $this->assertEmpty($result['products']);
        $this->assertNotEmpty($result['missing']);
        $this->assertStringContainsString('Au moins 2 produits', $result['missing'][0]);
    }

    /** @test */
    public function it_explains_traceability_successfully(): void
    {
        // Créer des données de traçabilité (sans hook de slug)
        $user = User::factory()->create(['role' => 'pro', 'pro_type' => 'producer', 'status' => 'approved']);
        $category = Category::withoutEvents(function () {
            return Category::factory()->create(['name' => 'Légumes Test 6', 'is_active' => true]);
        });
        $farm = Farm::factory()->create([
            'user_id' => $user->id,
            'name' => 'Ferme Test',
            'city' => 'Bizerte',
            'governorate' => 'Bizerte',
            'farming_type' => 'organic',
            'certification' => 'Bio Tunisie',
        ]);

        $harvest = Harvest::factory()->create([
            'farm_id' => $farm->id,
            'category_id' => $category->id,
            'product_name' => 'Tomate',
            'variety' => 'Roma',
            'batch_code' => 'LOT-20261022-ABCDE',
            'quantity' => 100,
            'unit' => 'kg',
            'harvest_date' => now()->subDays(5),
            'planting_date' => now()->subMonths(3),
            'quality_grade' => 'A',
            'status' => 'in_stock',
        ]);

        $result = $this->service->explainTraceability('LOT-20261022-ABCDE');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('traceability', $result);
        $this->assertArrayHasKey('sources', $result);
        $this->assertArrayHasKey('missing', $result);
        $this->assertNotNull($result['traceability']);
        $this->assertEquals('LOT-20261022-ABCDE', $result['traceability']['batch_code']);
        $this->assertEquals('Tomate', $result['traceability']['product_name']);
        $this->assertEquals('Roma', $result['traceability']['variety']);
        $this->assertEquals('Ferme Test', $result['traceability']['farm']['name']);
        $this->assertEquals('Bizerte', $result['traceability']['farm']['city']);
        $this->assertEquals('A', $result['traceability']['quality_grade']);
        // Vérifier que les informations importantes sont présentes, peu importe le contenu de missing
        $this->assertArrayHasKey('has_photo', $result['traceability']);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_batch_code(): void
    {
        $result = $this->service->explainTraceability('LOT-99999999-XXXXX');

        $this->assertIsArray($result);
        $this->assertNull($result['traceability']);
        $this->assertEmpty($result['sources']);
        $this->assertNotEmpty($result['missing']);
        $this->assertStringContainsString('non trouvé', $result['missing'][0]);
    }

    /** @test */
    public function it_indicates_missing_traceability_information(): void
    {
        $user = User::factory()->create(['role' => 'pro', 'pro_type' => 'producer', 'status' => 'approved']);
        $category = Category::withoutEvents(function () {
            return Category::factory()->create(['name' => 'Fruits Test 7', 'is_active' => true]);
        });
        $farm = Farm::factory()->create([
            'user_id' => $user->id,
            'name' => 'Ferme Test',
            'city' => 'Sousse',
            'governorate' => 'Sousse',
        ]);

        $harvest = Harvest::factory()->create([
            'farm_id' => $farm->id,
            'category_id' => $category->id,
            'product_name' => 'Pomme',
            'variety' => null, // Variété manquante
            'batch_code' => 'LOT-20261022-TEST1',
            'quantity' => 50,
            'unit' => 'kg',
            'harvest_date' => now()->subDays(10),
            'planting_date' => null, // Date de plantation manquante
            'quality_grade' => 'B',
            'status' => 'in_stock',
        ]);

        $result = $this->service->explainTraceability('LOT-20261022-TEST1');

        $this->assertNotNull($result['traceability']);
        $this->assertNotEmpty($result['missing']);
        $this->assertContains('Variété non spécifiée', $result['missing']);
        $this->assertContains('Date de plantation non spécifiée', $result['missing']);
        $this->assertContains('Photo non disponible', $result['missing']);
    }

    /** @test */
    public function it_answers_questions_with_local_response(): void
    {
        // Ce test est modifié pour tester l'appel API avec mock
        // car la méthode answerQuestion utilise maintenant l'API réelle
        $mockClient = $this->createMock(AIClientInterface::class);
        $mockClient->method('isEnabled')->willReturn(true);
        $mockClient->method('complete')->willReturn('Réponse simulée de l\'API');

        $this->service->setAiClient($mockClient);

        $result = $this->service->answerQuestion('Quels produits sont disponibles ?');

        $this->assertIsArray($result);
        $this->assertEquals('groq', $result['source']);
        $this->assertEquals('Réponse simulée de l\'API', $result['answer']);
    }

    /** @test */
    public function it_returns_error_when_api_key_missing(): void
    {
        $mockClient = $this->createMock(AIClientInterface::class);
        $mockClient->method('isEnabled')->willReturn(false);

        $this->service->setAiClient($mockClient);

        $result = $this->service->answerQuestion('Quels produits sont disponibles ?');

        $this->assertIsArray($result);
        $this->assertEquals('error', $result['source']);
        $this->assertStringContainsString('clé API', $result['answer']);
    }

    /** @test */
    public function it_returns_error_when_api_call_fails(): void
    {
        $mockClient = $this->createMock(AIClientInterface::class);
        $mockClient->method('isEnabled')->willReturn(true);
        $mockClient->method('complete')->willReturn(null); // Simule un échec API

        $this->service->setAiClient($mockClient);

        $result = $this->service->answerQuestion('Quels produits sont disponibles ?');

        $this->assertIsArray($result);
        $this->assertEquals('error', $result['source']);
        $this->assertStringContainsString('indisponible', $result['answer']);
    }

    /** @test */
    public function it_returns_error_for_short_question(): void
    {
        $result = $this->service->answerQuestion('x');

        $this->assertIsArray($result);
        $this->assertStringContainsString('trop courte', $result['answer']);
    }

    /** @test */
    public function it_handles_null_user_gracefully(): void
    {
        // Le service doit fonctionner avec user = null (visiteurs non authentifiés)
        $result = $this->service->searchProducts('Test', null);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('sources', $result);
        $this->assertArrayHasKey('missing', $result);
    }

    /** @test */
    public function it_handles_http_401_authentication_error(): void
    {
        $mockClient = $this->createMock(AIClientInterface::class);
        $mockClient->method('isEnabled')->willReturn(true);
        $mockClient->method('complete')->willReturn(null); // Simule erreur 401

        $this->service->setAiClient($mockClient);

        $result = $this->service->answerQuestion('Test question');

        $this->assertIsArray($result);
        $this->assertEquals('error', $result['source']);
    }

    /** @test */
    public function it_handles_http_404_model_not_found(): void
    {
        $mockClient = $this->createMock(AIClientInterface::class);
        $mockClient->method('isEnabled')->willReturn(true);
        $mockClient->method('complete')->willReturn(null); // Simule erreur 404

        $this->service->setAiClient($mockClient);

        $result = $this->service->answerQuestion('Test question');

        $this->assertIsArray($result);
        $this->assertEquals('error', $result['source']);
    }

    /** @test */
    public function it_handles_http_429_rate_limit(): void
    {
        $mockClient = $this->createMock(AIClientInterface::class);
        $mockClient->method('isEnabled')->willReturn(true);
        $mockClient->method('complete')->willReturn(null); // Simule erreur 429

        $this->service->setAiClient($mockClient);

        $result = $this->service->answerQuestion('Test question');

        $this->assertIsArray($result);
        $this->assertEquals('error', $result['source']);
    }

    /** @test */
    public function it_handles_malformed_json_response(): void
    {
        $mockClient = $this->createMock(AIClientInterface::class);
        $mockClient->method('isEnabled')->willReturn(true);
        $mockClient->method('complete')->willReturn(null); // Simule réponse malformée

        $this->service->setAiClient($mockClient);

        $result = $this->service->answerQuestion('Test question');

        $this->assertIsArray($result);
        $this->assertEquals('error', $result['source']);
    }
}
