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
}
