<?php

namespace Database\Seeders;

use App\Models\TransportMode;
use Illuminate\Database\Seeder;

class TransportModeSeeder extends Seeder
{
    /**
     * Module 3 - Facteurs d'émission par mode de transport (kg CO2e / tonne.km)
     * Valeurs indicatives inspirées de la Base Carbone ADEME, modifiables par l'admin
     */
    public function run(): void
    {
        $modes = [
            ['code' => 'truck_heavy', 'name' => 'Camion poids lourd (40 t)', 'emission_factor' => 0.08500],
            ['code' => 'truck_light', 'name' => 'Camion porteur (12-19 t)', 'emission_factor' => 0.20000],
            ['code' => 'van', 'name' => 'Véhicule utilitaire léger (< 3,5 t)', 'emission_factor' => 0.60000],
            ['code' => 'rail', 'name' => 'Train de fret', 'emission_factor' => 0.00520],
            ['code' => 'river', 'name' => 'Bateau fluvial', 'emission_factor' => 0.03300],
            ['code' => 'sea', 'name' => 'Bateau porte-conteneurs', 'emission_factor' => 0.01200],
            ['code' => 'air', 'name' => 'Avion cargo', 'emission_factor' => 1.06000],
        ];

        foreach ($modes as $mode) {
            TransportMode::updateOrCreate(
                ['code' => $mode['code']],
                $mode + ['source' => 'ADEME Base Carbone (indicatif)', 'is_active' => true]
            );
        }
    }
}
