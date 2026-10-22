<?php

namespace Tests\Unit;

use App\Models\Farm;
use App\Models\Harvest;
use Tests\TestCase;

/**
 * Tests unitaires des calculs des modèles Harvest et Farm (modèles en mémoire, sans base).
 */
class HarvestAndFarmModelTest extends TestCase
{
    public function test_quantity_is_converted_to_kilograms(): void
    {
        $this->assertSame(250.0, (new Harvest(['quantity' => 250, 'unit' => 'kg']))->quantityInKg());
        $this->assertSame(300.0, (new Harvest(['quantity' => 3, 'unit' => 'quintal']))->quantityInKg());
        $this->assertSame(1500.0, (new Harvest(['quantity' => 1.5, 'unit' => 'tonne']))->quantityInKg());
    }

    public function test_yield_per_hectare_needs_a_plot_area(): void
    {
        $this->assertSame(2000.0, (new Harvest(['quantity' => 3, 'unit' => 'tonne', 'plot_area' => 1.5]))->yieldPerHectare());
        $this->assertNull((new Harvest(['quantity' => 3, 'unit' => 'tonne']))->yieldPerHectare());
    }

    public function test_growing_days_between_planting_and_harvest(): void
    {
        $harvest = new Harvest(['planting_date' => '2026-03-01', 'harvest_date' => '2026-07-05']);

        $this->assertSame(126, $harvest->growingDays());
        $this->assertNull((new Harvest(['harvest_date' => '2026-07-05']))->growingDays());
    }

    public function test_labels_are_translated(): void
    {
        $harvest = new Harvest(['unit' => 'quintal', 'status' => 'shipped']);

        $this->assertSame('q', $harvest->unitShort());
        $this->assertSame('Expédié', $harvest->statusLabel());
        $this->assertSame('Sans catégorie', $harvest->categoryLabel());
    }

    public function test_farm_total_production_and_yield(): void
    {
        $farm = new Farm(['total_area' => 4]);
        $farm->setRelation('harvests', collect([
            new Harvest(['quantity' => 2, 'unit' => 'tonne']),
            new Harvest(['quantity' => 10, 'unit' => 'quintal']),
        ]));

        $this->assertSame(3000.0, $farm->totalHarvestedKg());
        $this->assertSame(750.0, $farm->yieldPerHectare());
    }

    public function test_farm_location_and_labels(): void
    {
        $farm = new Farm(['farming_type' => 'organic', 'irrigation_type' => 'rainfed', 'status' => 'fallow']);

        $this->assertFalse($farm->hasLocation());
        $this->assertSame('Biologique', $farm->farmingTypeLabel());
        $this->assertSame('Pluviale (sans irrigation)', $farm->irrigationTypeLabel());
        $this->assertSame('En jachère', $farm->statusLabel());

        $farm->latitude = 36.8;
        $farm->longitude = 10.2;
        $this->assertTrue($farm->hasLocation());
    }
}
