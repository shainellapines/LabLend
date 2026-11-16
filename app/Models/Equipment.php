<?php

namespace App\Models;

use App\Enums\EquipmentCondition;
use App\Enums\EquipmentStatus;
use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * One physical item, e.g. "Epson Projector" with asset tag PRJ-0004.
 * Table name is "equipment" (Laravel knows "equipment" has no plural).
 */
#[Fillable([
    'category_id', 'asset_tag', 'name', 'brand', 'model', 'serial_number',
    'location', 'condition', 'status', 'description', 'photo_path',
])]
class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory;

    // Soft delete: delete() only fills deleted_at, so old reservations and damage reports
    // can still show the item (FR-E5). Normal queries skip soft-deleted rows automatically.
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'condition' => EquipmentCondition::class,
            'status' => EquipmentStatus::class,
        ];
    }

    // ---------- Relationships ----------

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Many-to-many through the equipment_reservation pivot table (with the item's conditions). */
    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class)
            ->withPivot('condition_out', 'condition_in')
            ->withTimestamps();
    }

    public function damageReports(): HasMany
    {
        return $this->hasMany(DamageReport::class);
    }

    // ---------- Scopes ----------

    /** Items that may be reserved: status "available" (FR-E6). Usage: Equipment::reservable()->get() */
    public function scopeReservable(Builder $query): Builder
    {
        return $query->where('status', EquipmentStatus::Available);
    }

    /**
     * Search + filters for the equipment list (FR-E2).
     * $filters comes from the query string: ['search' => 'epson', 'category' => 1, 'status' => 'available', ...].
     * Empty values are skipped, so "All categories" simply means no where-clause.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn (Builder $q, string $term) => $q->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('asset_tag', 'like', "%{$term}%")
                  ->orWhere('serial_number', 'like', "%{$term}%");
            }))
            ->when($filters['category'] ?? null, fn (Builder $q, $id) => $q->where('category_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['condition'] ?? null, fn (Builder $q, $condition) => $q->where('condition', $condition));
    }

    // ---------- Photo ----------

    /** Public URL of the photo, or null. Usage in Blade: $item->photoUrl() */
    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    /** Section 9.3: when a record is permanently deleted (not soft-deleted), remove its photo file too. */
    protected static function booted(): void
    {
        static::forceDeleted(function (Equipment $equipment) {
            if ($equipment->photo_path) {
                Storage::disk('public')->delete($equipment->photo_path);
            }
        });
    }
}
