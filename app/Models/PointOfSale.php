<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PointOfSale extends Model
{
    protected $table = 'points_of_sale';

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'address',
        'city',
        'latitude',
        'longitude',
    ];

    public const TYPES = [
        'store' => 'Magasin',
        'warehouse' => 'Entrepôt',
        'market' => 'Marché',
    ];

    /**
     * Distributeur propriétaire
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Expéditions livrées à ce point de vente
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
