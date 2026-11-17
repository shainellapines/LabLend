<?php

namespace App\Models;

use App\Enums\EquipmentStatus;
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

    /**
     * "My Reservations" tabs (FR-R8):
     *   upcoming = pending/approved that have not ended yet
     *   active   = released (the borrower has the items now, including overdue)
     *   history  = everything else (returned, rejected, cancelled, and pending/approved whose time passed)
     */
    public function scopeForTab(Builder $query, string $tab): Builder
    {
        $waiting = [ReservationStatus::Pending->value, ReservationStatus::Approved->value];

        return match ($tab) {
            'active' => $query->where('status', ReservationStatus::Released),
            'history' => $query->where(fn (Builder $q) => $q
                ->whereNotIn('status', [...$waiting, ReservationStatus::Released->value])
                ->orWhere(fn (Builder $q) => $q->whereIn('status', $waiting)->where('end_at', '<=', now()))),
            default => $query->whereIn('status', $waiting)->where('end_at', '>', now()),
        };
    }

    /** One search box for the admin list: reference no., borrower, item, or room. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = "%{$term}%";

        return $query->where(fn (Builder $q) => $q
            ->where('reference_no', 'like', $like)
            ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('id_number', 'like', $like))
            ->orWhereHas('equipment', fn (Builder $e) => $e->where('asset_tag', 'like', $like)->orWhere('name', 'like', $like))
            ->orWhereHas('room', fn (Builder $r) => $r->where('name', 'like', $like)->orWhere('code', 'like', $like)));
    }

    /** Admin list filters: status (plus the computed "overdue"), and a date range on the start date. */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $status = $filters['status'] ?? null;

        return $query
            ->when($status === 'overdue', fn (Builder $q) => $q->overdue())
            ->when($status && $status !== 'overdue' && $status !== 'all', fn (Builder $q) => $q->where('status', $status))
            ->when($filters['from'] ?? null, fn (Builder $q, $from) => $q->whereDate('start_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, $to) => $q->whereDate('start_at', '<=', $to))
            ->search($filters['search'] ?? null);
    }

    // ---------- Helpers ----------

    /**
     * FR-D3: a not-yet-released reservation that includes an item now under maintenance, retired, or deleted.
     * Uses the already-loaded equipment (eager loaded in the list), so it does not run a query per row.
     */
    public function hasUnavailableItems(): bool
    {
        if (! in_array($this->status, [ReservationStatus::Pending, ReservationStatus::Approved], true)) {
            return false;
        }

        return $this->equipment->contains(fn (Equipment $item) => $item->trashed() || $item->status !== EquipmentStatus::Available);
    }

    /** Short text for lists: "PRJ-0001, CBL-0003 +1 · Computer Lab 2". */
    public function summary(): string
    {
        $tags = $this->equipment->pluck('asset_tag');
        $items = $tags->take(2)->implode(', ') . ($tags->count() > 2 ? ' +' . ($tags->count() - 2) : '');

        return collect([$items, $this->room?->name])->filter()->implode(' · ') ?: '—';
    }

    public function isOverdue(): bool
    {
        return $this->status === ReservationStatus::Released && $this->end_at->isPast();
    }
}
