<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $fillable = [
        'reference',
        'user_id',
        'batch_id',
        'product_name',
        'weight_kg',
        'origin_address',
        'point_of_sale_id',
        'status',
        'departed_at',
        'delivered_at',
        'total_distance_km',
        'total_co2_kg',
    ];

    public const STATUSES = [
        'planned' => 'Planifiée',
        'in_transit' => 'En transit',
        'delivered' => 'Livrée',
        'cancelled' => 'Annulée',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'total_distance_km' => 'decimal:2',
            'total_co2_kg' => 'decimal:3',
            'departed_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Génère la prochaine référence unique (ex. SHP-2026-0001)
     */
    public static function nextReference(): string
    {
        $year = now()->year;
        $last = static::where('reference', 'like', "SHP-{$year}-%")->max('reference');
        $next = $last ? (int) substr($last, -4) + 1 : 1;

        return sprintf('SHP-%d-%04d', $year, $next);
    }

    /**
     * Distributeur responsable de l'expédition
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Point de vente de destination
     */
    public function pointOfSale(): BelongsTo
    {
        return $this->belongsTo(PointOfSale::class);
    }

    // TODO: relation batch() vers App\Models\Batch quand le module 1 sera fusionné

    /**
     * Étapes de transport, dans l'ordre
     */
    public function legs(): HasMany
    {
        return $this->hasMany(ShipmentLeg::class)->orderBy('position');
    }

    /**
     * Journal de traçabilité, du plus récent au plus ancien
     */
    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)->orderByDesc('occurred_at');
    }

    /**
     * Recalcule la distance et le CO2 totaux à partir des étapes
     */
    public function recalculateTotals(): void
    {
        $this->update([
            'total_distance_km' => $this->legs()->sum('distance_km'),
            'total_co2_kg' => $this->legs()->sum('co2_kg'),
        ]);
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
