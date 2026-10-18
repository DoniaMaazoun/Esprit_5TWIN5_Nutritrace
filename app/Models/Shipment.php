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
        'harvest_id',
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

    /**
     * Lot transporté, déclaré par un producteur (module 1)
     */
    public function harvest(): BelongsTo
    {
        return $this->belongsTo(Harvest::class);
    }

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

    /** Couleur de chaque mode de transport sur la carte du trajet */
    public const MODE_COLORS = [
        'truck_heavy' => '#e76f51',
        'truck_light' => '#f4a261',
        'van' => '#c9a227',
        'rail' => '#2d5a27',
        'river' => '#2a9d8f',
        'sea' => '#264653',
        'air' => '#d62828',
    ];

    /**
     * Données de la carte Leaflet : départ (ferme du lot), arrivée de chaque étape,
     * destination (point de vente). Une étape sans coordonnées n'est pas tracée.
     *
     * @return array{markers: array, segments: array, missing: array<int, string>}
     */
    public function mapData(): array
    {
        $this->loadMissing(['harvest.farm', 'pointOfSale', 'legs.transportMode']);

        $markers = [];
        $segments = [];
        $missing = [];

        $farm = $this->harvest?->farm;
        $previous = $farm?->hasLocation() ? [(float) $farm->latitude, (float) $farm->longitude] : null;
        if ($previous) {
            $markers[] = ['position' => $previous, 'type' => 'start', 'label' => "Départ : {$this->origin_address} ({$farm->name})"];
        } elseif ($this->legs->isNotEmpty()) {
            $missing[] = 'Départ : lot sans ferme géolocalisée';
        }

        $pos = $this->pointOfSale;
        $posPoint = $pos && $pos->latitude !== null && $pos->longitude !== null
            ? [(float) $pos->latitude, (float) $pos->longitude]
            : null;

        foreach ($this->legs as $index => $leg) {
            $isLast = $index === $this->legs->count() - 1;
            $end = $leg->to_latitude !== null && $leg->to_longitude !== null
                ? [(float) $leg->to_latitude, (float) $leg->to_longitude]
                : ($isLast ? $posPoint : null);

            if (!$end) {
                $missing[] = "Étape {$leg->position} : {$leg->to_location}";
                $previous = null;

                continue;
            }

            if ($previous) {
                $segments[] = [
                    'from' => $previous,
                    'to' => $end,
                    'color' => self::MODE_COLORS[$leg->transportMode->code] ?? '#6c757d',
                    'label' => "Étape {$leg->position} · {$leg->transportMode->name} · "
                        . number_format($leg->distance_km, 1, ',', ' ') . ' km · ' . number_format($leg->co2_kg, 2, ',', ' ') . ' kg CO2e',
                ];
            }
            $markers[] = [
                'position' => $end,
                'type' => $isLast ? 'end' : 'step',
                'label' => ($isLast ? 'Arrivée' : "Étape {$leg->position}") . " : {$leg->to_location}",
            ];
            $previous = $end;
        }

        // Destination connue mais pas encore reliée par une étape
        if ($posPoint && !collect($markers)->contains(fn ($m) => $m['position'] === $posPoint)) {
            $markers[] = ['position' => $posPoint, 'type' => 'end', 'label' => "Destination : {$pos->name}, {$pos->city}"];
        }

        return ['markers' => $markers, 'segments' => $segments, 'missing' => $missing];
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
