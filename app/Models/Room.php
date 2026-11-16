<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use App\Enums\RoomType;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'type', 'capacity', 'location', 'description', 'is_active'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => RoomType::class,
            'is_active' => 'boolean',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** Rooms that may be reserved (inactive rooms cannot, FR-RM2). */
    public function scopeReservable(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Search by name/code/location and filter by type, for the rooms list. */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn (Builder $q, string $term) => $q->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%")
                  ->orWhere('location', 'like', "%{$term}%");
            }))
            ->when($filters['type'] ?? null, fn (Builder $q, $type) => $q->where('type', $type));
    }

    /** Active (pending/approved/released) reservations from now until $days days ahead, soonest first (FR-RM2). */
    public function upcomingReservations(int $days = 7): HasMany
    {
        return $this->reservations()
            ->active()
            ->where('end_at', '>', now())
            ->where('start_at', '<', now()->addDays($days)->endOfDay())
            ->orderBy('start_at');
    }

    /** The approved or released reservation using the room right now, if any. */
    public function currentReservation(): ?Reservation
    {
        return $this->reservations()
            ->with('user')
            ->whereIn('status', [ReservationStatus::Approved, ReservationStatus::Released])
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->first();
    }
}
