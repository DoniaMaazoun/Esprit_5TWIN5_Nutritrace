<?php

namespace Tests\Feature;

use App\Models\Harvest;
use App\Models\PointOfSale;
use App\Models\Shipment;
use App\Models\User;
use App\Services\TransportRecommender;
use Database\Seeders\TransportModeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\BuildsProductionChain;
use Tests\TestCase;

/**
 * Module 3 - Recommandation IA du mode de transport : nature du produit, plans multimodaux,
 * conseils, et mode de secours sans clé API
 */
class TransportRecommendationTest extends TestCase
{
    use BuildsProductionChain, RefreshDatabase;

    private User $distributor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TransportModeSeeder::class);
        config(['services.ai.key' => null, 'services.ai.base_url' => 'https://ai.test/v1']);
        Http::preventStrayRequests();

        $this->distributor = User::factory()->create([
            'role' => 'pro',
            'pro_type' => 'distributor',
            'company_name' => 'Distrib Test',
            'status' => 'approved',
        ]);
    }

    private function recommender(): TransportRecommender
    {
        return app(TransportRecommender::class);
    }

    /**
     * Expédition reliée, si une durée de conservation est donnée, à un lot de production du module 2
     * (produit fini fabriqué il y a $producedDaysAgo jours, DLC = production + $shelfDays jours)
     */
    private function shipment(string $product, ?int $shelfDays = null, int $producedDaysAgo = 1, array $attributes = [], ?string $categorySlug = null): Shipment
    {
        $lotId = null;
        if ($shelfDays !== null) {
            $producedOn = now()->subDays($producedDaysAgo)->startOfDay();
            $lotId = $this->productionLot($product, "Matière {$product}", [
                'production_date' => $producedOn->toDateString(),
                'expiry_date' => $producedOn->copy()->addDays($shelfDays)->toDateString(),
            ], categorySlug: $categorySlug)->id;
        }

        return Shipment::create($attributes + [
            'reference' => Shipment::nextReference(),
            'user_id' => $this->distributor->id,
            'production_lot_id' => $lotId,
            'product_name' => $product,
            'weight_kg' => 2000,
            'origin_address' => 'Nabeul',
            'status' => 'planned',
        ])->load('productionLot.recipe.items', 'productionLot.processor', 'pointOfSale');
    }

    private function aiMessage(string $content): array
    {
        return ['choices' => [['message' => ['content' => $content]]]];
    }

    public function test_products_are_classified_by_name_then_category(): void
    {
        $recommender = $this->recommender();

        $this->assertSame('perishable', $recommender->classifyProduct('Tomates cerises'));
        $this->assertSame('perishable', $recommender->classifyProduct('Fraises'));
        $this->assertSame('durable', $recommender->classifyProduct("Huile d'olive extra vierge"));
        $this->assertSame('semi', $recommender->classifyProduct('Oranges Maltaises'));
        $this->assertSame('durable', $recommender->classifyProduct('Deglet Nour', 'dattes'));
        $this->assertSame('perishable', $recommender->classifyProduct('Variété locale', 'legumes'));
        $this->assertSame('unknown', $recommender->classifyProduct('Colis divers'));
    }

    public function test_processed_products_are_classified_by_shelf_life(): void
    {
        $recommender = $this->recommender();

        $this->assertSame('perishable', $recommender->classifyShelfLife(5));
        $this->assertSame('semi', $recommender->classifyShelfLife(30));
        $this->assertSame('durable', $recommender->classifyShelfLife(180));
    }

    public function test_product_nature_comes_from_the_production_lot_and_keeps_the_harvest(): void
    {
        // « Sauce tomate » contient le mot-clé « tomate » (périssable), mais le lot se conserve 6 mois
        $shipment = $this->shipment('Sauce tomate en bocal', 180, 2, [], 'legumes');
        $harvest = Harvest::firstOrFail();

        $result = $this->recommender()->recommend(500, 2000, null, $shipment);
        $product = $result['product'];

        $this->assertSame('durable', $product['class']);
        $this->assertSame('expiry', $product['class_by']);
        $this->assertSame($shipment->productionLot->qr_code_data, $product['lot_code']);
        $this->assertSame('Usine Test', $product['processor']);
        $this->assertSame(2, $product['age_days']);
        $this->assertSame(178, $product['days_to_expiry']);
        $this->assertNull($result['max_hours']);
        $this->assertSame('plan_rail', $result['recommended']['key']);
        // L'origine (récolte du module 1) est gardée
        $this->assertCount(1, $product['origins']);
        $this->assertSame($harvest->batch_code, $product['origins'][0]['batch_code']);
        $this->assertSame($harvest->harvest_date->format('d/m/Y'), $product['origins'][0]['harvest_date']);
        $this->assertNotNull($product['category']);
    }

    public function test_delay_is_capped_by_the_days_left_before_expiry(): void
    {
        // Lot qui se conserve un an mais périme dans 2 jours
        $shipment = $this->shipment("Huile d'olive", 365, 363);

        $result = $this->recommender()->recommend(500, 2000, null, $shipment);

        $this->assertSame('durable', $result['product']['class']);
        $this->assertSame(2, $result['product']['days_to_expiry']);
        $this->assertEquals(48, $result['max_hours']);
        // Le plan ferroviaire (≈ 26 h) reste possible en 48 h
        $this->assertSame('plan_rail', $result['recommended']['key']);
    }

    public function test_expired_lot_is_flagged(): void
    {
        $shipment = $this->shipment('Yaourt nature', 7, 10);

        $result = $this->recommender()->recommend(100, 2000, null, $shipment);

        $this->assertTrue($result['product']['expired']);
        $this->assertStringContainsString('a dépassé sa date limite', implode(' ', $result['tips']));
    }

    public function test_perishable_product_without_api_key_prioritises_speed_and_cold_chain(): void
    {
        $shipment = $this->shipment('Tomates', 5);

        $result = $this->recommender()->recommend(500, 2000, null, $shipment);
        $options = collect($result['options'])->keyBy('key');

        $this->assertSame('local', $result['source']);
        $this->assertSame('perishable', $result['product']['class']);
        $this->assertSame('expiry', $result['product']['class_by']);
        $this->assertTrue($result['product']['refrigerated']);
        $this->assertSame(1, $result['product']['age_days']);
        $this->assertEquals(24, $result['max_hours']);
        // Le plan camion -> train (≈ 26 h) dépasse le délai du produit périssable
        $this->assertFalse($options['plan_rail']['feasible']);
        // Poids lourd : 500 km x 2 t x 0,085 = 85 kg, en ≈ 10,3 h
        $this->assertSame('mode_truck_heavy', $result['recommended']['key']);
        $this->assertEqualsWithDelta(85.0, $result['recommended']['co2_kg'], 0.001);
        $this->assertStringContainsString('périssable', $result['explanation']);
        $this->assertStringContainsString('frigorifique', implode(' ', $result['tips']));
        Http::assertNothingSent();
    }

    public function test_durable_product_without_api_key_gets_the_greenest_multimodal_plan(): void
    {
        $shipment = $this->shipment("Huile d'olive", 365);

        $result = $this->recommender()->recommend(500, 2000, null, $shipment);
        $plan = $result['recommended'];

        $this->assertSame('durable', $result['product']['class']);
        $this->assertNull($result['max_hours']);
        $this->assertSame('plan_rail', $plan['key']);
        $this->assertSame('plan', $plan['type']);
        // Camion porteur 30 km (12 kg) -> train 450 km (4,68 kg) -> camion porteur 20 km (8 kg)
        $this->assertSame(['truck_light', 'rail', 'truck_light'], array_column($plan['legs'], 'code'));
        $this->assertSame([30.0, 450.0, 20.0], array_column($plan['legs'], 'distance_km'));
        $this->assertEqualsWithDelta(24.68, $plan['co2_kg'], 0.001);
        $this->assertSame('truck_light', $plan['code']);
        // Gain par rapport au camion porteur sur 500 km (200 kg)
        $this->assertSame('mode_truck_light', $result['reference']['key']);
        $this->assertEqualsWithDelta(175.32, $result['saving_kg'], 0.001);
        $this->assertEqualsWithDelta(87.7, $result['saving_percent'], 0.1);
        $this->assertStringContainsString('se conserve bien', $result['explanation']);
    }

    public function test_light_load_uses_a_van_for_the_last_mile(): void
    {
        $shipment = $this->shipment('Dattes', 365, 10, ['weight_kg' => 1000]);

        $plan = $this->recommender()->recommend(600, 1000, null, $shipment)['recommended'];

        $this->assertSame(['truck_light', 'rail', 'van'], array_column($plan['legs'], 'code'));
        // 30 x 1 x 0,2 + 550 x 1 x 0,0052 + 20 x 1 x 0,6
        $this->assertEqualsWithDelta(20.86, $plan['co2_kg'], 0.001);
    }

    public function test_tip_quantifies_the_saving_of_a_longer_delay(): void
    {
        $shipment = $this->shipment("Huile d'olive", 365);

        $result = $this->recommender()->recommend(500, 2000, 12, $shipment);

        $this->assertSame('mode_truck_heavy', $result['recommended']['key']);
        // Plan ferroviaire ≈ 26,1 h : 85 - 24,68 = 60,32 kg économisés
        $this->assertSame("Avec un délai de 27 h au lieu de 12 h, l'option Camion porteur → Train de fret → Camion porteur serait possible et économiserait 60,3 kg CO2e (71 %).", $result['tips'][0]);
    }

    public function test_tip_suggests_grouping_shipments_to_the_same_point_of_sale(): void
    {
        $pointOfSale = PointOfSale::create([
            'user_id' => $this->distributor->id, 'name' => 'Épicerie Bio', 'type' => 'store', 'address' => 'Rue de Marseille', 'city' => 'Tunis',
        ]);
        $other = $this->shipment('Colis divers', null, 1, ['point_of_sale_id' => $pointOfSale->id]);
        $shipment = $this->shipment('Colis divers', null, 1, ['point_of_sale_id' => $pointOfSale->id]);

        $tips = $this->recommender()->recommend(150, 2000, null, $shipment)['tips'];

        $this->assertContains("Regroupez avec {$other->reference} vers Épicerie Bio : un seul trajet au lieu de 2.", $tips);
    }

    public function test_fastest_option_is_proposed_when_none_meets_the_delay(): void
    {
        $result = $this->recommender()->recommend(2000, 500, 3);

        $this->assertSame('mode_air', $result['recommended']['key']);
        $this->assertStringContainsString('Aucune option ne respecte le délai', $result['explanation']);
    }

    public function test_ai_choice_explanation_and_tips_are_used_when_valid(): void
    {
        config(['services.ai.key' => 'test-key']);
        Http::fake(['ai.test/*' => Http::response($this->aiMessage(
            "```json\n{\"choice\": \"plan_rail\", \"explanation\": \"L'huile se conserve : le train réduit fortement le CO2.\", "
            ."\"tips\": [\"Planifiez l'expédition 2 jours à l'avance.\", \"\"]}\n```"
        ))]);
        $shipment = $this->shipment("Huile d'olive", 365);

        $result = $this->recommender()->recommend(500, 2000, null, $shipment);

        $this->assertSame('ai', $result['source']);
        $this->assertSame('plan_rail', $result['recommended']['key']);
        $this->assertSame("L'huile se conserve : le train réduit fortement le CO2.", $result['explanation']);
        $this->assertSame(["Planifiez l'expédition 2 jours à l'avance."], $result['tips']);
        // Les chiffres restent calculés localement
        $this->assertEqualsWithDelta(24.68, $result['recommended']['co2_kg'], 0.001);
        // L'IA reçoit la nature du produit et les plans multimodaux
        Http::assertSent(fn (Request $request) => str_contains($request['messages'][1]['content'], 'Se conserve longtemps')
            && str_contains($request['messages'][1]['content'], '[plan_rail]'));
    }

    public function test_invalid_ai_answer_falls_back_to_local_rules(): void
    {
        config(['services.ai.key' => 'test-key']);
        Http::fake(['ai.test/*' => Http::sequence()
            // Plan trop lent pour des tomates : réponse ignorée
            ->push($this->aiMessage('{"choice": "plan_rail", "explanation": "Prenez le train."}'))
            // Nouvelle tentative, sans JSON
            ->push($this->aiMessage('Je recommande le train.'))]);
        $tomatoes = $this->shipment('Tomates', 5);

        $result = $this->recommender()->recommend(500, 2000, null, $tomatoes);
        $this->assertSame('local', $result['source']);
        $this->assertSame('mode_truck_heavy', $result['recommended']['key']);
        // Une seule nouvelle tentative
        Http::assertSentCount(2);
    }

    public function test_truncated_ai_answer_is_retried_once(): void
    {
        config(['services.ai.key' => 'test-key']);
        Http::fake(['ai.test/*' => Http::sequence()
            // Réponse coupée par la limite de tokens
            ->push($this->aiMessage('{"choice": "plan_rail", "explanation": "Les olives se conservent", "tips": ['))
            // Clé entre crochets (format renvoyé par certains modèles) et conseil au chiffre inventé
            ->push($this->aiMessage('{"choice": "[plan_rail]", "explanation": "Le train est le moins émetteur.", '
                .'"tips": ["Le plan rail émet 24,68 kg au lieu de 200 kg.", "Vous économiserez 0,012 kg par kg transporté."]}'))]);

        $result = $this->recommender()->recommend(500, 2000, null, $this->shipment("Huile d'olive", 365));

        $this->assertSame('ai', $result['source']);
        $this->assertSame('plan_rail', $result['recommended']['key']);
        // Seul le conseil dont les chiffres viennent du calcul local est gardé
        $this->assertSame(['Le plan rail émet 24,68 kg au lieu de 200 kg.'], $result['tips']);
        Http::assertSentCount(2);
    }

    public function test_network_failure_is_not_retried(): void
    {
        config(['services.ai.key' => 'test-key']);
        $calls = 0;
        Http::fake(['ai.test/*' => function () use (&$calls) {
            $calls++;
            throw new ConnectionException('cURL error 28: Resolving timed out');
        }]);

        $result = $this->recommender()->recommend(500, 2000);

        $this->assertSame('local', $result['source']);
        $this->assertSame(1, $calls);
    }

    public function test_api_error_falls_back_to_local_rules(): void
    {
        config(['services.ai.key' => 'test-key']);
        Http::fake(['ai.test/*' => Http::response('Server error', 500)]);

        $result = $this->recommender()->recommend(500, 2000, null, $this->shipment("Huile d'olive", 365));

        $this->assertSame('local', $result['source']);
        $this->assertSame('plan_rail', $result['recommended']['key']);
        $this->assertSame('erreur du service IA', $result['fallback_reason']);
    }

    public function test_fallback_reason_is_returned_and_logged(): void
    {
        Log::spy();

        $result = $this->recommender()->recommend(500, 2000);
        $this->assertSame('local', $result['source']);
        $this->assertSame('pas de clé', $result['fallback_reason']);
        $this->assertNull($result['provider']);

        config(['services.ai.key' => 'test-key']);
        Http::fake(['ai.test/*' => fn () => throw new ConnectionException('cURL error 28: Resolving timed out')]);
        $this->assertSame('IA injoignable', $this->recommender()->recommend(500, 2000)['fallback_reason']);

        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context) => str_contains($message, 'mode secours : IA injoignable')
            && str_contains($context['detail'], 'Resolving timed out'))->once();
        Log::shouldHaveReceived('warning')->withArgs(fn ($message) => str_contains($message, 'mode secours : pas de clé'))->once();
    }

    public function test_invalid_answers_are_logged_with_the_model_text(): void
    {
        Log::spy();
        config(['services.ai.key' => 'test-key']);
        Http::fake(['ai.test/*' => Http::response($this->aiMessage('Je recommande le train.'))]);

        $result = $this->recommender()->recommend(500, 2000);

        $this->assertSame('réponse invalide', $result['fallback_reason']);
        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context) => str_contains($message, 'réponse invalide')
            && str_contains($context['detail'], 'JSON illisible') && str_contains($context['detail'], 'Je recommande le train.'))->once();
        // La nouvelle tentative explique au modèle pourquoi sa réponse a été refusée
        Http::assertSent(fn (Request $request) => count($request['messages']) === 4
            && str_contains($request['messages'][3]['content'], 'JSON illisible'));
    }

    public function test_ai_answer_parsing_is_tolerant(): void
    {
        config(['services.ai.key' => 'test-key', 'services.ai.base_url' => 'https://api.groq.test/openai/v1']);
        // Code du mode au lieu de la clé, guillemets typographiques et virgule finale
        Http::fake(['api.groq.test/*' => Http::response($this->aiMessage(
            'Voici ma réponse : {“choice”: “rail”, “explanation”: “Le train est le moins émetteur.”, “tips”: [“Le plan émet environ 25 kg.”],}'
        ))]);

        $result = $this->recommender()->recommend(500, 2000, null, $this->shipment("Huile d'olive", 365));

        $this->assertSame('ai', $result['source']);
        $this->assertSame('Groq', $result['provider']);
        $this->assertNull($result['fallback_reason']);
        $this->assertSame('plan_rail', $result['recommended']['key']);
        // 25 est l'arrondi de 24,68 : le conseil est gardé
        $this->assertSame(['Le plan émet environ 25 kg.'], $result['tips']);
        Http::assertSentCount(1);
    }

    public function test_negative_saving_is_shown_as_extra_emissions(): void
    {
        $this->actingAs($this->distributor)->get(route('pro.distributor.shipments.create'))
            ->assertOk()
            ->assertSee('Surcoût vs ', false)
            ->assertSee('Mode secours (hors-ligne)', false);

        // 250 km, 50 kg, 5 h : seul l'utilitaire respecte le délai, il émet plus que le camion porteur
        $result = $this->recommender()->recommend(250, 50, 5);
        $this->assertSame('mode_van', $result['recommended']['key']);
        $this->assertLessThan(0, $result['saving_kg']);
    }

    public function test_recommendation_works_before_the_shipment_is_saved_without_api_key(): void
    {
        $url = route('pro.distributor.shipments.transport-recommendation');
        $this->actingAs($this->distributor)->get(route('pro.distributor.shipments.create'))
            ->assertOk()
            ->assertSee('ai-recommend-btn', false);

        // Sans lot de production, la nature est déduite du nom saisi dans le formulaire
        $this->postJson($url, ['distance_km' => 500, 'weight_kg' => 2000, 'product_name' => 'Fraises'])
            ->assertOk()
            ->assertJsonPath('source', 'local')
            ->assertJsonPath('product.class', 'perishable')
            ->assertJsonPath('recommended.key', 'mode_truck_heavy')
            ->assertJsonPath('reference.code', 'truck_light');

        // Avec le lot de production choisi : sa DLC (6 mois) en fait un produit durable, plan ferroviaire en 3 étapes
        $lot = $this->productionLot("Huile d'olive", 'Olives');
        $this->postJson($url, ['distance_km' => 500, 'weight_kg' => 2000, 'production_lot_id' => $lot->id])
            ->assertOk()
            ->assertJsonPath('product.name', "Huile d'olive")
            ->assertJsonPath('product.lot_code', $lot->qr_code_data)
            ->assertJsonPath('product.class', 'durable')
            ->assertJsonPath('recommended.key', 'plan_rail')
            ->assertJsonCount(3, 'recommended.legs')
            ->assertJsonPath('recommended.legs.1.code', 'rail');

        // Aucune expédition n'est créée par la recommandation
        $this->assertDatabaseCount('shipments', 0);
        Http::assertNothingSent();

        $this->postJson($url, ['distance_km' => '', 'weight_kg' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['distance_km', 'weight_kg']);
    }

    public function test_recommendation_before_saving_uses_the_ai_when_a_key_is_set(): void
    {
        config(['services.ai.key' => 'test-key']);
        Http::fake(['ai.test/*' => Http::response($this->aiMessage(
            '{"choice": "plan_rail", "explanation": "Le lot se conserve : le train divise le CO2.", "tips": []}'
        ))]);
        $lot = $this->productionLot("Huile d'olive", 'Olives');

        $this->actingAs($this->distributor)
            ->postJson(route('pro.distributor.shipments.transport-recommendation'), [
                'distance_km' => 500, 'weight_kg' => 2000, 'max_hours' => 72, 'production_lot_id' => $lot->id,
            ])
            ->assertOk()
            ->assertJsonPath('source', 'ai')
            ->assertJsonPath('recommended.key', 'plan_rail')
            ->assertJsonPath('explanation', 'Le lot se conserve : le train divise le CO2.');

        // L'IA reçoit les données du formulaire : lot, poids, distance et délai
        Http::assertSent(fn (Request $request) => str_contains($request['messages'][1]['content'], $lot->qr_code_data)
            && str_contains($request['messages'][1]['content'], '500 km, 2000 kg')
            && str_contains($request['messages'][1]['content'], 'délai souhaité par le distributeur 72 h'));
    }

    public function test_recommendation_cannot_use_another_distributors_data(): void
    {
        $other = User::factory()->create(['role' => 'pro', 'pro_type' => 'distributor', 'status' => 'approved']);
        $foreignStore = PointOfSale::create([
            'user_id' => $other->id, 'name' => 'Magasin concurrent', 'type' => 'store', 'address' => 'Rue X', 'city' => 'Sousse',
        ]);
        $foreignShipment = Shipment::create([
            'reference' => Shipment::nextReference(), 'user_id' => $other->id, 'product_name' => 'Huile',
            'weight_kg' => 100, 'origin_address' => 'Sfax', 'status' => 'planned',
        ]);
        $url = route('pro.distributor.shipments.transport-recommendation');

        $this->postJson($url, ['distance_km' => 500, 'weight_kg' => 100])->assertUnauthorized();

        $this->actingAs($this->distributor)
            ->postJson($url, ['distance_km' => 500, 'weight_kg' => 100, 'point_of_sale_id' => $foreignStore->id, 'shipment_id' => $foreignShipment->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['point_of_sale_id', 'shipment_id']);
    }

    public function test_grouping_tip_works_for_a_shipment_not_yet_saved(): void
    {
        $pointOfSale = PointOfSale::create([
            'user_id' => $this->distributor->id, 'name' => 'Épicerie Bio', 'type' => 'store', 'address' => 'Rue de Marseille', 'city' => 'Tunis',
        ]);
        $planned = $this->shipment('Colis divers', null, 1, ['point_of_sale_id' => $pointOfSale->id]);

        $this->actingAs($this->distributor)
            ->postJson(route('pro.distributor.shipments.transport-recommendation'), [
                'distance_km' => 150, 'weight_kg' => 2000, 'product_name' => 'Colis divers', 'point_of_sale_id' => $pointOfSale->id,
            ])
            ->assertOk()
            ->assertJsonFragment(["Regroupez avec {$planned->reference} vers Épicerie Bio : un seul trajet au lieu de 2."]);

        // En modification, l'expédition ne se propose pas de se regrouper avec elle-même
        $this->postJson(route('pro.distributor.shipments.transport-recommendation'), [
            'distance_km' => 150, 'weight_kg' => 2000, 'product_name' => 'Colis divers',
            'point_of_sale_id' => $pointOfSale->id, 'shipment_id' => $planned->id,
        ])->assertOk()->assertJsonMissing(["Regroupez avec {$planned->reference} vers Épicerie Bio : un seul trajet au lieu de 2."]);
    }
}
