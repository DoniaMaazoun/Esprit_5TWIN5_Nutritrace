<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ingrédients des recettes (composants requiredis)
 */
class RecipeItem extends Model
{
    protected $fillable = [
        'recipe_id',
        'raw_material_name',
        'quantity_required',
        'unit',
        'notes',
    ];

    protected $casts = [
        'quantity_required' => 'float',
    ];

    /**
     * Relation: Ingrédient appartient à une recette
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Format d'affichage: "500 kg", "1.5 L"
     */
    public function getQuantityDisplay(): string
    {
        return "{$this->quantity_required} {$this->unit}";
    }
}
