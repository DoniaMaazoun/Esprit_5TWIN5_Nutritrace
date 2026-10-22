<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Production Lots: Finished products created from recipes (Module 3 - Processor)
 */
class ProductionLot extends Model
{
    protected $fillable = [
        'recipe_id',
        'processor_id',
        'quantity_produced',
        'unit',
        'production_date',
        'expiry_date',
        'status',
        'qr_code_data',
        'production_notes',
    ];

    protected $casts = [
        'quantity_produced' => 'float',
        'production_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Relation: Production lot belongs to a Recipe
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Relation: Production lot belongs to a Processor (user)
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processor_id');
    }

    /**
     * Get the recipe items (ingredients) used in this lot
     */
    public function getRecipeItems()
    {
        return $this->recipe->items;
    }

    /**
     * Get the name of the finished product
     */
    public function getProductName(): string
    {
        return $this->recipe->name ?? 'Produit';
    }

    /**
     * Get status label in French
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'produced' => 'Produit ✅',
            'packaged' => 'Emballé 📦',
            'shipped' => 'Expédié 🚚',
            'archived' => 'Archivé 📋',
            default => 'Inconnu',
        };
    }

    /**
     * Get status badge color for display
     */
    public function getStatusBadgeColor(): string
    {
        return match($this->status) {
            'produced' => '#2D6A4F',    // processor-primary
            'packaged' => '#40B5AD',    // teal
            'shipped' => '#1B4332',     // processor-dark
            'archived' => '#6c757d',    // gray
            default => '#999',
        };
    }

    /**
     * Format display: "500 kg", "1.5 L", etc.
     */
    public function getQuantityDisplay(): string
    {
        return "{$this->quantity_produced} {$this->unit}";
    }

    /**
     * Check if lot is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }

    /**
     * Check if lot is close to expiry (within 7 days)
     */
    public function isCloseToExpiry(): bool
    {
        return now()->diffInDays($this->expiry_date) <= 7 && !$this->isExpired();
    }
}
