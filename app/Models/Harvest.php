<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Harvest extends Model
{
    /**
     * Unités acceptées et leur facteur de conversion vers le kg.
     */
    public const UNITS = [
        'kg' => ['label' => 'Kilogrammes (kg)', 'factor' => 1],
        'quintal' => ['label' => 'Quintaux (q)', 'factor' => 100],
        'tonne' => ['label' => 'Tonnes (t)', 'factor' => 1000],
    ];

    public const QUALITY_GRADES = [
        'A' => 'A — Extra',
        'B' => 'B — Catégorie I',
        'C' => 'C — Catégorie II',
    ];

    public const STATUSES = [
        'in_stock' => 'En stock',
        'shipped' => 'Expédié',
        'processed' => 'Transformé',
        'sold' => 'Vendu',
    ];

    protected $fillable = [
        'farm_id', 'product_name', 'category_id', 'variety', 'plot_name', 'plot_area',
        'planting_date', 'harvest_date', 'quantity', 'unit', 'quality_grade',
        'treatments', 'storage_conditions', 'notes', 'public_description', 'status',
    ];

    protected function casts(): array
    {
        return [
            'planting_date' => 'date',
            'harvest_date' => 'date',
            'quantity' => 'float',
            'plot_area' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Harvest $harvest) {
            $harvest->batch_code ??= self::generateBatchCode($harvest->harvest_date);
        });

        // La photo est supprimée du disque avec le lot
        static::deleting(fn (Harvest $harvest) => $harvest->deleteImageFile());
    }

    // ==================== PHOTO DU PRODUIT ====================

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    /**
     * Image à afficher : la photo du producteur, sinon une photo réelle du produit,
     * sinon le visuel de la catégorie.
     */
    public function displayImageUrl(): string
    {
        return $this->imageUrl()
            ?? Category::photoUrlFor($this->product_name)
            ?? $this->category?->illustrationUrl()
            ?? asset('frontend/images/categories/default.svg');
    }

    public function hasPhoto(): bool
    {
        return $this->image_path !== null;
    }

    /**
     * Remplace ou retire la photo du lot puis enregistre.
     */
    public function syncImage(?UploadedFile $file, bool $remove = false): void
    {
        if ($file || $remove) {
            $this->deleteImageFile();
            $this->image_path = null;
        }
        if ($file) {
            $this->image_path = $file->store('harvests', 'public');
        }
        $this->save();
    }

    public function deleteImageFile(): void
    {
        if ($this->image_path) {
            Storage::disk('public')->delete($this->image_path);
        }
    }

    /**
     * Code de lot unique : LOT-AAAAMMJJ-XXXXX (sert aussi d'identifiant du QR code).
     */
    public static function generateBatchCode($date = null): string
    {
        $prefix = 'LOT-' . Carbon::parse($date ?? now())->format('Ymd');

        do {
            $code = $prefix . '-' . Str::upper(Str::random(5));
        } while (self::where('batch_code', $code)->exists());

        return $code;
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Limite les récoltes à celles des exploitations d'un utilisateur.
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereHas('farm', fn (Builder $q) => $q->where('user_id', $user->id));
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function (Builder $q, string $term) {
                $q->where(fn (Builder $w) => $w
                    ->where('product_name', 'like', "%{$term}%")
                    ->orWhere('variety', 'like', "%{$term}%")
                    ->orWhere('batch_code', 'like', "%{$term}%"));
            })
            ->when($filters['farm_id'] ?? null, fn (Builder $q, $v) => $q->where('farm_id', $v))
            ->when($filters['category'] ?? null, fn (Builder $q, $v) => $q->where('category_id', $v))
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['from'] ?? null, fn (Builder $q, $v) => $q->whereDate('harvest_date', '>=', $v))
            ->when($filters['to'] ?? null, fn (Builder $q, $v) => $q->whereDate('harvest_date', '<=', $v));
    }

    public function quantityInKg(): float
    {
        return $this->quantity * (self::UNITS[$this->unit]['factor'] ?? 1);
    }

    /**
     * Rendement de la parcelle (kg / ha), si sa surface est connue.
     */
    public function yieldPerHectare(): ?float
    {
        return $this->plot_area > 0 ? round($this->quantityInKg() / $this->plot_area, 1) : null;
    }

    /**
     * Durée du cycle de culture, de la plantation à la récolte.
     */
    public function growingDays(): ?int
    {
        return $this->planting_date ? (int) $this->planting_date->diffInDays($this->harvest_date) : null;
    }

    public function categoryLabel(): string
    {
        return $this->category?->name ?? 'Sans catégorie';
    }

    public function categoryIcon(): string
    {
        return $this->category?->icon ?? 'fa-seedling';
    }

    public function unitShort(): string
    {
        return ['kg' => 'kg', 'quintal' => 'q', 'tonne' => 't'][$this->unit] ?? $this->unit;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function traceabilityUrl(): string
    {
        return route('traceability.show', $this->batch_code);
    }
}
