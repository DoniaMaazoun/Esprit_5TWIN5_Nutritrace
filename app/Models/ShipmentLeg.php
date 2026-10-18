<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentLeg extends Model
{
    protected $fillable = [
        'shipment_id',
        'transport_mode_id',
        'position',
        'from_location',
        'to_location',
        'distance_km',
        'weight_kg',
        'emission_factor',
        'co2_kg',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'emission_factor' => 'decimal:5',
            'co2_kg' => 'decimal:3',
        ];
    }

    /**
     * Calcule l'empreinte carbone de l'étape à partir du mode de transport choisi
     * CO2 (kg) = distance (km) x poids (tonnes) x facteur (kg CO2e / t.km)
     */
    public function calculateCo2(): self
    {
        // On copie le facteur actuel pour que l'historique ne change pas
        $this->emission_factor = TransportMode::findOrFail($this->transport_mode_id)->emission_factor;
        $this->co2_kg = $this->distance_km * ($this->weight_kg / 1000) * $this->emission_factor;

        return $this;
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function transportMode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class);
    }
}
