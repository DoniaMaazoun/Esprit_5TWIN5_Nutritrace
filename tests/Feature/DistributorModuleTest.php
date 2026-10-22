<?php

namespace Tests\Feature;

use App\Models\Harvest;
use App\Models\PointOfSale;
use App\Models\Shipment;
use App\Models\TransportMode;
use App\Models\User;
use Database\Seeders\TransportModeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsProductionChain;
use Tests\TestCase;

/**
 * Module 3 - Tests des CRUD de l'espace distributeur et du back-office
 */
class DistributorModuleTest extends TestCase
{
    use BuildsProductionChain, RefreshDatabase;

    private User $distributor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TransportModeSeeder::class);

        $this->distributor = User::factory()->create([
            'role' => 'pro',
            'pro_type' => 'distributor',
            'company_name' => 'Distrib Test',
            'status' => 'approved',
        ]);
    }

    public function test_only_approved_distributors_can_access_the_module(): void
    {
        $producer = User::factory()->create(['role' => 'pro', 'pro_type' => 'producer', 'status' => 'approved']);

        $this->get(route('pro.distributor.shipments.index'))->assertRedirect(route('login'));
        $this->actingAs($producer)->get(route('pro.distributor.shipments.index'))->assertRedirect(route('pro.dashboard'));
        $this->actingAs($this->distributor)->get(route('pro.distributor.shipments.index'))->assertOk();
    }

    public function test_point_of_sale_crud(): void
    {
        $this->actingAs($this->distributor);

        $this->post(route('pro.distributor.points-of-sale.store'), [
            'name' => 'Épicerie Bio',
            'type' => 'store',
            'address' => 'Rue de Marseille',
            'city' => 'Tunis',
        ])->assertRedirect(route('pro.distributor.points-of-sale.index'));

        $pointOfSale = PointOfSale::firstOrFail();
        $this->assertSame($this->distributor->id, $pointOfSale->user_id);

        $this->get(route('pro.distributor.points-of-sale.index'))->assertSee('Épicerie Bio');
        $this->get(route('pro.distributor.points-of-sale.show', $pointOfSale))->assertOk();
        $this->get(route('pro.distributor.points-of-sale.edit', $pointOfSale))->assertOk();

        $this->put(route('pro.distributor.points-of-sale.update', $pointOfSale), [
            'name' => 'Épicerie Bio Lac',
            'type' => 'market',
            'address' => 'Rue de Marseille',
            'city' => 'Tunis',
        ])->assertRedirect();
        $this->assertSame('market', $pointOfSale->fresh()->type);

        $this->delete(route('pro.distributor.points-of-sale.destroy', $pointOfSale))->assertRedirect();
        $this->assertDatabaseCount('points_of_sale', 0);
    }

    public function test_shipment_crud_with_legs_events_and_co2(): void
    {
        $this->actingAs($this->distributor);
        $this->get(route('pro.distributor.shipments.create'))->assertOk();

        $this->post(route('pro.distributor.shipments.store'), [
            'product_name' => 'Dattes Deglet Nour',
            'weight_kg' => 2000,
            'origin_address' => 'Tozeur',
            'status' => 'planned',
        ])->assertRedirect();

        $shipment = Shipment::firstOrFail();
        $this->assertMatchesRegularExpression('/^SHP-\d{4}-0001$/', $shipment->reference);

        // Étape camion : 450 km x 2 t x 0,085 = 76,5 kg CO2e
        $truck = TransportMode::where('code', 'truck_heavy')->firstOrFail();
        $this->post(route('pro.distributor.shipments.legs.store', $shipment), [
            'transport_mode_id' => $truck->id,
            'from_location' => 'Tozeur',
            'to_location' => 'Tunis',
            'distance_km' => 450,
            'weight_kg' => 2000,
        ])->assertRedirect(route('pro.distributor.shipments.show', $shipment));

        $leg = $shipment->legs()->firstOrFail();
        $this->assertEqualsWithDelta(76.5, (float) $leg->co2_kg, 0.001);
        $this->assertEqualsWithDelta(76.5, (float) $shipment->fresh()->total_co2_kg, 0.001);

        // Modification de l'étape : le CO2 est recalculé
        $this->get(route('pro.distributor.shipments.legs.edit', [$shipment, $leg]))->assertOk();
        $this->put(route('pro.distributor.shipments.legs.update', [$shipment, $leg]), [
            'transport_mode_id' => $truck->id,
            'from_location' => 'Tozeur',
            'to_location' => 'Tunis',
            'distance_km' => 100,
            'weight_kg' => 2000,
        ])->assertRedirect();
        $this->assertEqualsWithDelta(17.0, (float) $shipment->fresh()->total_co2_kg, 0.001);

        // Journal de traçabilité
        $this->post(route('pro.distributor.shipments.events.store', $shipment), [
            'type' => 'loaded',
            'location' => 'Tozeur',
            'occurred_at' => now()->format('Y-m-d H:i'),
        ])->assertRedirect();
        $event = $shipment->events()->firstOrFail();
        $this->get(route('pro.distributor.shipments.events.edit', [$shipment, $event]))->assertOk();
        $this->put(route('pro.distributor.shipments.events.update', [$shipment, $event]), [
            'type' => 'departed',
            'occurred_at' => now()->format('Y-m-d H:i'),
        ])->assertRedirect();
        $this->assertSame('departed', $event->fresh()->type);

        $this->get(route('pro.distributor.shipments.show', $shipment))->assertOk()->assertSee('Tozeur');
        $this->get(route('pro.distributor.shipments.edit', $shipment))->assertOk();
        $this->put(route('pro.distributor.shipments.update', $shipment), [
            'product_name' => 'Dattes Deglet Nour',
            'weight_kg' => 2000,
            'origin_address' => 'Tozeur',
            'status' => 'in_transit',
        ])->assertRedirect();
        $this->assertSame('in_transit', $shipment->fresh()->status);

        // Suppression d'une étape puis de l'expédition (cascade)
        $this->delete(route('pro.distributor.shipments.legs.destroy', [$shipment, $leg]))->assertRedirect();
        $this->assertEqualsWithDelta(0, (float) $shipment->fresh()->total_co2_kg, 0.001);
        $this->delete(route('pro.distributor.shipments.destroy', $shipment))->assertRedirect();
        $this->assertDatabaseCount('shipments', 0);
        $this->assertDatabaseCount('shipment_events', 0);
    }

    public function test_shipments_reference_production_lots_instead_of_harvests(): void
    {
        $this->assertFalse(Schema::hasColumn('shipments', 'harvest_id'));
        $this->assertTrue(Schema::hasColumn('shipments', 'production_lot_id'));
    }

    public function test_shipment_is_linked_to_a_production_lot_traced_back_to_the_harvest(): void
    {
        $lot = $this->productionLot('Sauce Tomate Bio', 'Tomates Bio');
        $harvest = Harvest::firstOrFail();
        $archived = $this->productionLot('Confiture de figues', 'Figues', ['status' => 'archived']);

        $this->actingAs($this->distributor);
        $this->get(route('pro.distributor.shipments.create'))->assertOk()
            ->assertSee($lot->qr_code_data)
            ->assertSee('Sauce Tomate Bio')
            ->assertDontSee($archived->qr_code_data);

        // Sans nom de produit : il est repris de la recette du lot
        $this->post(route('pro.distributor.shipments.store'), [
            'production_lot_id' => $lot->id,
            'weight_kg' => 500,
            'origin_address' => 'Usine Test, Sousse',
            'status' => 'planned',
        ])->assertRedirect();

        $shipment = Shipment::firstOrFail();
        $this->assertTrue($shipment->productionLot->is($lot));
        $this->assertSame('Sauce Tomate Bio', $shipment->product_name);

        // Traçabilité jusqu'à la récolte, déduite des réceptions du transformateur
        $this->assertCount(1, $shipment->originHarvests());
        $this->assertTrue($shipment->originHarvests()->first()->is($harvest));
        $this->get(route('pro.distributor.shipments.show', $shipment))->assertOk()
            ->assertSee('Lot de production')
            ->assertSee($lot->qr_code_data)
            ->assertSee('Usine Test')
            ->assertSee($harvest->batch_code);

        // Un lot inexistant est refusé
        $this->post(route('pro.distributor.shipments.store'), [
            'production_lot_id' => 999999,
            'weight_kg' => 500,
            'origin_address' => 'Sfax',
            'status' => 'planned',
        ])->assertSessionHasErrors('production_lot_id');

        // La suppression du lot ne supprime pas l'expédition
        $lot->delete();
        $this->assertNull($shipment->fresh()->production_lot_id);
        $this->get(route('pro.distributor.shipments.show', $shipment))->assertOk()->assertSee('Aucun lot de production relié');
    }

    public function test_origin_harvest_is_reported_missing_when_the_reception_cannot_be_found(): void
    {
        $lot = $this->productionLot('Sauce Tomate Bio', 'Tomates Bio', withReception: false);
        $shipment = $this->distributor->shipments()->create([
            'reference' => Shipment::nextReference(),
            'production_lot_id' => $lot->id,
            'product_name' => 'Sauce Tomate Bio',
            'weight_kg' => 100,
            'origin_address' => 'Sousse',
        ]);

        $this->assertCount(0, $shipment->originHarvests());
        $this->actingAs($this->distributor)->get(route('pro.distributor.shipments.show', $shipment))
            ->assertOk()
            ->assertSee($lot->qr_code_data)
            ->assertSee("Récolte d'origine non retrouvée.", false);
    }

    public function test_distributor_cannot_access_another_distributors_shipment(): void
    {
        $other = User::factory()->create(['role' => 'pro', 'pro_type' => 'distributor', 'status' => 'approved']);
        $shipment = $other->shipments()->create([
            'reference' => Shipment::nextReference(),
            'product_name' => 'Huile',
            'weight_kg' => 100,
            'origin_address' => 'Sfax',
        ]);

        $this->actingAs($this->distributor)
            ->get(route('pro.distributor.shipments.show', $shipment))
            ->assertForbidden();
    }

    public function test_admin_can_manage_transport_modes_and_view_shipments(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin);

        $this->get(route('backend.logistics.transport-modes.index'))->assertOk()->assertSee('Train de fret');
        $this->get(route('backend.logistics.transport-modes.create'))->assertOk();
        $this->post(route('backend.logistics.transport-modes.store'), [
            'name' => 'Vélo cargo',
            'code' => 'cargo_bike',
            'emission_factor' => 0,
            'is_active' => 1,
        ])->assertRedirect(route('backend.logistics.transport-modes.index'));

        $mode = TransportMode::where('code', 'cargo_bike')->firstOrFail();
        $this->get(route('backend.logistics.transport-modes.edit', $mode))->assertOk();
        $this->put(route('backend.logistics.transport-modes.update', $mode), [
            'name' => 'Vélo cargo électrique',
            'code' => 'cargo_bike',
            'emission_factor' => 0.002,
        ])->assertRedirect();
        $this->assertFalse($mode->fresh()->is_active);

        $this->delete(route('backend.logistics.transport-modes.destroy', $mode))->assertRedirect();
        $this->assertDatabaseMissing('transport_modes', ['code' => 'cargo_bike']);

        $lot = $this->productionLot('Huile vierge extra', 'Olive');
        $shipment = $this->distributor->shipments()->create([
            'reference' => Shipment::nextReference(),
            'production_lot_id' => $lot->id,
            'product_name' => 'Huile',
            'weight_kg' => 100,
            'origin_address' => 'Sfax',
        ]);
        $this->get(route('backend.logistics.shipments.index'))->assertOk()->assertSee($shipment->reference);
        $this->get(route('backend.logistics.shipments.show', $shipment))->assertOk()
            ->assertSee($lot->qr_code_data)
            ->assertSee(Harvest::firstOrFail()->batch_code);
    }
}
