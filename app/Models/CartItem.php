<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Article du panier consommateur
 * Relie un User (consommateur) à un DistributorStock (produit fini disponible)
 */
class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'distributor_stock_id',
        'quantity',
        'unit_price_at_addition',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price_at_addition' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function distributorStock(): BelongsTo
    {
        return $this->belongsTo(DistributorStock::class);
    }

    /**
     * Scope pour filtrer par utilisateur (isolation du panier)
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Calculer le sous-total basé sur le prix actuel du stock (pas le prix historique)
     * Recalculé depuis la base pour éviter la manipulation des prix côté client
     */
    public function getSubtotal(): float
    {
        return $this->quantity * ($this->distributorStock->unit_price ?? 0);
    }

    /**
     * Vérifier si la quantité demandée est disponible en stock
     */
    public function isQuantityAvailable(): bool
    {
        return $this->quantity <= $this->distributorStock->quantity_available;
    }

    /**
     * Obtenir la quantité maximum disponible
     */
    public function getMaxQuantity(): float
    {
        return $this->distributorStock->quantity_available;
    }

    /**
     * Formater le sous-total pour l'affichage
     */
    public function getFormattedSubtotal(): string
    {
        return number_format($this->getSubtotal(), 2, ',', ' ') . ' DT';
    }

    /**
     * Obtenir le nom du produit
     */
    public function getProductName(): string
    {
        return $this->distributorStock->getProductName();
    }

    /**
     * Obtenir le prix unitaire actuel (recalculé depuis la base)
     */
    public function getCurrentUnitPrice(): float
    {
        return $this->distributorStock->unit_price ?? 0;
    }

    /**
     * Formater le prix unitaire actuel
     */
    public function getFormattedUnitPrice(): string
    {
        return number_format($this->getCurrentUnitPrice(), 2, ',', ' ') . ' DT';
    }
}
