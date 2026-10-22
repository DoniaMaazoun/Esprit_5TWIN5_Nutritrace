<?php

namespace Tests\Feature;

use App\Models\Harvest;
use App\Models\PointOfSale;
use App\Models\Shipment;
use App\Models\ShipmentLeg;
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

    private function mode(string $code): int
    {
        return TransportMode::where('code', $code)->value('id');
    }

    public function test_shipment_is_created_with_several_legs_in_one_form(): void
    {
        $this->actingAs($this->distributor);
        $this->get(route('pro.distributor.shipments.create'))->assertOk()
            ->assertSee('Étapes de transport')
            ->assertSee('Recommandation IA')
            ->assertSee('leg-template', false);

        $this->post(route('pro.distributor.shipments.store'), [
            'product_name' => 'Dattes Deglet Nour',
            'weight_kg' => 2000,
            'origin_address' => 'Tozeur',
            'status' => 'planned',
            'legs' => [
                // Poids lourd : 450 km x 2 t x 0,085 = 76,5 kg CO2e
                ['transport_mode_id' => $this->mode('truck_heavy'), 'from_location' => 'Tozeur', 'to_location' => 'Gare de Tunis',
                    'distance_km' => 450, 'to_latitude' => 36.7948, 'to_longitude' => 10.1802, 'from_recommendation' => 1],
                // Utilitaire : 12 km x 2 t x 0,6 = 14,4 kg CO2e
                ['transport_mode_id' => $this->mode('van'), 'from_location' => 'Gare de Tunis', 'to_location' => 'Lac 2', 'distance_km' => 12],
                // Ligne laissée vide : ignorée
                ['transport_mode_id' => '', 'from_location' => '', 'to_location' => '', 'distance_km' => ''],
            ],
        ])->assertRedirect();

        $shipment = Shipment::firstOrFail();
        $this->assertMatchesRegularExpression('/^SHP-\d{4}-0001$/', $shipment->reference);
        $legs = $shipment->legs;
        $this->assertCount(2, $legs);
        $this->assertSame([1, 2], $legs->pluck('position')->all());
        $this->assertEqualsWithDelta(76.5, (float) $legs[0]->co2_kg, 0.001);
        $this->assertEqualsWithDelta(14.4, (float) $legs[1]->co2_kg, 0.001);
        $this->assertTrue($legs[0]->from_recommendation);
        $this->assertFalse($legs[1]->from_recommendation);
        $this->assertEqualsWithDelta(36.7948, $legs[0]->to_latitude, 0.0001);
        // Le poids de chaque étape est celui de l'expédition
        $this->assertEquals(2000, $legs[1]->weight_kg);
        $this->assertEqualsWithDelta(462, (float) $shipment->total_distance_km, 0.001);
        $this->assertEqualsWithDelta(90.9, (float) $shipment->total_co2_kg, 0.001);

        // La fiche est une page de consultation : étapes en lecture seule, plus de formulaire d'étape ni d'IA
        $this->get(route('pro.distributor.shipments.show', $shipment))->assertOk()
            ->assertSee('Gare de Tunis')
            ->assertSee('Journal de traçabilité')
            ->assertSee('Changer le statut')
            ->assertDontSee('Ajouter une étape')
            ->assertDontSee('ai-recommend-btn', false);
    }

    public function test_validation_error_keeps_the_legs_already_entered(): void
    {
        $this->actingAs($this->distributor);

        $this->from(route('pro.distributor.shipments.create'))->post(route('pro.distributor.shipments.store'), [
            'product_name' => 'Huile',
            'weight_kg' => 1000,
            'origin_address' => 'Sfax',
            'status' => 'planned',
            'legs' => [
                ['transport_mode_id' => $this->mode('rail'), 'from_location' => 'Sfax', 'to_location' => 'Tunis', 'distance_km' => 270],
                ['transport_mode_id' => $this->mode('van'), 'from_location' => 'Tunis', 'to_location' => 'Lac 2', 'to_latitude' => 36.84],
            ],
        ])->assertRedirect(route('pro.distributor.shipments.create'))
            ->assertSessionHasErrors(['legs.1.distance_km', 'legs.1.to_longitude']);

        $this->assertDatabaseCount('shipments', 0);
        $this->assertDatabaseCount('shipment_legs', 0);

        // Le formulaire réaffiche toutes les lignes saisies
        $this->get(route('pro.distributor.shipments.create'))->assertOk()
            ->assertSee('name="legs[1][to_location]"', false)
            ->assertSee('value="Lac 2"', false)
            ->assertSee('distance (étape 2)');
    }

    public function test_legs_are_added_modified_and_removed_from_the_edit_form(): void
    {
        $this->actingAs($this->distributor);
        $this->post(route('pro.distributor.shipments.store'), [
            'product_name' => 'Dattes', 'weight_kg' => 2000, 'origin_address' => 'Tozeur', 'status' => 'planned',
            'legs' => [
                ['transport_mode_id' => $this->mode('truck_heavy'), 'from_location' => 'Tozeur', 'to_location' => 'Sfax', 'distance_km' => 250],
                ['transport_mode_id' => $this->mode('truck_heavy'), 'from_location' => 'Sfax', 'to_location' => 'Tunis', 'distance_km' => 270],
            ],
        ]);
        $shipment = Shipment::firstOrFail();
        [$first, $second] = $shipment->legs->all();

        // Le formulaire de modification est le même, pré-rempli avec les étapes
        $this->get(route('pro.distributor.shipments.edit', $shipment))->assertOk()
            ->assertSee('value="'.$first->id.'"', false)
            ->assertSee('value="Sfax"', false)
            ->assertSee('Recommandation IA');

        // Étape 1 modifiée (train), étape 2 supprimée, nouvelle étape ajoutée
        $this->put(route('pro.distributor.shipments.update', $shipment), [
            'product_name' => 'Dattes', 'weight_kg' => 2000, 'origin_address' => 'Tozeur', 'status' => 'in_transit',
            'legs' => [
                ['id' => $first->id, 'transport_mode_id' => $this->mode('rail'), 'from_location' => 'Tozeur', 'to_location' => 'Tunis', 'distance_km' => 500],
                ['transport_mode_id' => $this->mode('van'), 'from_location' => 'Tunis', 'to_location' => 'Lac 2', 'distance_km' => 10],
            ],
        ])->assertRedirect(route('pro.distributor.shipments.show', $shipment));

        $shipment->refresh();
        $legs = $shipment->legs;
        $this->assertSame('in_transit', $shipment->status);
        $this->assertCount(2, $legs);
        $this->assertSame($first->id, $legs[0]->id);
        $this->assertNull(ShipmentLeg::find($second->id));
        // 500 x 2 x 0,0052 = 5,2 ; 10 x 2 x 0,6 = 12
        $this->assertEqualsWithDelta(5.2, (float) $legs[0]->co2_kg, 0.001);
        $this->assertEqualsWithDelta(17.2, (float) $shipment->total_co2_kg, 0.001);

        // Un nouveau poids recalcule toutes les étapes
        $this->put(route('pro.distributor.shipments.update', $shipment), [
            'product_name' => 'Dattes', 'weight_kg' => 1000, 'origin_address' => 'Tozeur', 'status' => 'in_transit',
            'legs' => $legs->map(fn ($leg) => $leg->only(['id', 'transport_mode_id', 'from_location', 'to_location', 'distance_km']))->all(),
        ]);
        $this->assertEqualsWithDelta(8.6, (float) $shipment->fresh()->total_co2_kg, 0.001);

        // Toutes les étapes supprimées
        $this->put(route('pro.distributor.shipments.update', $shipment), [
            'product_name' => 'Dattes', 'weight_kg' => 1000, 'origin_address' => 'Tozeur', 'status' => 'in_transit',
        ]);
        $this->assertCount(0, $shipment->legs()->get());
        $this->assertEqualsWithDelta(0, (float) $shipment->fresh()->total_co2_kg, 0.001);
    }

    public function test_status_and_traceability_log_are_managed_from_the_show_page(): void
    {
        $shipment = $this->distributor->shipments()->create([
            'reference' => Shipment::nextReference(), 'product_name' => 'Huile', 'weight_kg' => 100, 'origin_address' => 'Sfax', 'status' => 'planned',
        ]);
        $this->actingAs($this->distributor);

        // Changement de statut : la date de départ est complétée
        $this->patch(route('pro.distributor.shipments.status', $shipment), ['status' => 'in_transit'])
            ->assertRedirect(route('pro.distributor.shipments.show', $shipment));
        $this->assertSame('in_transit', $shipment->fresh()->status);
        $this->assertNotNull($shipment->fresh()->departed_at);
        $this->patch(route('pro.distributor.shipments.status', $shipment), ['status' => 'delivered']);
        $this->assertNotNull($shipment->fresh()->delivered_at);
        $this->patch(route('pro.distributor.shipments.status', $shipment), ['status' => 'lost'])->assertSessionHasErrors('status');

        // Journal de traçabilité
        $this->post(route('pro.distributor.shipments.events.store', $shipment), [
            'type' => 'loaded',
            'location' => 'Port de Sfax',
            'occurred_at' => now()->format('Y-m-d H:i'),
        ])->assertRedirect();
        $event = $shipment->events()->firstOrFail();
        $this->get(route('pro.distributor.shipments.events.edit', [$shipment, $event]))->assertOk();
        $this->put(route('pro.distributor.shipments.events.update', [$shipment, $event]), [
            'type' => 'departed',
            'location' => 'Port de Sfax',
            'occurred_at' => now()->format('Y-m-d H:i'),
        ])->assertRedirect();
        $this->assertSame('departed', $event->fresh()->type);
        $this->get(route('pro.distributor.shipments.show', $shipment))->assertOk()->assertSee('Port de Sfax');
        $this->delete(route('pro.distributor.shipments.events.destroy', [$shipment, $event]))->assertRedirect();
        $this->assertDatabaseCount('shipment_events', 0);

        // Suppression de l'expédition
        $this->delete(route('pro.distributor.shipments.destroy', $shipment))->assertRedirect();
        $this->assertDatabaseCount('shipments', 0);
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
            'status' => 'planned',
        ]);
        $otherLeg = $shipment->legs()->make([
            'transport_mode_id' => $this->mode('van'), 'position' => 1, 'from_location' => 'Sfax', 'to_location' => 'Tunis',
            'distance_km' => 270, 'weight_kg' => 100,
        ])->calculateCo2();
        $otherLeg->save();
        $data = ['product_name' => 'Huile', 'weight_kg' => 100, 'origin_address' => 'Sfax', 'status' => 'cancelled'];

        $this->actingAs($this->distributor);
        $this->get(route('pro.distributor.shipments.index'))->assertDontSee($shipment->reference);
        $this->get(route('pro.distributor.shipments.show', $shipment))->assertForbidden();
        $this->get(route('pro.distributor.shipments.edit', $shipment))->assertForbidden();
        $this->put(route('pro.distributor.shipments.update', $shipment), $data)->assertForbidden();
        $this->patch(route('pro.distributor.shipments.status', $shipment), ['status' => 'cancelled'])->assertForbidden();
        $this->post(route('pro.distributor.shipments.events.store', $shipment), [
            'type' => 'loaded', 'occurred_at' => now()->format('Y-m-d H:i'),
        ])->assertForbidden();
        $this->delete(route('pro.distributor.shipments.destroy', $shipment))->assertForbidden();
        $this->assertSame('planned', $shipment->fresh()->status);

        // L'étape d'une autre expédition ne peut pas être reprise en passant son id dans le formulaire
        $this->post(route('pro.distributor.shipments.store'), $data + [
            'legs' => [['id' => $otherLeg->id, 'transport_mode_id' => $this->mode('rail'), 'from_location' => 'A', 'to_location' => 'B', 'distance_km' => 5]],
        ])->assertSessionHasErrors('legs.0.id');
        $this->assertSame($shipment->id, $otherLeg->fresh()->shipment_id);
        $this->assertEquals(270, $otherLeg->fresh()->distance_km);

        // Ni utiliser un point de vente d'un autre distributeur
        $foreignStore = PointOfSale::create([
            'user_id' => $other->id, 'name' => 'Magasin concurrent', 'type' => 'store', 'address' => 'Rue X', 'city' => 'Sousse',
        ]);
        $this->post(route('pro.distributor.shipments.store'), $data + ['point_of_sale_id' => $foreignStore->id])
            ->assertSessionHasErrors('point_of_sale_id');
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
