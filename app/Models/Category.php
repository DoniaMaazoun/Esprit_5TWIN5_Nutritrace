<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Catégorie de produits (Légumes, Fruits…), gérée par l'administrateur.
 */
class Category extends Model
{
    /**
     * Icônes Font Awesome proposées dans le formulaire admin.
     */
    public const ICONS = [
        'fa-carrot' => 'Carotte', 'fa-apple-whole' => 'Pomme', 'fa-lemon' => 'Agrume', 'fa-wheat-awn' => 'Épi',
        'fa-seedling' => 'Pousse', 'fa-tree' => 'Arbre', 'fa-leaf' => 'Feuille', 'fa-pepper-hot' => 'Piment',
        'fa-bowl-rice' => 'Grains', 'fa-egg' => 'Œuf', 'fa-cow' => 'Élevage',
        'fa-fish' => 'Poisson', 'fa-jar' => 'Bocal', 'fa-bottle-droplet' => 'Huile', 'fa-cheese' => 'Fromage',
        'fa-mug-hot' => 'Boisson', 'fa-spa' => 'Fleur',
    ];

    protected $fillable = ['name', 'description', 'icon', 'color', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            $category->slug = Str::slug($category->name);
        });
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
    }

    /**
     * Illustration de la catégorie (public/frontend/images/categories/{slug}.svg), sinon visuel générique.
     */
    public function illustrationUrl(): string
    {
        $file = "frontend/images/categories/{$this->slug}.svg";

        return asset(is_file(public_path($file)) ? $file : 'frontend/images/categories/default.svg');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Catégories proposées dans un formulaire de produit : les actives,
     * plus la catégorie actuelle du produit même si elle a été désactivée.
     */
    public static function forSelect(?int $currentId = null)
    {
        return self::query()
            ->where(fn (Builder $q) => $q->where('is_active', true)->when($currentId, fn ($w) => $w->orWhere('id', $currentId)))
            ->orderBy('name')
            ->get();
    }
}
