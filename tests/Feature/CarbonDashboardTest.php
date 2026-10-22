<?php

namespace Tests\Feature;

use App\Models\PointOfSale;
use App\Models\Shipment;
use App\Models\TransportMode;
use App\Models\User;
use App\Services\CarbonDashboard;
use Database\Seeders\TransportModeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\BuildsProductionChain;
use Tests\TestCase;

/**
 * Module 3 - Dashboard carbone et carte du trajet
 */
class CarbonDashboardTest extends TestCase
{
    use BuildsProductionChain, RefreshDatabase;

    private User $distributor;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-18 12:00:00');
        $this->seed(TransportModeSeeder::class);

        $this->distributor = $this->makeDistributor();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeDistributor(): User
    {
        return User::factory()->create([
            'role' => 'pro', 'pro_type' => 'distributor', 'company_name' => 'Distrib Test', 'status' => 'approved',
        ]);
    }

    /**
     * @param  array<int, array{0: string, 1: float, 2?: bool, 3?: array}>  $legs  [code, km, recommandée, attributs]
     */
    private function shipment(User $user, string $status, string $departedAt, float $weightKg, array $legs, array $attributes = []): Shipment
    {
        $shipment = Shipment::create($attributes + [
            'reference' => Shipment::nextReference(),
            'user_id' => $user->id,
            'product_name' => 'Olives',
            'weight_kg' => $weightKg,
            'origin_address' => 'Sfax',
            'status' => $status,
            'departed_at' => $departedAt,
        ]);

        foreach ($legs as $position => $leg) {
            $shipment->legs()->make(($leg[3] ?? []) + [
                'transport_mode_id' => TransportMode::where('code', $leg[0])->value('id'),
                'position' => $position + 1,
                'from_location' => 'A',
                'to_location' => 'B'.($position + 1),
                'distance_km' => $leg[1],
                'weight_kg' => $weightKg,
                'from_recommendation' => $leg[2] ?? false,
            ])->calculateCo2()->save();
        }
        $shipment->recalculateTotals();

        return $shipment;
    }

    private function seedShipments(): void
    {
        // Ce mois, livrée : train 450 km (4,68 kg, recommandé par l'IA) + camion porteur 30 km (12 kg)
        $this->shipment($this->distributor, 'delivered', '2026-10-05 08:00', 2000, [['rail', 450, true], ['truck_light', 30]]);
        // Il y a 2 mois : poids lourd 100 km, 1 t (8,5 kg)
        $this->shipment($this->distributor, 'planned', '2026-08-18 08:00', 1000, [['truck_heavy', 100]]);
        // Exclues : annulée, hors des 12 mois, autre distributeur
        $this->shipment($this->distributor, 'cancelled', '2026-10-10 08:00', 1000, [['air', 500]]);
        $this->shipment($this->distributor, 'delivered', '2025-08-01 08:00', 1000, [['air', 500]]);
        $this->shipment($this->makeDistributor(), 'delivered', '2026-10-10 08:00', 1000, [['air', 500]]);
    }

    public function test_dashboard_indicators_over_twelve_months(): void
    {
        $this->seedShipments();

        $stats = app(CarbonDashboard::class)->forUser($this->distributor, '12m');

        $this->assertSame(2, $stats['shipment_count']);
        $this->assertSame(1, $stats['delivered_count']);
        $this->assertEqualsWithDelta(25.18, $stats['total_co2_kg'], 0.001);
        $this->assertEqualsWithDelta(580.0, $stats['total_distance_km'], 0.001);
        // Seule l'expédition livrée compte : 16,68 kg / 2 t
        $this->assertEqualsWithDelta(8.34, $stats['co2_per_tonne'], 0.001);
        // Étape recommandée : 450 km x 2 t x 0,2 (camion porteur) - 4,68 kg
        $this->assertSame(1, $stats['savings']['legs']);
        $this->assertEqualsWithDelta(175.32, $stats['savings']['co2_kg'], 0.001);
        $this->assertSame([
            'Camion porteur (12-19 t)' => 12.0,
            'Camion poids lourd (40 t)' => 8.5,
            'Train de fret' => 4.68,
        ], $stats['by_mode']);
        $this->assertCount(12, $stats['by_month']);
        $this->assertSame('nov. 2025', array_key_first($stats['by_month']));
        $this->assertEqualsWithDelta(16.68, $stats['by_month']['oct. 2026'], 0.001);
        $this->assertEqualsWithDelta(8.5, $stats['by_month']['août 2026'], 0.001);
        $this->assertEquals(0, $stats['by_month']['sept. 2026']);
    }

    public function test_dashboard_period_filter(): void
    {
        $this->seedShipments();
        $dashboard = app(CarbonDashboard::class);

        $month = $dashboard->forUser($this->distributor, 'month');
        $this->assertSame(1, $month['shipment_count']);
        $this->assertEqualsWithDelta(16.68, $month['total_co2_kg'], 0.001);
        $this->assertSame(['oct. 2026'], array_keys($month['by_month']));

        $quarter = $dashboard->forUser($this->distributor, '3m');
        $this->assertSame(2, $quarter['shipment_count']);
        $this->assertSame(['août 2026', 'sept. 2026', 'oct. 2026'], array_keys($quarter['by_month']));

        // Période inconnue : 12 mois par défaut
        $this->assertSame('12m', $dashboard->forUser($this->distributor, 'abc')['period']);
    }

    public function test_dashboard_without_shipments(): void
    {
        $stats = app(CarbonDashboard::class)->forUser($this->distributor);

        $this->assertSame(0, $stats['shipment_count']);
        $this->assertEquals(0, $stats['total_co2_kg']);
        $this->assertNull($stats['co2_per_tonne']);
        $this->assertSame(0, $stats['savings']['legs']);
        $this->assertSame([], $stats['by_mode']);
    }

    public function test_dashboard_page(): void
    {
        $this->seedShipments();

        $this->get(route('pro.distributor.dashboard'))->assertRedirect(route('login'));

        $this->actingAs($this->distributor)->get(route('pro.distributor.dashboard', ['period' => '3m']))
            ->assertOk()
            ->assertSee('Dashboard carbone')
            ->assertSee('3 derniers mois')
            ->assertSee('co2ByModeChart')
            ->assertSee('kg CO2e par tonne livrée');
    }

    public function test_leg_coordinates_and_recommendation_flag_are_saved(): void
    {
        $shipment = $this->shipment($this->distributor, 'planned', '2026-10-17 08:00', 1000, []);

        $this->actingAs($this->distributor)->post(route('pro.distributor.shipments.legs.store', $shipment), [
            'transport_mode_id' => TransportMode::where('code', 'truck_heavy')->value('id'),
            'from_location' => 'Sfax',
            'to_location' => 'Tunis',
            'to_latitude' => 36.8065,
            'to_longitude' => 10.1815,
            'distance_km' => 270,
            'weight_kg' => 1000,
            'from_recommendation' => 1,
        ])->assertRedirect(route('pro.distributor.shipments.show', $shipment));

        $leg = $shipment->legs()->firstOrFail();
        $this->assertEqualsWithDelta(36.8065, $leg->to_latitude, 0.0001);
        $this->assertTrue($leg->from_recommendation);

        // Une latitude sans longitude est refusée
        $this->post(route('pro.distributor.shipments.legs.store', $shipment), [
            'transport_mode_id' => TransportMode::where('code', 'van')->value('id'),
            'from_location' => 'Tunis', 'to_location' => 'Lac 2', 'to_latitude' => 36.84,
            'distance_km' => 12, 'weight_kg' => 1000,
        ])->assertSessionHasErrors('to_longitude');
    }

    public function test_map_data_draws_known_legs_and_reports_missing_coordinates(): void
    {
        $lot = $this->productionLot('Huile vierge extra', 'Olive');
        $store = PointOfSale::create([
            'user_id' => $this->distributor->id, 'name' => 'Épicerie Bio', 'type' => 'store', 'address' => 'Lac 2', 'city' => 'Tunis',
            'latitude' => 36.8437, 'longitude' => 10.2733,
        ]);

        // Étape 1 géolocalisée ; étape 2 sans coordonnées, mais la destination est connue
        $shipment = $this->shipment($this->distributor, 'planned', '2026-10-17 08:00', 1000, [
            ['rail', 270, false, ['to_latitude' => 36.7948, 'to_longitude' => 10.1802]],
            ['van', 12],
        ], ['production_lot_id' => $lot->id, 'point_of_sale_id' => $store->id]);

        // Le transformateur n'a pas de coordonnées : départ signalé, le reste du trajet est tracé
        $map = $shipment->fresh()->mapData();
        $this->assertCount(1, $map['segments']);
        $this->assertSame([36.7948, 10.1802], $map['segments'][0]['from']);
        $this->assertSame([36.8437, 10.2733], $map['segments'][0]['to']);
        $this->assertSame(Shipment::MODE_COLORS['van'], $map['segments'][0]['color']);
        $this->assertSame(["Départ : le transformateur Usine Test n'a pas de coordonnées"], $map['missing']);

        // Si le site du transformateur est géolocalisé, il devient le point de départ
        $located = $shipment->fresh()->load('productionLot.processor');
        $located->productionLot->processor->forceFill(['latitude' => 35.8256, 'longitude' => 10.6084]);
        $map = $located->mapData();
        $this->assertCount(2, $map['segments']);
        $this->assertSame([35.8256, 10.6084], $map['segments'][0]['from']);
        $this->assertSame(Shipment::MODE_COLORS['rail'], $map['segments'][0]['color']);
        $this->assertSame('start', $map['markers'][0]['type']);
        $this->assertSame([], $map['missing']);

        // Sans coordonnées pour l'étape 1, elle est signalée et le trajet n'est pas cassé
        $shipment->legs()->where('position', 1)->update(['to_latitude' => null, 'to_longitude' => null]);
        $map = $shipment->fresh()->mapData();
        $this->assertCount(0, $map['segments']);
        $this->assertSame(["Départ : le transformateur Usine Test n'a pas de coordonnées", 'Étape 1 : B1'], $map['missing']);

        $this->actingAs($this->distributor)->get(route('pro.distributor.shipments.show', $shipment))
            ->assertOk()
            ->assertSee('Carte du trajet')
            ->assertSee('shipment-map')
            ->assertSee('faute de coordonnées')
            ->assertSee("n'a pas de coordonnées");
    }

    public function test_map_without_production_lot_does_not_break(): void
    {
        $shipment = $this->shipment($this->distributor, 'planned', '2026-10-17 08:00', 1000, [['van', 12]]);

        $map = $shipment->fresh()->mapData();

        $this->assertSame([], $map['markers']);
        $this->assertSame(['Départ : aucun lot de production relié', 'Étape 1 : B1'], $map['missing']);
        $this->actingAs($this->distributor)->get(route('pro.distributor.shipments.show', $shipment))
            ->assertOk()
            ->assertSee('Aucune position connue pour ce trajet');
    }
}
