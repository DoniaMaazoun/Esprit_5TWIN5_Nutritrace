<?php

namespace Tests\Feature\ModuleProduit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsModuleProduitData;
use Tests\TestCase;

/**
 * Fonctions IA : moteur local sans clé, appel au LLM quand une clé est configurée, repli en cas d'erreur.
 */
class AiFeaturesTest extends TestCase
{
    use BuildsModuleProduitData, RefreshDatabase;

    public function test_farm_description_falls_back_to_the_local_engine(): void
    {
        $this->actingAs($this->makeProducer())
            ->postJson(route('pro.ai.farm-description'), ['name' => 'Domaine Zitouna', 'city' => 'Agareb', 'governorate' => 'Sfax', 'farming_type' => 'organic'])
            ->assertOk()
            ->assertJsonPath('source', 'local')
            ->assertJsonFragment(['source' => 'local'])
            ->assertSee('Domaine Zitouna');
    }

    public function test_farm_description_uses_the_llm_when_a_key_is_configured(): void
    {
        config(['services.ai.key' => 'test-key', 'services.ai.base_url' => 'https://llm.test/v1']);
        Http::fake(['llm.test/*' => Http::response(['choices' => [['message' => ['content' => 'Texte rédigé par le modèle.']]]])]);

        $this->actingAs($this->makeProducer())
            ->postJson(route('pro.ai.farm-description'), ['name' => 'Domaine Zitouna'])
            ->assertOk()
            ->assertJson(['source' => 'ai', 'text' => 'Texte rédigé par le modèle.']);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-key')
            && str_ends_with($request->url(), '/chat/completions'));
    }

    public function test_llm_errors_fall_back_to_local_rules(): void
    {
        config(['services.ai.key' => 'test-key', 'services.ai.base_url' => 'https://llm.test/v1']);
        Http::fake(['llm.test/*' => Http::response('Service indisponible', 503)]);

        $this->actingAs($this->makeProducer())
            ->postJson(route('pro.ai.farm-description'), ['name' => 'Domaine Zitouna'])
            ->assertOk()->assertJsonPath('source', 'local');
    }

    public function test_ai_endpoints_validate_their_input(): void
    {
        $producer = $this->makeProducer();

        $this->actingAs($producer)->postJson(route('pro.ai.farm-description'), [])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->actingAs($producer)->postJson(route('pro.ai.chat'), ['message' => str_repeat('a', 501)])->assertJsonValidationErrors('message');
        $this->actingAs($producer)->postJson(route('pro.ai.harvest-description'), ['product_name' => 'Tomate', 'farm_id' => $this->makeFarm($this->makeProducer())->id])
            ->assertJsonValidationErrors('farm_id');
    }

    public function test_chatbot_answers_with_the_producers_real_stock(): void
    {
        $producer = $this->makeProducer();
        $this->makeHarvest($this->makeFarm($producer), ['product_name' => 'Grenade', 'quantity' => 320]);

        $this->actingAs($producer)->postJson(route('pro.ai.chat'), ['message' => 'Quel est mon stock ?'])
            ->assertOk()
            ->assertJsonPath('source', 'local')
            ->assertSee('Grenade')
            ->assertSee('320 kg');
    }

    public function test_consumer_description_is_generated_for_a_lot(): void
    {
        $producer = $this->makeProducer();
        $farm = $this->makeFarm($producer, ['name' => 'Cap Bon Primeurs']);

        $this->actingAs($producer)->postJson(route('pro.ai.harvest-description'), [
            'product_name' => 'Tomate', 'farm_id' => $farm->id, 'category_id' => $this->category()->id, 'quality_grade' => 'A',
        ])->assertOk()->assertJsonPath('source', 'local')->assertSee('Cap Bon Primeurs');
    }

    public function test_admin_gets_an_explanation_of_the_conformity_score(): void
    {
        $harvest = $this->makeHarvest($this->makeFarm($this->makeProducer()), ['plot_area' => 1, 'quantity' => 300000]);

        $response = $this->actingAs($this->makeAdmin())->getJson(route('backend.products.risk', $harvest))
            ->assertOk()
            ->assertJsonPath('source', 'local');

        $this->assertStringContainsString('Risque élevé', $response->json('text'));
        $this->assertStringContainsString('surdéclaration', $response->json('text'));
    }

    public function test_farm_insights_are_private_to_the_owner(): void
    {
        $owner = $this->makeProducer();
        $farm = $this->makeFarm($owner);

        $this->actingAs($owner)->getJson(route('pro.ai.farm-insights', $farm))->assertOk()->assertJsonStructure(['metrics', 'recommendations', 'summary', 'source']);
        $this->actingAs($this->makeProducer())->getJson(route('pro.ai.farm-insights', $farm))->assertForbidden();
    }
}
