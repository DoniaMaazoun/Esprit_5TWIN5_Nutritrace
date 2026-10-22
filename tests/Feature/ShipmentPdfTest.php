<?php

namespace Tests\Feature;

use App\Models\Harvest;
use App\Models\ProductionLot;
use App\Models\Shipment;
use App\Models\TransportMode;
use App\Models\User;
use Database\Seeders\TransportModeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProductionChain;
use Tests\TestCase;

/**
 * Module 3 - Export PDF du rapport de traçabilité d'une expédition
 */
class ShipmentPdfTest extends TestCase
{
    use BuildsProductionChain, RefreshDatabase;

    private User $distributor;

    private Shipment $shipment;

    private ProductionLot $lot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TransportModeSeeder::class);
        $this->distributor = $this->makeDistributor();

        // Huile produite par un transformateur à partir d'olives du Domaine des Oliviers
        $this->lot = $this->productionLot('Huile vierge extra Chemlali', 'Olive', [
            'production_date' => '2026-10-05', 'expiry_date' => '2027-10-05',
        ], ['name' => 'Domaine des Oliviers', 'governorate' => 'Sfax', 'city' => 'Agareb'], processor: ['company_name' => 'Huilerie Test']);

        $this->shipment = Shipment::create([
            'reference' => Shipment::nextReference(),
            'user_id' => $this->distributor->id,
            'production_lot_id' => $this->lot->id,
            'product_name' => 'Huile vierge extra Chemlali',
            'weight_kg' => 2000,
            'origin_address' => 'Huilerie, Sfax',
            'status' => 'in_transit',
        ]);
        $this->shipment->legs()->make([
            'transport_mode_id' => TransportMode::where('code', 'rail')->value('id'),
            'position' => 1, 'from_location' => 'Gare de Sfax', 'to_location' => 'Gare de Tunis',
            'distance_km' => 270, 'weight_kg' => 2000, 'from_recommendation' => true,
        ])->calculateCo2()->save();
        $this->shipment->recalculateTotals();
        $this->shipment->events()->create(['type' => 'loaded', 'location' => 'Huilerie, Sfax', 'occurred_at' => '2026-10-17 08:00']);
    }

    private function makeDistributor(): User
    {
        return User::factory()->create([
            'role' => 'pro', 'pro_type' => 'distributor', 'company_name' => 'Distrib Test', 'status' => 'approved',
        ]);
    }

    public function test_owner_downloads_the_traceability_report(): void
    {
        $this->actingAs($this->distributor)->get(route('pro.distributor.shipments.show', $this->shipment))
            ->assertSee('Télécharger le PDF');

        $response = $this->get(route('pro.distributor.shipments.pdf', $this->shipment))->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString("rapport-tracabilite-{$this->shipment->reference}.pdf", $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pdf_is_reserved_to_the_owner(): void
    {
        $url = route('pro.distributor.shipments.pdf', $this->shipment);

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->makeDistributor())->get($url)->assertForbidden();

        $producer = User::factory()->create(['role' => 'pro', 'pro_type' => 'producer', 'status' => 'approved']);
        $this->actingAs($producer)->get($url)->assertRedirect(route('pro.dashboard'));
    }

    public function test_report_contains_production_lot_origin_legs_events_and_total_co2(): void
    {
        $this->actingAs($this->distributor);
        $shipment = $this->shipment->load(['user', 'productionLot.recipe.items', 'productionLot.processor', 'pointOfSale', 'legs.transportMode', 'events']);

        $html = view('pro.distributor.shipments.pdf', [
            'shipment' => $shipment,
            'originHarvests' => $shipment->originHarvests(),
        ])->render();

        $this->assertStringContainsString($shipment->reference, $html);
        // Section « Lot de production »
        $this->assertStringContainsString('Lot de production', $html);
        $this->assertStringContainsString($this->lot->qr_code_data, $html);
        $this->assertStringContainsString('Huilerie Test', $html);
        $this->assertStringContainsString('05/10/2027', $html);
        // Traçabilité jusqu'à la récolte d'olives
        $this->assertStringContainsString(Harvest::firstOrFail()->batch_code, $html);
        $this->assertStringContainsString('Domaine des Oliviers', $html);
        $this->assertStringContainsString('Train de fret', $html);
        $this->assertStringContainsString('Gare de Sfax → Gare de Tunis', $html);
        $this->assertStringContainsString("recommandé par l'IA", $html);
        $this->assertStringContainsString('Huilerie, Sfax', $html);
        // 270 km x 2 t x 0,0052 = 2,81 kg
        $this->assertStringContainsString('2,81', $html);
    }

    public function test_report_without_production_lot_says_so(): void
    {
        $this->shipment->update(['production_lot_id' => null]);

        $html = view('pro.distributor.shipments.pdf', ['shipment' => $this->shipment->fresh()->load(['user', 'pointOfSale', 'legs.transportMode', 'events'])])->render();

        $this->assertStringContainsString('Aucun lot de production relié à cette expédition.', $html);
    }
}
