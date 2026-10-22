<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Harvest;
use App\Models\ProductionLot;
use App\Models\Recipe;
use App\Models\RawMaterialReception;
use App\Models\User;
use App\Services\AiAssistant;
use App\Services\ProcessorAiAssistant;
use App\Services\ProductIntelligence;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProDashboardController extends Controller
{
    /**
     * Affiche le tableau de bord des professionnels
     * Accessible uniquement pour role='pro' et status='approved'
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Vérifications de sécurité (redondant mais utile)
        if ($user->role !== 'pro' || $user->status !== 'approved') {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Accès refusé. Votre compte n\'est pas approuvé.');
        }

        // Producteur : tableau de bord du Module 1 (exploitations & récoltes)
        if ($user->isProducer()) {
            return $this->producerDashboard($user);
        }

        // Récupérer les données de l'utilisateur
        $proType = $user->pro_type;
        $companyName = $user->company_name;
        $taxId = $user->tax_id;

        // Dashboard KPIs pour les transformateurs
        $dashboardKpis = [];
        $wastePreventionRecommendation = null;
        
        if ($proType === 'processor') {
            $dashboardKpis = [
                'pending_harvests' => \App\Models\Harvest::where('status', 'in_stock')->count(),
                'active_recipes' => \App\Models\Recipe::where('processor_id', $user->id)->where('is_active', true)->count(),
                'total_receptions' => \App\Models\RawMaterialReception::where('processor_id', $user->id)->count(),
                'total_recipes' => \App\Models\Recipe::where('processor_id', $user->id)->count(),
                'total_production_lots' => \App\Models\ProductionLot::where('processor_id', $user->id)->count(),
                'production_this_month' => \App\Models\ProductionLot::where('processor_id', $user->id)
                    ->whereMonth('production_date', now()->month)
                    ->whereYear('production_date', now()->year)
                    ->count(),
            ];
            
            // Get AI waste prevention recommendation
            $processorAi = app(ProcessorAiAssistant::class);
            $wastePreventionRecommendation = $processorAi->getWastePreventionRecommendation($user);
        }

        // Préparer les données selon le type de professionnel
        $dashboardData = match($proType) {
            'producer' => [
                'title' => 'Tableau de Bord Producteur',
                'icon' => 'fa-leaf',
                'color' => '#28a745',
                'modules' => [
                    [
                        'title' => 'Gestion des Exploitations',
                        'icon' => 'fa-tree',
                        'description' => 'Créez et gérez vos fermes et parcelles : localisation sur carte, surfaces, pratiques culturales et description rédigée par l\'IA.',
                        'route' => route('pro.farms.index'),
                        'badge' => $user->farms()->count() . ' exploitation(s)'
                    ],
                    [
                        'title' => 'Origine des Récoltes',
                        'icon' => 'fa-basket-shopping',
                        'description' => 'Déclarez vos lots de récolte avec dates, quantités et qualité. Rendement et cycle de culture calculés automatiquement.',
                        'route' => route('pro.harvests.index'),
                        'badge' => 'Disponible'
                    ],
                    [
                        'title' => 'QR Codes & Assistant IA',
                        'icon' => 'fa-qrcode',
                        'description' => 'Chaque lot reçoit un QR code menant à sa page d\'origine. AgriBot répond à vos questions et analyse vos exploitations.',
                        'route' => route('pro.harvests.index'),
                        'badge' => 'Disponible'
                    ],
                ]
            ],
            'processor' => [
                'title' => 'Tableau de Bord Transformateur',
                'icon' => 'fa-industry',
                'color' => '#28a745',
                'modules' => [
                    [
                        'title' => 'Réception de Lots',
                        'icon' => 'fa-inbox',
                        'description' => 'Recevez et enregistrez les lots provenant de producteurs. Vérifiez les quantités et certificats.',
                        'route' => route('pro.processor.raw-materials.index'),
                        'badge' => 'Actif'
                    ],
                    [
                        'title' => 'Transformations & Assemblages',
                        'icon' => 'fa-gears',
                        'description' => 'Enregistrez les processus de transformation. Tracez les ingrédients et ajouts. Créez de nouveaux lots.',
                        'route' => route('pro.processor.recipes.index'),
                        'badge' => 'Actif'
                    ],
                    [
                        'title' => 'Gestion des Emballages & Éco-Score',
                        'icon' => 'fa-box',
                        'description' => 'Gérez les emballages et calculez l\'éco-score. Optimisez l\'impact environnemental de vos produits.',
                        'route' => '#',
                        'badge' => 'À venir'
                    ],
                ]
            ],
            'distributor' => [
                'title' => 'Tableau de Bord Distributeur',
                'icon' => 'fa-truck',
                'color' => '#0d6efd',
                'modules' => [
                    [
                        'title' => 'Suivi des Transports & Kilométrage',
                        'icon' => 'fa-route',
                        'description' => 'Suivez les trajets de livraison. Enregistrez les kilométrages et calcul de l\'empreinte carbone.',
                        'route' => route('pro.distributor.shipments.index'),
                        'badge' => 'Disponible'
                    ],
                    [
                        'title' => 'Points de Vente',
                        'icon' => 'fa-store',
                        'description' => 'Gérez vos points de vente. Assignez des stocks et visualisez les disponibilités.',
                        'route' => route('pro.distributor.points-of-sale.index'),
                        'badge' => 'Disponible'
                    ],
                    [
                        'title' => 'Statuts des Stocks',
                        'icon' => 'fa-boxes-stacked',
                        'description' => 'Consultez les stocks en temps réel. Recevez des alertes sur les ruptures de stock prévues.',
                        'route' => '#',
                        'badge' => 'À venir'
                    ],
                ]
            ],
            default => [
                'title' => 'Tableau de Bord',
                'icon' => 'fa-chart-line',
                'color' => '#6c757d',
                'modules' => []
            ]
        };

        // pro.dashboard est la vue du transformateur (Module 2) ; le distributeur (Module 3) a la sienne
        $view = $proType === 'distributor' ? 'pro.distributor.home' : 'pro.dashboard';

        return view($view, [
            'user' => $user,
            'companyName' => $companyName,
            'taxId' => $taxId,
            'proType' => $proType,
            'dashboardData' => $dashboardData,
            'dashboardKpis' => $dashboardKpis,
            'wastePreventionRecommendation' => $wastePreventionRecommendation,
        ]);
    }

    /**
     * Indicateurs réels du producteur : production, stock, qualité, alertes, conseil de saison.
     */
    private function producerDashboard(User $user): View
    {
        $farms = $user->farms()->with('harvests')->orderBy('name')->get();
        $harvests = Harvest::ownedBy($user)->with(['farm', 'category'])->orderByDesc('harvest_date')->get();
        $kg = fn ($items) => $items->sum(fn (Harvest $h) => $h->quantityInKg());
        $plotted = $harvests->filter(fn ($h) => $h->plot_area > 0);

        $kpis = [
            'farms' => $farms->count(),
            'area' => $farms->sum('total_area'),
            'harvests' => $harvests->count(),
            'kg' => $kg($harvests),
            'in_stock_kg' => $kg($harvests->where('status', 'in_stock')),
            'grade_a' => $harvests->count() ? round($harvests->where('quality_grade', 'A')->count() / $harvests->count() * 100) : 0,
            'yield' => $plotted->sum('plot_area') > 0 ? round($kg($plotted) / $plotted->sum('plot_area')) : null,
            'no_photo' => $harvests->whereNull('image_path')->count(),
        ];

        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $chart = [
            'months' => $months->map(fn ($m) => $m->translatedFormat('M y'))->values(),
            'kg' => $months->map(fn ($m) => round($kg($harvests->filter(fn ($h) => $h->harvest_date->isSameMonth($m)))))->values(),
            'status' => collect(Harvest::STATUSES)->map(fn ($label, $key) => $harvests->where('status', $key)->count()),
        ];

        // Prévision 3 mois (régression linéaire sur la série mensuelle)
        $intel = app(ProductIntelligence::class);
        $forecast = $intel->forecast($chart['kg']->all());
        $chart['forecastMonths'] = collect(range(1, 3))->map(fn ($i) => now()->startOfMonth()->addMonths($i)->translatedFormat('M y'))->values();
        $chart['forecast'] = $forecast['values'];

        $alerts = [];
        // Fraîcheur : lots expirés ou à écouler rapidement
        $fresh = $harvests->where('status', 'in_stock')->map(fn ($h) => [$h, $intel->freshness($h)]);
        $expired = $fresh->filter(fn ($x) => $x[1]['level'] === 'expired');
        $soon = $fresh->filter(fn ($x) => $x[1]['level'] === 'low');
        if ($expired->isNotEmpty()) {
            $alerts[] = ['danger', 'fa-triangle-exclamation', "{$expired->count()} lot(s) en stock ont dépassé leur date limite estimée : " . $expired->map(fn ($x) => $x[0]->product_name)->unique()->implode(', ') . '.', route('pro.harvests.index', ['status' => 'in_stock'])];
        }
        foreach ($soon->take(3) as [$h, $f]) {
            $alerts[] = ['warning', 'fa-hourglass-half', "{$h->product_name} ({$h->farm->name}) : à écouler avant le {$f['best_before']->format('d/m')} (fraîcheur {$f['percent']} %).", route('pro.harvests.show', $h)];
        }
        // Conformité : lots à compléter
        $weak = $harvests->filter(fn ($h) => $intel->risk($h)['level'] !== 'low')->count();
        if ($weak) {
            $alerts[] = ['info', 'fa-shield-halved', "{$weak} déclaration(s) à compléter pour améliorer votre score de conformité.", route('pro.harvests.index')];
        }
        if ($kpis['no_photo']) {
            $alerts[] = ['info', 'fa-camera', "{$kpis['no_photo']} produit(s) sans photo : ajoutez-en pour le catalogue.", route('pro.harvests.index')];
        }
        foreach ($farms->where('status', 'active')->filter(fn ($f) => $f->harvests->isEmpty()) as $farm) {
            $alerts[] = ['warning', 'fa-seedling', "« {$farm->name} » n'a encore aucune récolte déclarée.", route('pro.harvests.create', ['farm' => $farm->id])];
        }

        return view('pro.producer-dashboard', [
            'user' => $user,
            'farms' => $farms,
            'recent' => $harvests->take(5),
            'kpis' => $kpis,
            'chart' => $chart,
            'alerts' => $alerts,
            'seasonTip' => app(AiAssistant::class)->seasonalAdvice((int) now()->month),
            'forecast' => $forecast,
            'stockValue' => $harvests->where('status', 'in_stock')->sum(fn ($h) => $intel->price($h)['value']),
        ]);
    }
}
