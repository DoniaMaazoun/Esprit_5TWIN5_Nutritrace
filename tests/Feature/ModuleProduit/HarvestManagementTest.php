<?php

namespace Tests\Feature\ModuleProduit;

use App\Models\Harvest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsModuleProduitData;
use Tests\TestCase;

/**
 * Produits (récoltes) : CRUD, règles métier de saisie, photo, droits producteur / admin.
 */
class HarvestManagementTest extends TestCase
{
    use BuildsModuleProduitData, RefreshDatabase;

    public function test_producer_declares_a_harvest_and_gets_a_batch_code(): void
    {
        $producer = $this->makeProducer();
        $farm = $this->makeFarm($producer);

        $response = $this->actingAs($producer)->post(route('pro.harvests.store'), $this->harvestPayload($farm));

        $harvest = Harvest::firstOrFail();
        $response->assertRedirect(route('pro.harvests.show', $harvest));
        $this->assertMatchesRegularExpression('/^LOT-\d{8}-[A-Z0-9]{5}$/', $harvest->batch_code);
        $this->assertSame('Piment', $harvest->product_name);
    }

    public function test_business_rules_are_enforced_on_the_server(): void
    {
        $producer = $this->makeProducer();
        $farm = $this->makeFarm($producer, ['total_area' => 5]);

        $this->actingAs($producer)->post(route('pro.harvests.store'), $this->harvestPayload($farm, [
            'plot_area' => 8,                                          // > surface de la ferme
            'harvest_date' => now()->addDays(3)->format('Y-m-d'),     // dans le futur
            'quantity' => 0,                                           // doit être > 0
        ]))->assertSessionHasErrors(['plot_area', 'harvest_date', 'quantity']);
    }

    public function test_planting_must_precede_the_harvest(): void
    {
        $producer = $this->makeProducer();
        $farm = $this->makeFarm($producer);

        $this->actingAs($producer)->post(route('pro.harvests.store'), $this->harvestPayload($farm, [
            'planting_date' => now()->format('Y-m-d'),
            'harvest_date' => now()->subDays(10)->format('Y-m-d'),
        ]))->assertSessionHasErrors('planting_date');
    }

    public function test_no_harvest_on_a_fallow_farm(): void
    {
        $producer = $this->makeProducer();
        $farm = $this->makeFarm($producer, ['status' => 'fallow']);

        $this->actingAs($producer)->post(route('pro.harvests.store'), $this->harvestPayload($farm))
            ->assertSessionHasErrors('farm_id');
    }

    public function test_synthetic_treatment_is_refused_on_an_organic_farm(): void
    {
        $producer = $this->makeProducer();
        $farm = $this->makeFarm($producer, ['farming_type' => 'organic', 'certification' => 'Ecocert']);

        $this->actingAs($producer)->post(route('pro.harvests.store'), $this->harvestPayload($farm, ['treatments' => 'Glyphosate 2 L/ha']))
            ->assertSessionHasErrors(['treatments' => 'Ce traitement est interdit en agriculture biologique.']);
    }

    public function test_cannot_declare_a_harvest_on_someone_elses_farm(): void
    {
        $otherFarm = $this->makeFarm($this->makeProducer());

        $this->actingAs($this->makeProducer())->post(route('pro.harvests.store'), $this->harvestPayload($otherFarm))
            ->assertSessionHasErrors('farm_id');
    }

    public function test_inactive_category_cannot_be_chosen(): void
    {
        $producer = $this->makeProducer();
        $farm = $this->makeFarm($producer);
        $category = $this->category('fruits');
        $category->update(['is_active' => false]);

        $this->actingAs($producer)->post(route('pro.harvests.store'), $this->harvestPayload($farm, ['category_id' => $category->id]))
            ->assertSessionHasErrors('category_id');
    }

    public function test_photo_is_stored_validated_and_deleted_with_the_lot(): void
    {
        Storage::fake('public');
        $producer = $this->makeProducer();
        $farm = $this->makeFarm($producer);

        // Image trop petite : refusée
        $this->actingAs($producer)->post(route('pro.harvests.store'), $this->harvestPayload($farm, [
            'image' => UploadedFile::fake()->image('petite.jpg', 100, 100),
        ]))->assertSessionHasErrors('image');

        // Fichier qui n'est pas une image : refusé
        $this->actingAs($producer)->post(route('pro.harvests.store'), $this->harvestPayload($farm, [
            'image' => UploadedFile::fake()->create('facture.pdf', 50, 'application/pdf'),
        ]))->assertSessionHasErrors('image');

        // Image valide : enregistrée
        $this->actingAs($producer)->post(route('pro.harvests.store'), $this->harvestPayload($farm, [
            'image' => UploadedFile::fake()->image('tomates.jpg', 800, 600),
        ]))->assertSessionHasNoErrors();

        $harvest = Harvest::firstOrFail();
        Storage::disk('public')->assertExists($harvest->image_path);

        $this->actingAs($producer)->delete(route('pro.harvests.destroy', $harvest));
        Storage::disk('public')->assertMissing($harvest->image_path);
    }

    public function test_other_producers_can_view_but_not_modify(): void
    {
        $owner = $this->makeProducer();
        $harvest = $this->makeHarvest($this->makeFarm($owner));
        $other = $this->makeProducer();

        $this->actingAs($other)->get(route('pro.harvests.show', $harvest))->assertOk();
        $this->actingAs($other)->get(route('pro.harvests.edit', $harvest))
            ->assertForbidden()->assertSee('Vous ne pouvez pas modifier le produit d', false);
        $this->actingAs($other)->delete(route('pro.harvests.destroy', $harvest))->assertForbidden();
        $this->assertModelExists($harvest);
    }

    public function test_admin_can_update_and_delete_any_product(): void
    {
        $farm = $this->makeFarm($this->makeProducer());
        $harvest = $this->makeHarvest($farm);
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->put(route('backend.products.update', $harvest), $this->harvestPayload($farm, ['quantity' => 999]))
            ->assertRedirect(route('backend.products.index'));
        $this->assertEquals(999, $harvest->fresh()->quantity);

        $this->actingAs($admin)->delete(route('backend.products.destroy', $harvest));
        $this->assertModelMissing($harvest);
    }

    public function test_producer_cannot_reach_the_back_office(): void
    {
        $this->actingAs($this->makeProducer())->get(route('backend.products.index'))->assertForbidden();
    }

    public function test_printable_label_is_reserved_to_the_owner(): void
    {
        $owner = $this->makeProducer();
        $harvest = $this->makeHarvest($this->makeFarm($owner));

        $this->actingAs($owner)->get(route('pro.harvests.label', $harvest))->assertOk()->assertSee($harvest->batch_code);
        $this->actingAs($this->makeProducer())->get(route('pro.harvests.label', $harvest))->assertForbidden();
    }
}
