<?php

namespace App\Console\Commands;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Console\Command;

/**
 * FR-N4 (optional): cancels "pending" reservations whose start time has already passed,
 * because nobody approved them in time. This frees the items and the borrower's student limit.
 *
 * Runs every hour (routes/console.php), or by hand: php artisan lablend:expire-pending
 */
class ExpirePendingReservations extends Command
{
    protected $signature = 'lablend:expire-pending';

    protected $description = 'Cancel pending reservations whose start time has already passed';

    public function handle(ReservationService $reservations): int
    {
        $expired = Reservation::where('status', ReservationStatus::Pending)
            ->where('start_at', '<=', now())
            ->get();

        foreach ($expired as $reservation) {
            // Same gatekeeper as every other status change (pending -> cancelled is allowed by the flow).
            $reservations->transition($reservation, ReservationStatus::Cancelled);

            $reservation->remarks = 'Cancelled automatically: not approved before the start time.';
            $reservation->save();

            $this->line("  Cancelled {$reservation->reference_no} (was due to start {$reservation->start_at->format('M d, Y g:i A')})");
        }

        $this->info("Cancelled {$expired->count()} expired pending reservation(s).");

        return self::SUCCESS;
    }
}
