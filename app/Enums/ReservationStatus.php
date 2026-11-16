<?php

namespace App\Enums;

/**
 * Reservation status flow (FR-R4):
 *   pending  -> approved | rejected | cancelled
 *   approved -> released | cancelled
 *   released -> returned
 *   rejected, cancelled, returned are final.
 *
 * "Overdue" is NOT a status: it is computed (released + end_at in the past). See Reservation::isOverdue().
 */
enum ReservationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Released = 'released';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Released => 'Released',
            self::Returned => 'Returned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'blue',
            self::Released => 'indigo',
            self::Returned => 'green',
            self::Rejected => 'red',
            self::Cancelled => 'gray',
        };
    }

    /** The statuses this one may change to. Used by ReservationService::transition() in step 2.5. */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Approved, self::Rejected, self::Cancelled],
            self::Approved => [self::Released, self::Cancelled],
            self::Released => [self::Returned],
            self::Rejected, self::Cancelled, self::Returned => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /** Statuses that "hold" an item or room: they block overlapping reservations (FR-R2). */
    public static function activeValues(): array
    {
        return [self::Pending->value, self::Approved->value, self::Released->value];
    }
}
