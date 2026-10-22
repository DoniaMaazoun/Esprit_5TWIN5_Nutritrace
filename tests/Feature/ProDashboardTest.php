<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * /pro/dashboard affiche le tableau de bord propre à chaque type de professionnel
 */
class ProDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function pro(string $type): User
    {
        return User::factory()->create([
            'role' => 'pro',
            'pro_type' => $type,
            'company_name' => 'Société ' . $type,
            'status' => 'approved',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    public function test_producer_sees_the_producer_dashboard(): void
    {
        $this->actingAs($this->pro('producer'))->get(route('pro.dashboard'))
            ->assertOk()
            ->assertViewIs('pro.producer-dashboard')
            ->assertDontSee('Espace Transformateur');
    }

    public function test_processor_sees_the_processor_dashboard(): void
    {
        $this->actingAs($this->pro('processor'))->get(route('pro.dashboard'))
            ->assertOk()
            ->assertViewIs('pro.dashboard')
            ->assertSee('Espace Transformateur');
    }

    public function test_distributor_sees_the_distributor_dashboard(): void
    {
        $this->actingAs($this->pro('distributor'))->get(route('pro.dashboard'))
            ->assertOk()
            ->assertViewIs('pro.distributor.home')
            ->assertDontSee('Espace Transformateur')
            ->assertSee('Tableau de Bord Distributeur')
            ->assertSee('Suivi des Transports & Kilométrage')
            ->assertSee('Points de Vente')
            ->assertSee('Statuts des Stocks')
            ->assertSee(route('pro.distributor.shipments.index'))
            ->assertSee(route('pro.distributor.points-of-sale.index'));
    }
}
