<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportMode extends Model
{
    protected $fillable = [
        'name',
        'code',
        'emission_factor',
        'source',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'emission_factor' => 'decimal:5',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Étapes de transport utilisant ce mode
     */
    public function legs(): HasMany
    {
        return $this->hasMany(ShipmentLeg::class);
    }
}
