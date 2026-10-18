<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentEvent extends Model
{
    protected $fillable = [
        'shipment_id',
        'type',
        'location',
        'note',
        'occurred_at',
    ];

    public const TYPES = [
        'loaded' => 'Chargement',
        'departed' => 'Départ',
        'checkpoint' => 'Point de passage',
        'arrived' => 'Arrivée',
        'incident' => 'Incident',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
