<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'pro_type',
        'company_name',
        'tax_id',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Helper Methods for User Roles
     */

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a professional
     */
    public function isPro(): bool
    {
        return $this->role === 'pro';
    }

    /**
     * Check if user is a consumer
     */
    public function isConsumer(): bool
    {
        return $this->role === 'consumer';
    }

    /**
     * Check if professional account is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if professional account is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if account is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if professional is a distributor (Module 3)
     */
    public function isDistributor(): bool
    {
        return $this->isPro() && $this->pro_type === 'distributor';
    }

    /**
     * Module 3 - Expéditions du distributeur
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Module 3 - Points de vente du distributeur
     */
    public function pointsOfSale(): HasMany
    {
        return $this->hasMany(PointOfSale::class);
    }

    /**
     * Get professional type label in French
     */
    public function getProTypeLabel(): string
    {
        return match($this->pro_type) {
            'producer' => 'Producteur / Agriculteur',
            'processor' => 'Transformateur / Usine',
            'distributor' => 'Distributeur / Logistique',
            default => 'Non défini',
        };
    }
}
