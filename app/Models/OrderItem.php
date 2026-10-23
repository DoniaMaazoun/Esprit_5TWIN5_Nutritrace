<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne de commande
 * Relie une Order à un DistributorStock avec quantité et prix figés
 */
class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'distributor_stock_id',
        'quantity',
        'unit_price',
        'subtotal',
        'product_name',
        'point_of_sale_name',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function distributorStock(): BelongsTo
    {
        return $this->belongsTo(DistributorStock::class);
    }

    /**
     * Formater le sous-total pour l'affichage
     */
    public function getFormattedSubtotal(): string
    {
        return number_format($this->subtotal, 2, ',', ' ') . ' DT';
    }

    /**
     * Formater le prix unitaire pour l'affichage
     */
    public function getFormattedUnitPrice(): string
    {
        return number_format($this->unit_price, 2, ',', ' ') . ' DT';
    }
}
