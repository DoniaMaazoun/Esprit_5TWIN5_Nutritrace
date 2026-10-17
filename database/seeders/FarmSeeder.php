<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Harvest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Données de démonstration du Module 1 (Producteur).
 */
class FarmSeeder extends Seeder
{
    public function run(): void
    {
        $producer = User::firstOrCreate(
            ['email' => 'producteur@test.tn'],
            [
                'name' => 'Producteur Approuvé',
                'password' => Hash::make('password123'),
                'role' => 'pro',
                'pro_type' => 'producer',
                'company_name' => 'Domaine Ben Salem',
                'tax_id' => '11223344556',
                'status' => 'approved',
            ]
        );

        $this->seedFarms($producer, [
            [
                'farm' => [
                    'name' => 'Domaine des Oliviers', 'governorate' => 'Sfax', 'city' => 'Agareb',
                    'latitude' => 34.7406, 'longitude' => 10.5280, 'total_area' => 42.5,
                    'farming_type' => 'organic', 'irrigation_type' => 'drip', 'soil_type' => 'sandy',
                    'main_crops' => 'olives Chemlali, amandes', 'certification' => 'Bio Tunisie (CNAB)',
                    'established_year' => 1987, 'status' => 'active',
                    'description' => "Oliveraie familiale de la région de Sfax, conduite en agriculture biologique depuis 2005. Les oliviers Chemlali, certains centenaires, sont irrigués au goutte-à-goutte et récoltés à la main.",
                ],
                'harvests' => [
                    ['Olive', 'olives', 'Chemlali', 'Parcelle Nord', 15, '2025-03-01', '2025-11-20', 6.2, 'tonne', 'A', 'sold'],
                    ['Olive', 'olives', 'Chemlali', 'Parcelle Sud', 18, '2025-03-01', '2025-12-10', 7.8, 'tonne', 'B', 'processed'],
                    ['Amande', 'fruits', 'Achaak', 'Verger Est', 6, null, '2026-08-25', 1350, 'kg', 'A', 'in_stock'],
                ],
            ],
            [
                'farm' => [
                    'name' => 'Ferme Cap Bon Primeurs', 'governorate' => 'Nabeul', 'city' => 'Menzel Temime',
                    'latitude' => 36.7810, 'longitude' => 10.9870, 'total_area' => 12,
                    'farming_type' => 'reasoned', 'irrigation_type' => 'drip', 'soil_type' => 'loamy',
                    'main_crops' => 'tomates, piments, fraises', 'certification' => 'GlobalG.A.P.',
                    'established_year' => 2010, 'status' => 'active',
                    'description' => null,
                ],
                'harvests' => [
                    ['Tomate', 'vegetables', 'Rio Grande', 'Serre 1', 2.5, '2026-03-15', '2026-07-05', 180, 'quintal', 'A', 'sold'],
                    ['Piment', 'vegetables', 'Baklouti', 'Plein champ', 3, '2026-04-01', '2026-08-12', 95, 'quintal', 'B', 'shipped'],
                    ['Fraise', 'fruits', 'Camarosa', 'Tunnel A', 1.2, '2025-10-10', '2026-04-02', 14500, 'kg', 'A', 'sold'],
                    ['Tomate', 'vegetables', 'Rio Grande', 'Serre 2', 2, '2026-05-20', '2026-09-28', 120, 'quintal', 'C', 'in_stock'],
                ],
            ],
            [
                'farm' => [
                    'name' => 'Palmeraie El Waha', 'governorate' => 'Tozeur', 'city' => 'Nefta',
                    'latitude' => 33.8730, 'longitude' => 7.8770, 'total_area' => 8,
                    'farming_type' => 'conventional', 'irrigation_type' => 'gravity', 'soil_type' => 'sandy',
                    'main_crops' => 'dattes Deglet Nour', 'certification' => null,
                    'established_year' => 1995, 'status' => 'active',
                    'description' => null,
                ],
                'harvests' => [
                    ['Datte Deglet Nour', 'dates', 'Deglet Nour', 'Oasis Ouest', 5, null, '2025-11-05', 42, 'quintal', 'B', 'sold'],
                    ['Datte Deglet Nour', 'dates', 'Deglet Nour', 'Oasis Est', 3, null, '2026-10-10', 30, 'quintal', 'C', 'in_stock'],
                ],
            ],
            [
                'farm' => [
                    'name' => 'Terres de Béja', 'governorate' => 'Béja', 'city' => 'Testour',
                    'latitude' => null, 'longitude' => null, 'total_area' => 60,
                    'farming_type' => 'conventional', 'irrigation_type' => 'rainfed', 'soil_type' => 'clay',
                    'main_crops' => 'blé dur, orge', 'certification' => null,
                    'established_year' => 2001, 'status' => 'fallow',
                    'description' => null,
                ],
                'harvests' => [
                    ['Blé dur', 'cereals', 'Karim', 'Grande parcelle', 45, '2024-11-20', '2025-06-15', 1350, 'quintal', 'A', 'sold'],
                ],
            ],
        ]);

        // Second producteur : ses produits sont visibles mais non modifiables par le premier
        $producer2 = User::firstOrCreate(
            ['email' => 'producteur2@test.tn'],
            [
                'name' => 'Second Producteur',
                'password' => Hash::make('password123'),
                'role' => 'pro',
                'pro_type' => 'producer',
                'company_name' => 'Coopérative El Hana',
                'tax_id' => '66554433221',
                'status' => 'approved',
            ]
        );

        $this->seedFarms($producer2, [
            [
                'farm' => [
                    'name' => 'Vergers El Hana', 'governorate' => 'Kairouan', 'city' => 'Haffouz',
                    'latitude' => 35.6340, 'longitude' => 9.6760, 'total_area' => 20,
                    'farming_type' => 'organic', 'irrigation_type' => 'drip', 'soil_type' => 'limestone',
                    'main_crops' => 'abricots, grenades, piments', 'certification' => 'Bio Tunisie (CNAB)',
                    'established_year' => 2008, 'status' => 'active',
                    'description' => "Coopérative de petits producteurs de la région de Kairouan, spécialisée dans les fruits d'été en agriculture biologique.",
                ],
                'harvests' => [
                    ['Abricot', 'fruits', 'Amor Leuch', 'Verger 1', 5, null, '2026-06-02', 85, 'quintal', 'A', 'sold'],
                    ['Grenade', 'fruits', 'Gabsi', 'Verger 2', 4, null, '2026-10-05', 62, 'quintal', 'A', 'in_stock'],
                    ['Piment', 'vegetables', 'Baklouti', 'Parcelle 3', 2, '2026-04-10', '2026-08-20', 4200, 'kg', 'B', 'in_stock'],
                ],
            ],
        ]);
    }

    private function seedFarms(User $producer, array $farms): void
    {
        if ($producer->farms()->exists()) {
            return;
        }

        // Codes utilisés ci-dessus → catégories créées par la migration
        $slugs = ['vegetables' => 'legumes', 'fruits' => 'fruits', 'cereals' => 'cereales', 'olives' => 'olives',
                  'dates' => 'dattes', 'citrus' => 'agrumes', 'legumes' => 'legumineuses', 'herbs' => 'plantes-aromatiques'];
        $categoryIds = Category::pluck('id', 'slug');

        foreach ($farms as $data) {
            $farm = $producer->farms()->create($data['farm']);

            foreach ($data['harvests'] as [$product, $category, $variety, $plot, $area, $planted, $harvested, $qty, $unit, $grade, $status]) {
                // DatabaseSeeder désactive les événements de modèle : le code de lot est généré ici
                $farm->harvests()->make([
                    'product_name' => $product, 'category_id' => $categoryIds[$slugs[$category]] ?? null, 'variety' => $variety,
                    'plot_name' => $plot, 'plot_area' => $area, 'planting_date' => $planted,
                    'harvest_date' => $harvested, 'quantity' => $qty, 'unit' => $unit,
                    'quality_grade' => $grade, 'status' => $status,
                ])->forceFill(['batch_code' => Harvest::generateBatchCode($harvested)])->save();
            }
        }
    }
}
