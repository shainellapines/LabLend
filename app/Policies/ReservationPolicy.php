<?php

namespace App\Policies;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;

/**
 * Borrowers see and edit their own reservations; the admin sees all of them.
 */
class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $user->isAdmin() || (int) $reservation->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true; // any logged-in, active user (borrowing rules are checked in ReservationService)
    }

    /**
     * FR-R4: borrowers cancel their own reservations; the admin can cancel any.
     * WHICH statuses can be cancelled (pending/approved) is checked by ReservationService::transition().
     */
    public function cancel(User $user, Reservation $reservation): bool
    {
        return $user->isAdmin() || (int) $reservation->user_id === $user->id;
    }

    /** FR-R4: only the admin approves, rejects, releases, and receives. */
    public function manage(User $user, Reservation $reservation): bool
    {
        return $user->isAdmin();
    }

    /** FR-R9: only the borrower, and only while the reservation is still pending. */
    public function update(User $user, Reservation $reservation): bool
    {
        return (int) $reservation->user_id === $user->id
            && $reservation->status === ReservationStatus::Pending;
    }
}
