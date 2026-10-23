<?php

namespace App\Models;

use Database\Factories\FarmFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Farm extends Model
{
    use HasFactory;

    /**
     * Les 24 gouvernorats tunisiens.
     */
    public const GOVERNORATES = [
        'Ariana', 'Béja', 'Ben Arous', 'Bizerte', 'Gabès', 'Gafsa', 'Jendouba', 'Kairouan',
        'Kasserine', 'Kébili', 'Le Kef', 'Mahdia', 'La Manouba', 'Médenine', 'Monastir', 'Nabeul',
        'Sfax', 'Sidi Bouzid', 'Siliana', 'Sousse', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan',
    ];

    public const FARMING_TYPES = [
        'organic' => 'Biologique',
        'conventional' => 'Conventionnelle',
        'reasoned' => 'Raisonnée',
        'permaculture' => 'Permaculture',
        'agroforestry' => 'Agroforesterie',
    ];

    public const IRRIGATION_TYPES = [
        'drip' => 'Goutte-à-goutte',
        'sprinkler' => 'Aspersion',
        'gravity' => 'Gravitaire',
        'rainfed' => 'Pluviale (sans irrigation)',
        'mixed' => 'Mixte',
    ];

    public const SOIL_TYPES = [
        'clay' => 'Argileux',
        'sandy' => 'Sableux',
        'loamy' => 'Limoneux',
        'limestone' => 'Calcaire',
        'silty' => 'Limono-argileux',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'fallow' => 'En jachère',
        'inactive' => 'Inactive',
    ];

    protected $fillable = [
        'name', 'description', 'governorate', 'city', 'address', 'latitude', 'longitude',
        'total_area', 'farming_type', 'irrigation_type', 'soil_type', 'main_crops',
        'certification', 'established_year', 'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'total_area' => 'float',
            'established_year' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Les récoltes sont supprimées en cascade par la base : on nettoie leurs photos avant
        static::deleting(fn (Farm $farm) => $farm->harvests->each->deleteImageFile());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
    }

    /**
     * Dernier produit photographié : sert de couverture à l'exploitation.
     */
    public function coverHarvest(): HasOne
    {
        return $this->hasOne(Harvest::class)->ofMany(['id' => 'max'], fn ($q) => $q->whereNotNull('image_path'));
    }

    public function coverUrl(): ?string
    {
        return $this->coverHarvest?->imageUrl();
    }

    public function publicUrl(): string
    {
        return route('farms.public', $this);
    }

    /**
     * Filtre de recherche multi-critères utilisé par la liste des exploitations.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function (Builder $q, string $term) {
                $q->where(fn (Builder $w) => $w
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('main_crops', 'like', "%{$term}%"));
            })
            ->when($filters['governorate'] ?? null, fn (Builder $q, $v) => $q->where('governorate', $v))
            ->when($filters['farming_type'] ?? null, fn (Builder $q, $v) => $q->where('farming_type', $v))
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v));
    }

    public function farmingTypeLabel(): string
    {
        return self::FARMING_TYPES[$this->farming_type] ?? $this->farming_type;
    }

    public function irrigationTypeLabel(): string
    {
        return self::IRRIGATION_TYPES[$this->irrigation_type] ?? $this->irrigation_type;
    }

    public function soilTypeLabel(): ?string
    {
        return $this->soil_type ? (self::SOIL_TYPES[$this->soil_type] ?? $this->soil_type) : null;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Quantité totale récoltée sur l'exploitation, convertie en kg.
     */
    public function totalHarvestedKg(): float
    {
        return $this->harvests->sum(fn (Harvest $h) => $h->quantityInKg());
    }

    /**
     * Rendement moyen de l'exploitation (kg / ha).
     */
    public function yieldPerHectare(): ?float
    {
        return $this->total_area > 0 ? round($this->totalHarvestedKg() / $this->total_area, 1) : null;
    }

    /**
     * La plus grande parcelle déclarée dans les récoltes : la surface totale
     * de l'exploitation ne peut pas descendre en dessous.
     */
    public function largestPlotArea(): float
    {
        return (float) $this->harvests()->max('plot_area');
    }
}
