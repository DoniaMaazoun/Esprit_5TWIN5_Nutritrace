<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Commande consommateur
 * Relie un User à des OrderItem via DistributorStock
 */
class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'total_amount',
        'currency',
        'delivery_name',
        'delivery_phone',
        'delivery_address',
        'delivery_city',
        'delivery_governorate',
        'delivery_notes',
        'payment_method',
        'payment_status',
        'payment_reference',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'stripe_status',
        'stripe_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scope pour filtrer par utilisateur (isolation)
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Générer un numéro de commande unique
     * Format: CMD-AAAAMMJJ-XXXXX
     */
    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $random = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        return "CMD-{$date}-{$random}";
    }

    /**
     * Obtenir le libellé du statut de commande
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'processing' => 'En préparation',
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir la couleur du badge de statut
     */
    public function getStatusBadgeColor(): string
    {
        return match($this->status) {
            'pending' => 'bg-warning',
            'confirmed' => 'bg-info',
            'processing' => 'bg-primary',
            'shipped' => 'bg-secondary',
            'delivered' => 'bg-success',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Obtenir le libellé du statut de paiement
     */
    public function getPaymentStatusLabel(): string
    {
        return match($this->payment_status) {
            'pending' => 'En attente',
            'paid' => 'Payé',
            'failed' => 'Échoué',
            'refunded' => 'Remboursé',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir le libellé du mode de paiement
     */
    public function getPaymentMethodLabel(): string
    {
        return match($this->payment_method) {
            'cash_on_delivery' => 'Paiement à la livraison',
            'credit_card' => 'Carte bancaire',
            'bank_transfer' => 'Virement bancaire',
            'stripe' => 'Carte bancaire (Stripe)',
            default => 'Inconnu',
        };
    }

    /**
     * Formater le montant total
     */
    public function getFormattedTotal(): string
    {
        return number_format($this->total_amount, 2, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * Vérifier si la commande peut être annulée
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }
}
