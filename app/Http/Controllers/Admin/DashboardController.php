<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Farm;
use App\Models\Harvest;
use App\Models\User;
use App\Services\ProductIntelligence;
use Illuminate\View\View;

/**
 * Tableau de bord du back-office : indicateurs réels de la plateforme.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $harvests = Harvest::with(['farm.user', 'category'])->get();
        $farms = Farm::with('user')->withCount('harvests')->get();
        $kg = fn ($items) => $items->sum(fn (Harvest $h) => $h->quantityInKg());

        // Évolution : ce mois-ci vs mois précédent
        $thisMonth = $harvests->filter(fn ($h) => $h->created_at->isCurrentMonth())->count();
        $lastMonth = $harvests->filter(fn ($h) => $h->created_at->isSameMonth(now()->subMonthNoOverflow()))->count();

        $kpis = [
            'products' => $harvests->count(),
            'products_month' => $thisMonth,
            'products_trend' => $lastMonth ? round(($thisMonth - $lastMonth) / $lastMonth * 100) : null,
            'farms' => $farms->count(),
            'area' => $farms->sum('total_area'),
            'producers' => User::where('role', 'pro')->where('pro_type', 'producer')->where('status', 'approved')->count(),
            'pending' => User::where('role', 'pro')->where('status', 'pending')->count(),
            'tonnes' => $kg($harvests) / 1000,
            'in_stock' => $harvests->where('status', 'in_stock')->count(),
            'grade_a' => $harvests->count() ? round($harvests->where('quality_grade', 'A')->count() / $harvests->count() * 100) : 0,
            'consumers' => User::where('role', 'consumer')->count(),
        ];

        // Production mensuelle (t) sur 12 mois, par date de récolte
        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $charts = [
            'months' => $months->map(fn ($m) => $m->translatedFormat('M y'))->values(),
            'monthly' => $months->map(fn ($m) => round($kg($harvests->filter(fn ($h) => $h->harvest_date->isSameMonth($m))) / 1000, 2))->values(),
        ];

        $byCategory = Category::withCount('harvests')->orderByDesc('harvests_count')->get()->where('harvests_count', '>', 0)->values();
        $charts['categories'] = $byCategory->pluck('name');
        $charts['categoryCounts'] = $byCategory->pluck('harvests_count');
        $charts['categoryColors'] = $byCategory->pluck('color');

        $byGov = $farms->groupBy('governorate')->map->count()->sortDesc()->take(8);
        $charts['governorates'] = $byGov->keys();
        $charts['governorateCounts'] = $byGov->values();

        $byType = $farms->groupBy('farming_type')->map->count();
        $charts['farmingTypes'] = $byType->keys()->map(fn ($k) => Farm::FARMING_TYPES[$k] ?? $k);
        $charts['farmingTypeCounts'] = $byType->values();

        // Classement des producteurs par production
        $topProducers = $harvests->groupBy(fn ($h) => $h->farm->user_id)
            ->map(fn ($g) => ['user' => $g->first()->farm->user, 'kg' => $kg($g), 'count' => $g->count()])
            ->sortByDesc('kg')->take(5)->values();

        $latest = Harvest::with(['farm.user', 'category'])->latest()->take(6)->get();
        $pendingUsers = User::where('role', 'pro')->where('status', 'pending')->latest()->take(4)->get();

        return view('backend.pages.dashboard', compact('kpis', 'charts', 'topProducers', 'latest', 'pendingUsers') + [
            'alerts' => $this->alerts($harvests, $farms, $kpis),
        ]);
    }

    /**
     * Alertes calculées à partir des données (et non plus statiques).
     */
    private function alerts($harvests, $farms, array $kpis): array
    {
        $alerts = [];

        $highRisk = $harvests->filter(fn ($h) => app(ProductIntelligence::class)->risk($h)['level'] === 'high')->count();
        if ($highRisk) {
            $alerts[] = ['danger', 'shield-alert', "{$highRisk} lot(s) à risque élevé détecté(s) par l'analyse de conformité (rendement anormal, hors saison…).", route('backend.products.index', ['risk' => 'high', 'sort' => 'risk']), 'Contrôler'];
        }
        if ($kpis['pending']) {
            $alerts[] = ['warning', 'account-clock', "{$kpis['pending']} compte(s) professionnel(s) en attente d'approbation.", route('backend.users.approvals'), 'Traiter'];
        }
        $oldStock = $harvests->filter(fn ($h) => $h->status === 'in_stock' && $h->harvest_date->lt(now()->subDays(60)));
        if ($oldStock->isNotEmpty()) {
            $alerts[] = ['danger', 'clock-alert', "{$oldStock->count()} lot(s) en stock depuis plus de 60 jours : risque de perte de qualité.", route('backend.products.index', ['status' => 'in_stock']), 'Voir'];
        }
        $noPhoto = $harvests->whereNull('image_path')->count();
        if ($noPhoto) {
            $alerts[] = ['info', 'image-off', "{$noPhoto} produit(s) sans photo : moins attractifs dans le catalogue.", route('backend.products.index', ['photo' => 'without']), 'Voir'];
        }
        $gradeC = $harvests->where('quality_grade', 'C')->count();
        if ($gradeC) {
            $alerts[] = ['warning', 'alert-circle', "{$gradeC} lot(s) classé(s) en qualité C.", route('backend.products.index', ['quality' => 'C']), 'Contrôler'];
        }
        $idle = $farms->where('status', 'active')->where('harvests_count', 0)->count();
        if ($idle) {
            $alerts[] = ['info', 'barn', "{$idle} exploitation(s) active(s) sans aucune récolte déclarée.", route('backend.farms.index', ['status' => 'active']), 'Voir'];
        }

        return $alerts;
    }
}
