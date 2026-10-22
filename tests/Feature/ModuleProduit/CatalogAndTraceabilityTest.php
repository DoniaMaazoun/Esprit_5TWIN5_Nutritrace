<?php

namespace Tests\Feature\ModuleProduit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsModuleProduitData;
use Tests\TestCase;

/**
 * Pages publiques : catalogue, recherche intelligente, page d'origine (QR code), « Tracer un lot », vitrines.
 */
class CatalogAndTraceabilityTest extends TestCase
{
    use BuildsModuleProduitData, RefreshDatabase;

    public function test_catalog_only_shows_products_of_approved_producers(): void
    {
        $this->makeHarvest($this->makeFarm($this->makeProducer()), ['product_name' => 'Abricot visible']);
        $this->makeHarvest($this->makeFarm($this->makeProducer(['status' => 'pending'])), ['product_name' => 'Datte cachée']);

        $this->get(route('frontend.products'))->assertOk()
            ->assertSee('Abricot visible')
            ->assertDontSee('Datte cachée');
    }

    public function test_natural_language_search_is_turned_into_filters(): void
    {
        $sfax = $this->makeFarm($this->makeProducer(), ['governorate' => 'Sfax', 'farming_type' => 'organic', 'certification' => 'CNAB']);
        $this->makeHarvest($sfax, ['product_name' => 'Olive Chemlali', 'category_id' => $this->category('olives')->id]);
        $this->makeHarvest($this->makeFarm($this->makeProducer(), ['governorate' => 'Nabeul']), ['product_name' => 'Tomate de Nabeul']);

        $this->get(route('frontend.products', ['ask' => 'olives bio de Sfax']))->assertOk()
            ->assertSee('Catégorie : Olives')
            ->assertSee('Région : Sfax')
            ->assertSee('Agriculture : Biologique')
            ->assertSee('Olive Chemlali')
            ->assertDontSee('Tomate de Nabeul');
    }

    public function test_traceability_page_shows_the_origin_of_a_lot(): void
    {
        $farm = $this->makeFarm($this->makeProducer(), ['name' => 'Vergers du Test']);
        $harvest = $this->makeHarvest($farm, ['public_description' => 'Tomates cueillies à la main au petit matin.']);

        $this->get(route('traceability.show', $harvest->batch_code))->assertOk()
            ->assertSee('Vergers du Test')
            ->assertSee('Tomates cueillies à la main au petit matin.')
            ->assertSee('Origine vérifiée');
    }

    public function test_lot_lookup_validates_the_code(): void
    {
        $harvest = $this->makeHarvest($this->makeFarm($this->makeProducer()));

        $this->from('/')->get(route('traceability.lookup', ['code' => 'abc']))
            ->assertRedirect('/')->assertSessionHasErrorsIn('lookup', 'code');

        $this->from('/')->get(route('traceability.lookup', ['code' => 'LOT-20990101-ZZZZZ']))
            ->assertSessionHasErrorsIn('lookup', ['code' => 'Aucun lot ne porte le code LOT-20990101-ZZZZZ.']);

        // Saisie en minuscules acceptée
        $this->get(route('traceability.lookup', ['code' => strtolower($harvest->batch_code)]))
            ->assertRedirect(route('traceability.show', $harvest->batch_code));
    }

    public function test_farm_showcase_is_hidden_for_unapproved_producers(): void
    {
        $approved = $this->makeFarm($this->makeProducer(), ['name' => 'Ferme publique']);
        $pending = $this->makeFarm($this->makeProducer(['status' => 'pending']));

        $this->get(route('farms.public', $approved))->assertOk()->assertSee('Ferme publique');
        $this->get(route('farms.public', $pending))->assertNotFound();
        $this->get(route('farms.directory'))->assertOk()->assertSee('Ferme publique');
    }

    public function test_home_page_shows_real_statistics(): void
    {
        $this->makeHarvest($this->makeFarm($this->makeProducer()), ['product_name' => 'Fraise du jour']);

        $this->get(route('frontend.home'))->assertOk()->assertSee('Fraise du jour')->assertSee('lots tracés');
    }

    public function test_login_redirects_to_home_with_a_welcome_message(): void
    {
        $user = $this->makeProducer(['name' => 'Amel']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(route('frontend.home'))
            ->assertSessionHas('welcome', 'Bienvenue, Amel !');
    }
}
