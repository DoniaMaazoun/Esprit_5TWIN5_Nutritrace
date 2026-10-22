<?php

namespace Tests\Feature\ModuleProduit;

use App\Models\Farm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsModuleProduitData;
use Tests\TestCase;

/**
 * Exploitations : CRUD, contrôle de saisie, règles métier et droits d'accès.
 */
class FarmManagementTest extends TestCase
{
    use BuildsModuleProduitData, RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('pro.farms.index'))->assertRedirect(route('login'));
    }

    public function test_a_non_producer_professional_cannot_open_the_module(): void
    {
        $distributor = $this->makeProducer(['pro_type' => 'distributor']);

        $this->actingAs($distributor)->get(route('pro.farms.index'))->assertRedirect(route('pro.dashboard'));
    }

    public function test_producer_creates_a_farm(): void
    {
        $producer = $this->makeProducer();

        $response = $this->actingAs($producer)->post(route('pro.farms.store'), $this->farmPayload());

        $farm = Farm::firstWhere('name', 'Domaine des Tests');
        $response->assertRedirect(route('pro.farms.show', $farm));
        $this->assertSame($producer->id, $farm->user_id);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->actingAs($this->makeProducer())
            ->post(route('pro.farms.store'), [])
            ->assertSessionHasErrors(['name', 'governorate', 'city', 'total_area', 'farming_type', 'irrigation_type', 'status']);
    }

    public function test_organic_farm_must_declare_its_certification(): void
    {
        $this->actingAs($this->makeProducer())
            ->post(route('pro.farms.store'), $this->farmPayload(['farming_type' => 'organic']))
            ->assertSessionHasErrors(['certification' => 'Une exploitation biologique doit indiquer son organisme certificateur.']);
    }

    public function test_coordinates_must_be_located_in_tunisia(): void
    {
        $this->actingAs($this->makeProducer())
            ->post(route('pro.farms.store'), $this->farmPayload(['latitude' => 48.85, 'longitude' => 2.35]))
            ->assertSessionHasErrors(['latitude', 'longitude']);
    }

    public function test_farm_name_is_unique_per_producer_only(): void
    {
        $producer = $this->makeProducer();
        $this->makeFarm($producer, ['name' => 'Domaine des Tests']);

        $this->actingAs($producer)->post(route('pro.farms.store'), $this->farmPayload())
            ->assertSessionHasErrors(['name' => 'Ce producteur a déjà une exploitation portant ce nom.']);

        // Un autre producteur peut utiliser le même nom
        $this->actingAs($this->makeProducer())->post(route('pro.farms.store'), $this->farmPayload())
            ->assertSessionHasNoErrors();
    }

    public function test_area_cannot_drop_below_the_largest_declared_plot(): void
    {
        $producer = $this->makeProducer();
        $farm = $this->makeFarm($producer, ['total_area' => 10]);
        $this->makeHarvest($farm, ['plot_area' => 6]);

        $this->actingAs($producer)
            ->put(route('pro.farms.update', $farm), $this->farmPayload(['name' => $farm->name, 'total_area' => 4]))
            ->assertSessionHasErrors('total_area');
    }

    public function test_producer_updates_and_deletes_its_farm_with_its_harvests(): void
    {
        $producer = $this->makeProducer();
        $farm = $this->makeFarm($producer);
        $harvest = $this->makeHarvest($farm);

        $this->actingAs($producer)
            ->put(route('pro.farms.update', $farm), $this->farmPayload(['name' => 'Nouveau nom']))
            ->assertRedirect(route('pro.farms.show', $farm));
        $this->assertSame('Nouveau nom', $farm->fresh()->name);

        $this->actingAs($producer)->delete(route('pro.farms.destroy', $farm))->assertRedirect(route('pro.farms.index'));
        $this->assertModelMissing($farm);
        $this->assertModelMissing($harvest);
    }

    public function test_producer_can_view_but_not_modify_another_producers_farm(): void
    {
        $owner = $this->makeProducer();
        $farm = $this->makeFarm($owner);
        $other = $this->makeProducer();

        $this->actingAs($other)->get(route('pro.farms.show', $farm))->assertOk()->assertSee('lecture seule');
        $this->actingAs($other)->get(route('pro.farms.edit', $farm))->assertForbidden();
        $this->actingAs($other)->put(route('pro.farms.update', $farm), $this->farmPayload())->assertForbidden();
        $this->actingAs($other)->delete(route('pro.farms.destroy', $farm))->assertForbidden();
        $this->assertModelExists($farm);
    }

    public function test_admin_controls_every_farm(): void
    {
        $farm = $this->makeFarm($this->makeProducer());
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('backend.farms.index'))->assertOk()->assertSee($farm->name);
        $this->actingAs($admin)->patch(route('backend.farms.status', $farm), ['status' => 'fallow'])->assertSessionHasNoErrors();
        $this->assertSame('fallow', $farm->fresh()->status);
        $this->actingAs($admin)->delete(route('backend.farms.destroy', $farm));
        $this->assertModelMissing($farm);
    }

    public function test_csv_export_contains_only_own_farms(): void
    {
        $producer = $this->makeProducer();
        $this->makeFarm($producer, ['name' => 'Ma ferme']);
        $this->makeFarm($this->makeProducer(), ['name' => 'Ferme voisine']);

        $csv = $this->actingAs($producer)->get(route('pro.farms.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('Ma ferme', $csv);
        $this->assertStringNotContainsString('Ferme voisine', $csv);
    }
}
