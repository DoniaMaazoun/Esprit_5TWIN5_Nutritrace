<?php

namespace App\Models;

use Database\Factories\DistributorStockFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock de produits finis disponibles chez les distributeurs
 * Relie ProductionLot (produit fini) à PointOfSale (point de vente)
 */
class DistributorStock extends Model
{
    use HasFactory;

    public const STATUSES = [
        'available' => 'Disponible',
        'reserved' => 'Réservé',
        'out_of_stock' => 'Épuisé',
    ];

    protected $fillable = [
        'point_of_sale_id',
        'production_lot_id',
        'quantity_available',
        'quantity_reserved',
        'unit_price',
        'status',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_available' => 'decimal:2',
            'quantity_reserved' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'received_at' => 'date',
        ];
    }

    /**
     * Point de vente du distributeur
     */
    public function pointOfSale(): BelongsTo
    {
        return $this->belongsTo(PointOfSale::class);
    }

    /**
     * Produit fini (lot de production)
     */
    public function productionLot(): BelongsTo
    {
        return $this->belongsTo(ProductionLot::class);
    }

    /**
     * Distributeur propriétaire du point de vente
     */
    public function distributor(): BelongsTo
    {
        return $this->pointOfSale->user();
    }

    /**
     * Scope: Stocks disponibles à la vente
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available')
            ->where('quantity_available', '>', 0);
    }

    /**
     * Scope: Stocks d'un distributeur spécifique
     */
    public function scopeForDistributor(Builder $query, User $user): Builder
    {
        return $query->whereHas('pointOfSale', fn (Builder $q) => $q->where('user_id', $user->id));
    }

    /**
     * Scope: Stocks avec traçabilité complète (relations eager loaded)
     */
    public function scopeWithTraceability(Builder $query): Builder
    {
        return $query->with([
            'productionLot.recipe',
            'productionLot.processor',
            'pointOfSale.user',
        ]);
    }

    /**
     * Vérifie si le stock est disponible
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->quantity_available > 0;
    }

    /**
     * Vérifie si le stock est épuisé
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity_available <= 0 || $this->status === 'out_of_stock';
    }

    /**
     * Label du statut
     */
    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Prix formaté pour affichage
     */
    public function getFormattedPrice(): string
    {
        return $this->unit_price ? number_format($this->unit_price, 2, ',', ' ') . ' DT' : 'N/A';
    }

    /**
     * Nom du produit fini
     */
    public function getProductName(): string
    {
        return $this->productionLot->getProductName();
    }

    /**
     * Nom du point de vente
     */
    public function getPointOfSaleName(): string
    {
        return $this->pointOfSale->name;
    }

    /**
     * Nom du distributeur
     */
    public function getDistributorName(): string
    {
        return $this->pointOfSale->user->company_name ?? $this->pointOfSale->user->name;
    }

    /**
     * Ville du point de vente
     */
    public function getCity(): string
    {
        return $this->pointOfSale->city;
    }
}
