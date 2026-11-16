<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A booking of one or more items and/or one room for a time slot.
 * Only the fields the borrower types are fillable. reference_no, user_id, status, approved_by, etc.
 * are set explicitly by ReservationService (step 2.4/2.5), never from form input.
 */
#[Fillable(['room_id', 'purpose', 'start_at', 'end_at', 'remarks'])]
class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'approved_at' => 'datetime',
            'released_at' => 'datetime',
            'returned_at' => 'datetime',
            'overdue_notified_at' => 'datetime',
        ];
    }

    // ---------- Relationships ----------

    /** The borrower. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The admin who approved it (approved_by column). */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** withTrashed(): a soft-deleted item still shows up in old reservations (FR-E5). */
    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class)
            ->withPivot('condition_out', 'condition_in')
            ->withTimestamps()
            ->withTrashed();
    }

    public function damageReports(): HasMany
    {
        return $this->hasMany(DamageReport::class);
    }

    // ---------- Scopes (reusable query pieces: Reservation::active()->overdue()->get()) ----------

    /** pending, approved, or released: these block the item/room (FR-R2). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ReservationStatus::activeValues());
    }

    /** Released but the end time has passed (FR-R7). */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', ReservationStatus::Released)
            ->where('end_at', '<', now());
    }

    /**
     * Reservations whose time range overlaps [$start, $end) (FR-R2).
     * Two ranges overlap when: existing.start < new.end AND existing.end > new.start.
     * Strict < and > mean "touching" is allowed: one ending at 3:00 PM and the next starting at 3:00 PM do not clash.
     */
    public function scopeOverlapping(Builder $query, $start, $end): Builder
    {
        return $query->where('start_at', '<', $end)
            ->where('end_at', '>', $start);
    }

    // ---------- Helpers ----------

    public function isOverdue(): bool
    {
        return $this->status === ReservationStatus::Released && $this->end_at->isPast();
    }
}
