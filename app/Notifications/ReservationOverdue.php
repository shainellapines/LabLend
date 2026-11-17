<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FR-N1 / FR-N3: reminder to a borrower whose items are overdue.
 * Sent by the daily lablend:send-overdue-reminders command (step 3.2).
 */
class ReservationOverdue extends Notification
{
    public function __construct(public Reservation $reservation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->reservation;
        $items = $r->equipment->map(fn ($item) => "{$item->asset_tag} {$item->name}")->implode(', ');

        return (new MailMessage)
            ->subject("Overdue: please return {$r->reference_no}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your reservation **{$r->reference_no}** was due back on " . $r->end_at->format('M d, Y g:i A') . '.')
            ->line($items ? "Items: {$items}" : 'Please end your use of the room.')
            ->line('Please return them to the lab custodian as soon as possible. You cannot make new reservations until they are returned.')
            ->action('View reservation', route('reservations.show', $r));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'overdue',
            'reservation_id' => $this->reservation->id,
            'message' => "{$this->reservation->reference_no} is overdue. Please return the items.",
            'url' => route('reservations.show', $this->reservation, absolute: false),
        ];
    }
}
