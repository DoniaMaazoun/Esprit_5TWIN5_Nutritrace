<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use App\Services\TransportRecommender;
use Database\Seeders\TransportModeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Module 3 - Recommandation IA du mode de transport (avec mode de secours sans clé API)
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

    private function fakeAiAnswer(string $content): void
    {
        config(['services.ai.key' => 'test-key']);
        Http::fake(['ai.test/*' => Http::response(['choices' => [['message' => ['content' => $content]]]])]);
    }

    public function test_without_api_key_the_least_polluting_mode_is_recommended(): void
    {
        $result = $this->recommender()->recommend(500, 2000);

        $this->assertSame('local', $result['source']);
        // Train de fret : 500 km x 2 t x 0,0052 = 5,2 kg
        $this->assertSame('rail', $result['recommended']['code']);
        $this->assertEqualsWithDelta(5.2, $result['recommended']['co2_kg'], 0.001);
        // Référence camion porteur : 500 x 2 x 0,2 = 200 kg
        $this->assertSame('truck_light', $result['reference']['code']);
        $this->assertEqualsWithDelta(194.8, $result['saving_kg'], 0.001);
        $this->assertEqualsWithDelta(97.4, $result['saving_percent'], 0.1);
        $this->assertStringContainsString('Train de fret', $result['explanation']);
        Http::assertNothingSent();
    }

    public function test_delay_and_capacity_exclude_modes(): void
    {
        // 300 km en 8 h, 2 t : le train (19,5 h), le bateau et l'utilitaire (> 1,5 t) sont exclus
        $result = $this->recommender()->recommend(300, 2000, 8);
        $options = collect($result['options'])->keyBy('code');

        $this->assertFalse($options['rail']['feasible']);
        $this->assertFalse($options['sea']['feasible']);
        $this->assertFalse($options['van']['fits_capacity']);
        $this->assertTrue($options['truck_heavy']['feasible']);
        $this->assertSame('truck_heavy', $result['recommended']['code']);
        $this->assertEqualsWithDelta(51.0, $result['recommended']['co2_kg'], 0.001);
    }

    public function test_fastest_mode_is_proposed_when_no_mode_meets_the_delay(): void
    {
        $result = $this->recommender()->recommend(2000, 500, 3);

        $this->assertSame('air', $result['recommended']['code']);
        $this->assertStringContainsString('Aucun mode ne respecte le délai', $result['explanation']);
    }

    public function test_ai_choice_and_explanation_are_used_when_valid(): void
    {
        $this->fakeAiAnswer("```json\n{\"code\": \"truck_heavy\", \"explanation\": \"Le camion 40 t est plus réaliste pour des produits frais.\"}\n```");

        $result = $this->recommender()->recommend(500, 2000);

        $this->assertSame('ai', $result['source']);
        $this->assertSame('truck_heavy', $result['recommended']['code']);
        $this->assertSame('Le camion 40 t est plus réaliste pour des produits frais.', $result['explanation']);
        // Le CO2 reste calculé localement : 500 x 2 x 0,085 = 85 kg
        $this->assertEqualsWithDelta(85.0, $result['recommended']['co2_kg'], 0.001);
        $this->assertEqualsWithDelta(115.0, $result['saving_kg'], 0.001);
        Http::assertSentCount(1);
    }

    public function test_invalid_ai_answer_falls_back_to_co2_calculation(): void
    {
        config(['services.ai.key' => 'test-key']);
        Http::fake(['ai.test/*' => Http::sequence()
            // Mode exclu par le délai : on ignore la réponse
            ->push(['choices' => [['message' => ['content' => '{"code": "rail", "explanation": "Prenez le train."}']]]])
            // Réponse sans JSON
            ->push(['choices' => [['message' => ['content' => 'Je recommande le train.']]]])]);

        $this->assertSame('local', $this->recommender()->recommend(300, 2000, 8)['source']);
        $this->assertSame('local', $this->recommender()->recommend(500, 2000)['source']);
        Http::assertSentCount(2);
    }

    public function test_api_error_falls_back_to_co2_calculation(): void
    {
        config(['services.ai.key' => 'test-key']);
        Http::fake(['ai.test/*' => Http::response('Server error', 500)]);

        $result = $this->recommender()->recommend(500, 2000);

        $this->assertSame('local', $result['source']);
        $this->assertSame('rail', $result['recommended']['code']);
    }

    public function test_recommendation_endpoint(): void
    {
        $shipment = Shipment::create([
            'reference' => Shipment::nextReference(),
            'user_id' => $this->distributor->id,
            'product_name' => 'Tomates',
            'weight_kg' => 2000,
            'origin_address' => 'Nabeul',
            'status' => 'planned',
        ]);
        $url = route('pro.distributor.shipments.transport-recommendation', $shipment);

        $this->actingAs($this->distributor)->get(route('pro.distributor.shipments.show', $shipment))
            ->assertOk()
            ->assertSee('Recommandation IA');

        // Le poids de l'expédition est utilisé par défaut
        $this->postJson($url, ['distance_km' => 500])
            ->assertOk()
            ->assertJsonPath('source', 'local')
            ->assertJsonPath('recommended.code', 'rail')
            ->assertJsonPath('reference.code', 'truck_light');

        $this->postJson($url, ['distance_km' => ''])->assertUnprocessable()->assertJsonValidationErrors('distance_km');

        $other = User::factory()->create(['role' => 'pro', 'pro_type' => 'distributor', 'status' => 'approved']);
        $this->actingAs($other)->postJson($url, ['distance_km' => 500])->assertForbidden();
    }
}
