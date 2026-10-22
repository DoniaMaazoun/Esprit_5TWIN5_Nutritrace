<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Farm;
use App\Models\Harvest;
use App\Services\ProductIntelligence;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tests unitaires des métiers avancés du Module Produit (sans base de données).
 */
class ProductIntelligenceTest extends TestCase
{
    private ProductIntelligence $intel;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 10:00:00');
        $this->intel = app(ProductIntelligence::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Lot en mémoire avec sa catégorie et son exploitation (non enregistrés).
     */
    private function lot(array $attributes = [], string $categorySlug = 'legumes', array $farm = []): Harvest
    {
        // forceFill : image_path n'est volontairement pas « fillable » (la photo passe par syncImage)
        $harvest = (new Harvest)->forceFill(array_merge([
            'product_name' => 'Tomate',
            'harvest_date' => '2026-10-17',
            'quantity' => 100,
            'unit' => 'kg',
            'quality_grade' => 'B',
            'status' => 'in_stock',
        ], $attributes));

        $category = (new Category(['name' => 'Catégorie']))->forceFill(['slug' => $categorySlug]);
        $harvest->setRelation('category', $category);
        $harvest->setRelation('farm', new Farm(array_merge([
            'name' => 'Ferme test', 'governorate' => 'Nabeul', 'city' => 'Korba', 'total_area' => 10,
            'farming_type' => 'conventional', 'irrigation_type' => 'drip', 'status' => 'active',
            'latitude' => 36.5, 'longitude' => 10.8,
        ], $farm)));

        return $harvest;
    }

    // ---------- Fraîcheur ----------

    public function test_freshness_uses_ambient_shelf_life_of_the_category(): void
    {
        // Légumes : 7 jours à température ambiante ; récolté il y a 3 jours → 4 jours restants
        $fresh = $this->intel->freshness($this->lot());

        $this->assertFalse($fresh['cold']);
        $this->assertSame(7, $fresh['days']);
        $this->assertSame(4, $fresh['days_left']);
        $this->assertSame(57, $fresh['percent']);
        $this->assertSame('mid', $fresh['level']);
        $this->assertSame('2026-10-24', $fresh['best_before']->toDateString());
    }

    public function test_cold_storage_extends_shelf_life(): void
    {
        $fresh = $this->intel->freshness($this->lot(['storage_conditions' => 'Chambre froide 4 °C']));

        $this->assertTrue($fresh['cold']);
        $this->assertSame(21, $fresh['days']);
        $this->assertSame('high', $fresh['level']);
    }

    public function test_lot_past_its_best_before_date_is_expired(): void
    {
        $fresh = $this->intel->freshness($this->lot(['harvest_date' => '2026-10-01']));

        $this->assertSame('expired', $fresh['level']);
        $this->assertSame(0, $fresh['percent']);
        $this->assertLessThan(0, $fresh['days_left']);
    }

    public function test_sold_lot_is_no_longer_tracked(): void
    {
        $fresh = $this->intel->freshness($this->lot(['status' => 'sold', 'harvest_date' => '2026-01-01']));

        $this->assertFalse($fresh['active']);
        $this->assertSame('done', $fresh['level']);
    }

    // ---------- Prix conseillé ----------

    public function test_price_applies_quality_organic_and_certification_factors(): void
    {
        $price = $this->intel->price($this->lot(
            ['quality_grade' => 'A', 'quantity' => 2, 'unit' => 'quintal'],
            'legumes',
            ['farming_type' => 'organic', 'certification' => 'Ecocert'],
        ));

        // Légumes [1,2 ; 3,5] × 1,15 (A) × 1,3 (bio) × 1,05 (certifié) = × 1,56975
        $this->assertSame(1.88, $price['min']);
        $this->assertSame(5.49, $price['max']);
        $this->assertEqualsWithDelta(3.685, $price['median'], 0.006);
        $this->assertEqualsWithDelta($price['median'] * 200, $price['value'], 1);
        $this->assertCount(3, $price['factors']);
    }

    public function test_grade_c_lowers_the_price(): void
    {
        $base = $this->intel->price($this->lot(['quality_grade' => 'B']));
        $low = $this->intel->price($this->lot(['quality_grade' => 'C']));

        $this->assertLessThan($base['median'], $low['median']);
    }

    // ---------- Score de conformité ----------

    public function test_complete_and_consistent_declaration_is_compliant(): void
    {
        $risk = $this->intel->risk($this->lot([
            'image_path' => 'harvests/photo.jpg',
            'planting_date' => '2026-07-01',
            'plot_area' => 2,
            'quantity' => 60000, // 30 000 kg/ha : dans la norme des légumes
        ]));

        $this->assertSame(0, $risk['score']);
        $this->assertSame('low', $risk['level']);
        $this->assertSame([], $risk['reasons']);
    }

    public function test_abnormally_high_yield_is_flagged_as_high_risk(): void
    {
        $risk = $this->intel->risk($this->lot(['plot_area' => 1, 'quantity' => 200000]));

        $this->assertGreaterThanOrEqual(50, $risk['score']);
        $this->assertSame('high', $risk['level']);
        $this->assertStringContainsString('surdéclaration', $risk['reasons'][0][1]);
    }

    public function test_harvest_outside_the_usual_season_is_flagged(): void
    {
        // Olives : récolte habituelle d'octobre à janvier
        $risk = $this->intel->risk($this->lot(['harvest_date' => '2026-07-10', 'status' => 'sold'], 'olives'));

        $this->assertTrue(collect($risk['reasons'])->contains(fn ($r) => str_contains($r[1], 'hors de la saison')));
    }

    public function test_too_short_growing_cycle_is_flagged(): void
    {
        $risk = $this->intel->risk($this->lot(['planting_date' => '2026-10-15']));

        $this->assertTrue(collect($risk['reasons'])->contains(fn ($r) => str_contains($r[1], 'Cycle de culture de 2 jours')));
    }

    // ---------- Prévision ----------

    public function test_forecast_extends_a_growing_trend(): void
    {
        $forecast = $this->intel->forecast(array_map(fn ($i) => 100 * $i, range(0, 11)));

        $this->assertEquals([1200, 1300, 1400], $forecast['values']);
        $this->assertSame('hausse', $forecast['trend']);
    }

    public function test_forecast_is_flat_without_history(): void
    {
        $forecast = $this->intel->forecast(array_fill(0, 12, 0));

        $this->assertSame([0, 0, 0], $forecast['values']);
        $this->assertSame('stable', $forecast['trend']);
    }

    public function test_forecast_never_predicts_negative_quantities(): void
    {
        $forecast = $this->intel->forecast([1100, 1000, 900, 800, 700, 600, 500, 400, 300, 200, 100, 0]);

        $this->assertSame('baisse', $forecast['trend']);
        $this->assertEquals([0, 0, 0], $forecast['values']);
    }
}
