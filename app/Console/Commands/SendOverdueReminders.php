<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Notifications\ReservationOverdue;
use Illuminate\Console\Command;

/**
 * FR-N3: emails every borrower whose reservation is overdue, at most once per day.
 *
 * Runs automatically every day at 08:00 (see routes/console.php), or by hand for the demo:
 *   php artisan lablend:send-overdue-reminders
 */
class SendOverdueReminders extends Command
{
    protected $signature = 'lablend:send-overdue-reminders';

    protected $description = 'Send a reminder to each borrower with an overdue reservation (max once per day)';

    public function handle(): int
    {
        $reservations = Reservation::overdue()
            // "At most once per day": skip reservations already reminded today.
            ->where(fn ($q) => $q->whereNull('overdue_notified_at')->orWhere('overdue_notified_at', '<', today()))
            ->with('user', 'equipment')
            ->get();

        foreach ($reservations as $reservation) {
            try {
                $reservation->user->notify(new ReservationOverdue($reservation));

                $reservation->overdue_notified_at = now();
                $reservation->save();

                $this->line("  Reminded {$reservation->user->name} about {$reservation->reference_no} (due {$reservation->end_at->format('M d, Y g:i A')})");
            } catch (\Throwable $e) {
                report($e); // one failed email must not stop the others
                $this->error("  Could not remind {$reservation->user->email} about {$reservation->reference_no}");
            }
        }

        $this->info("Sent {$reservations->count()} overdue reminder(s).");

        return self::SUCCESS;
    }
}
