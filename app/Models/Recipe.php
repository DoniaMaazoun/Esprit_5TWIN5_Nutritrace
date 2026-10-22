<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Recettes: Formules de transformation créées par les Transformateurs
 */
class Recipe extends Model
{
    protected $fillable = [
        'processor_id',
        'name',
        'description',
        'yield_quantity',
        'yield_unit',
        'preparation_time_minutes',
        'instructions',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'yield_quantity' => 'float',
        'preparation_time_minutes' => 'integer',
    ];

    /**
     * Relation: Recette appartient à un Transformateur
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processor_id');
    }

    /**
     * Relation: Recette a plusieurs ingrédients
     */
    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    /**
     * Relation: Recette a plusieurs lots de production
     */
    public function productionLots(): HasMany
    {
        return $this->hasMany(ProductionLot::class);
    }

    /**
     * Scope: Recettes d'un transformateur spécifique
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('processor_id', $user->id);
    }

    /**
     * Scope: Recettes actives
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Obtenir le nombre total d'ingrédients
     */
    public function itemsCount(): int
    {
        return $this->items()->count();
    }

    /**
     * Obtenir la quantité totale en kg (conversion si nécessaire)
     */
    public function getTotalInputQuantityInKg(): float
    {
        $total = 0;
        $units = [
            'kg' => 1,
            'g' => 0.001,
            'quintal' => 100,
            'tonne' => 1000,
            'L' => 1, // Pour les liquides, 1L ≈ 1kg
            'mL' => 0.001,
        ];

        foreach ($this->items as $item) {
            $factor = $units[$item->unit] ?? 1;
            $total += $item->quantity_required * $factor;
        }

        return $total;
    }

    /**
     * Rendement en kg (conversion si nécessaire)
     */
    public function getYieldInKg(): float
    {
        $units = [
            'kg' => 1,
            'g' => 0.001,
            'quintal' => 100,
            'tonne' => 1000,
            'L' => 1,
            'mL' => 0.001,
        ];

        $factor = $units[$this->yield_unit] ?? 1;
        return $this->yield_quantity * $factor;
    }

    /**
     * Ratio de rendement (output / input)
     */
    public function getYieldRatio(): float
    {
        $input = $this->getTotalInputQuantityInKg();
        if ($input == 0) return 0;
        return round($this->getYieldInKg() / $input, 2);
    }

    /**
     * Label du statut
     */
    public function getStatusLabel(): string
    {
        return $this->is_active ? '✅ Active' : '❌ Inactive';
    }

    /**
     * Format: "500 kg", "1.5 L", etc.
     */
    public function getYieldDisplay(): string
    {
        return "{$this->yield_quantity} {$this->yield_unit}";
    }
}
