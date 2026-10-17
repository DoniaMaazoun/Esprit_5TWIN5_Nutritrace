<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Farm;
use App\Models\Harvest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/**
 * Page d'accueil : chiffres réels de la plateforme et derniers produits tracés.
 */
class HomeController extends Controller
{
    public function index(): View
    {
        $approved = fn (Builder $u) => $u->where('status', 'approved');
        $visible = Harvest::whereHas('farm.user', $approved);

        $stats = [
            'products' => (clone $visible)->count(),
            'producers' => User::where('role', 'pro')->where('pro_type', 'producer')->where('status', 'approved')->count(),
            'farms' => Farm::whereHas('user', $approved)->count(),
            'governorates' => Farm::whereHas('user', $approved)->distinct('governorate')->count('governorate'),
        ];

        $latest = (clone $visible)->with(['farm.user', 'category'])->latest()->take(4)->get();
        $categories = Category::active()->withCount(['harvests' => fn (Builder $q) => $q->whereHas('farm.user', $approved)])
            ->orderByDesc('harvests_count')->get()->where('harvests_count', '>', 0)->take(6)->values();

        $featuredFarms = Farm::whereHas('user', $approved)
            ->with(['user', 'coverHarvest', 'harvests' => fn ($q) => $q->with('category')])
            ->withCount('harvests')
            ->orderByDesc('harvests_count')
            ->take(3)
            ->get();

        $mapPoints = Farm::whereHas('user', $approved)->whereNotNull('latitude')->withCount('harvests')->get()
            ->map(fn (Farm $f) => [
                'name' => $f->name, 'lat' => $f->latitude, 'lng' => $f->longitude, 'url' => $f->publicUrl(),
                'info' => "{$f->city}, {$f->governorate} · {$f->harvests_count} lot(s)",
            ])->values();

        // Exemple de lot réel pour illustrer la recherche
        $sampleCode = (clone $visible)->latest()->value('batch_code');

        return view('frontend.pages.home', compact('stats', 'latest', 'categories', 'featuredFarms', 'mapPoints', 'sampleCode'));
    }
}
