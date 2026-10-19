<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Farm;
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
use Tests\TestCase;

/**
 * Module 3 - Recommandation IA du mode de transport : nature du produit, plans multimodaux,
 * conseils, et mode de secours sans clé API
 */
class TransportRecommendationTest extends TestCase
{
    use RefreshDatabase;

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
     * Expédition reliée, si une catégorie est donnée, à un lot du module 1
     */
    private function shipment(string $product, ?string $categorySlug = null, int $harvestedDaysAgo = 1, array $attributes = []): Shipment
    {
        $harvestId = null;
        if ($categorySlug) {
            $producer = User::factory()->create(['role' => 'pro', 'pro_type' => 'producer', 'status' => 'approved']);
            $farm = Farm::forceCreate([
                'user_id' => $producer->id, 'name' => 'Ferme '.uniqid(), 'governorate' => 'Nabeul', 'city' => 'Menzel Temime',
                'total_area' => 10, 'farming_type' => 'organic', 'irrigation_type' => 'drip',
            ]);
            $harvestId = Harvest::create([
                'farm_id' => $farm->id, 'product_name' => $product, 'category_id' => Category::where('slug', $categorySlug)->value('id'),
                'harvest_date' => now()->subDays($harvestedDaysAgo), 'quantity' => 2, 'unit' => 'tonne', 'quality_grade' => 'A',
            ])->id;
        }

        return Shipment::create($attributes + [
            'reference' => Shipment::nextReference(),
            'user_id' => $this->distributor->id,
            'harvest_id' => $harvestId,
            'product_name' => $product,
            'weight_kg' => 2000,
            'origin_address' => 'Nabeul',
            'status' => 'planned',
        ])->load('harvest.category', 'pointOfSale');
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

    public function test_perishable_product_without_api_key_prioritises_speed_and_cold_chain(): void
    {
        $shipment = $this->shipment('Tomates', 'legumes');

        $result = $this->recommender()->recommend(500, 2000, null, $shipment);
        $options = collect($result['options'])->keyBy('key');

        $this->assertSame('local', $result['source']);
        $this->assertSame('perishable', $result['product']['class']);
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
        $shipment = $this->shipment("Huile d'olive", 'olives');

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
        $shipment = $this->shipment('Dattes', 'dattes', 10, ['weight_kg' => 1000]);

        $plan = $this->recommender()->recommend(600, 1000, null, $shipment)['recommended'];

        $this->assertSame(['truck_light', 'rail', 'van'], array_column($plan['legs'], 'code'));
        // 30 x 1 x 0,2 + 550 x 1 x 0,0052 + 20 x 1 x 0,6
        $this->assertEqualsWithDelta(20.86, $plan['co2_kg'], 0.001);
    }

    public function test_tip_quantifies_the_saving_of_a_longer_delay(): void
    {
        $shipment = $this->shipment("Huile d'olive", 'olives');

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
        $shipment = $this->shipment("Huile d'olive", 'olives');

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
        $tomatoes = $this->shipment('Tomates', 'legumes');

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

        $result = $this->recommender()->recommend(500, 2000, null, $this->shipment("Huile d'olive", 'olives'));

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

        $result = $this->recommender()->recommend(500, 2000, null, $this->shipment("Huile d'olive", 'olives'));

        $this->assertSame('local', $result['source']);
        $this->assertSame('plan_rail', $result['recommended']['key']);
    }

    public function test_recommendation_endpoint(): void
    {
        // Sans lot lié, la nature est déduite du nom du produit
        $shipment = $this->shipment('Fraises');
        $url = route('pro.distributor.shipments.transport-recommendation', $shipment);

        $this->actingAs($this->distributor)->get(route('pro.distributor.shipments.show', $shipment))
            ->assertOk()
            ->assertSee('Recommandation IA');

        // Le poids de l'expédition est utilisé par défaut
        $this->postJson($url, ['distance_km' => 500])
            ->assertOk()
            ->assertJsonPath('source', 'local')
            ->assertJsonPath('product.class', 'perishable')
            ->assertJsonPath('recommended.key', 'mode_truck_heavy')
            ->assertJsonPath('reference.code', 'truck_light');

        $this->postJson($url, ['distance_km' => ''])->assertUnprocessable()->assertJsonValidationErrors('distance_km');

        $other = User::factory()->create(['role' => 'pro', 'pro_type' => 'distributor', 'status' => 'approved']);
        $this->actingAs($other)->postJson($url, ['distance_km' => 500])->assertForbidden();
    }
}
