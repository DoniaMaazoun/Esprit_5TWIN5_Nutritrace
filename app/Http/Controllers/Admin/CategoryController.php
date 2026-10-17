<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Models\Harvest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Back-office : gestion des catégories de produits (3e entité du Module 1).
 */
class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q'));
        $status = $request->input('status');

        $categories = Category::query()
            ->withCount('harvests')
            ->when($q, fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%")))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->get();

        // Production (kg) par catégorie, pour la barre de répartition
        $kgByCategory = Harvest::all()->groupBy('category_id')->map(fn ($g) => $g->sum(fn (Harvest $h) => $h->quantityInKg()));

        $stats = [
            'total' => Category::count(),
            'active' => Category::active()->count(),
            'unused' => Category::doesntHave('harvests')->count(),
            'products' => Harvest::count(),
        ];

        return view('backend.pages.categories.index', [
            'categories' => $categories,
            'kgByCategory' => $kgByCategory,
            'maxKg' => max($kgByCategory->max() ?? 0, 1),
            'stats' => $stats,
            'category' => new Category(['color' => '#2D6A4F', 'icon' => 'fa-seedling', 'is_active' => true]),
            'filters' => compact('q', 'status'),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return redirect()->route('backend.categories.index')
            ->with('success', "La catégorie « {$category->name} » a été créée.");
    }

    public function edit(Category $category): View
    {
        $category->loadCount('harvests');

        return view('backend.pages.categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('backend.categories.index')
            ->with('success', "La catégorie « {$category->name} » a été mise à jour.");
    }

    public function toggle(Category $category): RedirectResponse
    {
        if ($category->is_active && Category::active()->count() <= 1) {
            return back()->with('error', 'Impossible de désactiver la dernière catégorie active.');
        }

        $category->update(['is_active' => !$category->is_active]);

        return back()->with('success', "« {$category->name} » est maintenant " . ($category->is_active ? 'active' : 'désactivée') . '.');
    }

    /**
     * Règle métier : une catégorie utilisée par des produits ne peut pas être supprimée
     * (la clé étrangère l'interdit aussi) ; il faut la désactiver.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $count = $category->harvests()->count();
        if ($count > 0) {
            return back()->with('error', "« {$category->name} » est utilisée par {$count} produit(s) : désactivez-la plutôt que de la supprimer.");
        }

        $name = $category->name;
        $category->delete();

        return back()->with('success', "La catégorie « {$name} » a été supprimée.");
    }
}
