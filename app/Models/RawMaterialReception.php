<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialReception extends Model
{
    protected $fillable = [
        'harvest_id',
        'processor_id',
        'quantity_received',
        'unit',
        'reception_date',
        'status',
        'quality_notes',
        'quality_status',
        'storage_location',
        'temperature',
        'rejection_reason',
        'rejection_date',
        'internal_notes',
        'verified_at',
    ];

    protected $casts = [
        'reception_date' => 'datetime',
        'rejection_date' => 'datetime',
        'verified_at' => 'datetime',
        'quantity_received' => 'decimal:2',
        'temperature' => 'decimal:2',
    ];

    /**
     * Relationship: Reception belongs to a Harvest (from Producteur)
     */
    public function harvest(): BelongsTo
    {
        return $this->belongsTo(Harvest::class);
    }

    /**
     * Relationship: Reception belongs to a Processor (Transformateur)
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processor_id');
    }

    /**
     * Get the farm from the harvest
     */
    public function farm()
    {
        return $this->harvest->farm;
    }

    /**
     * Get the producteur from the farm
     */
    public function producteur()
    {
        return $this->farm->user;
    }

    /**
     * Check if reception is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if reception is accepted
     */
    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    /**
     * Check if reception is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Mark reception as received
     */
    public function markAsReceived(): void
    {
        $this->status = 'received';
        $this->verified_at = now();
        $this->save();
    }

    /**
     * Mark reception as rejected
     */
    public function markAsRejected(string $reason): void
    {
        $this->status = 'rejected';
        $this->rejection_reason = $reason;
        $this->rejection_date = now();
        $this->save();
    }

    /**
     * Get status label in French
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'received' => 'Reçu ✅',
            'rejected' => 'Rejeté ❌',
            default => 'Inconnu',
        };
    }

    /**
     * Get quality status label in French
     */
    public function getQualityLabel(): string
    {
        return match($this->quality_status) {
            'good' => 'Bon ✅',
            'acceptable' => 'Acceptable ⚠️',
            'damaged' => 'Endommagé ❌',
            default => 'Non évalué',
        };
    }
}
