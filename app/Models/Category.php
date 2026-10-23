<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Catégorie de produits (Légumes, Fruits…), gérée par l'administrateur.
 */
class Category extends Model
{
    use HasFactory;

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

    protected $fillable = ['name', 'description', 'icon', 'color', 'is_active', 'slug'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            // Slug fourni explicitement : conservé ; sinon calculé à la création et à chaque renommage
            if (empty($category->slug) || ($category->isDirty('name') && ! $category->isDirty('slug'))) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
    }

    /**
     * Mots dont la photo porte un autre nom (après passage au singulier).
     */
    private const PHOTO_ALIASES = ['cereale' => 'ble'];

    private static ?array $photos = null;

    /**
     * Visuel de la catégorie : sa photo (public/frontend/images/products), sinon son illustration
     * (public/frontend/images/categories/{slug}.svg), sinon le visuel générique.
     */
    public function illustrationUrl(): string
    {
        if ($photo = self::photoUrlFor($this->slug)) {
            return $photo;
        }

        $file = "frontend/images/categories/{$this->slug}.svg";

        return asset(is_file(public_path($file)) ? $file : 'frontend/images/categories/default.svg');
    }

    /**
     * Photo réelle correspondant à un nom de produit ou de catégorie
     * (« Tomates Bio Provence » → tomate.jpg, « Pommes de terre Charlotte » → pomme-de-terre.jpg).
     */
    public static function photoUrlFor(?string $name): ?string
    {
        $text = '-' . self::singular(Str::slug((string) $name)) . '-';

        foreach (self::photos() as $key => $file) {
            if (str_contains($text, "-{$key}-")) {
                return asset("frontend/images/products/{$file}");
            }
        }

        return null;
    }

    /**
     * Photos disponibles, clé au singulier → fichier, les noms composés d'abord.
     */
    private static function photos(): array
    {
        if (self::$photos === null) {
            self::$photos = [];
            foreach (glob(public_path('frontend/images/products/*.jpg')) ?: [] as $path) {
                self::$photos[self::singular(pathinfo($path, PATHINFO_FILENAME))] = basename($path);
            }
            uksort(self::$photos, fn ($a, $b) => substr_count($b, '-') <=> substr_count($a, '-') ?: strcmp($a, $b));
        }

        return self::$photos;
    }

    private static function singular(string $slug): string
    {
        return collect(explode('-', $slug))
            ->map(fn ($word) => strlen($word) > 3 ? preg_replace('/s$/', '', $word) : $word)
            ->map(fn ($word) => self::PHOTO_ALIASES[$word] ?? $word)
            ->implode('-');
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
