<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FR-N1: sent to the borrower when the admin approves their reservation.
 * A Notification class describes ONE message and how it looks on each channel:
 * via() picks the channels, toMail() builds the email, toArray() is what the bell (database) stores.
 */
class ReservationApproved extends Notification
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

        return (new MailMessage)
            ->subject("Approved: {$r->reference_no}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your reservation **{$r->reference_no}** for \"{$r->purpose}\" was approved.")
            ->line('Schedule: ' . $r->start_at->format('M d, Y g:i A') . ' – ' . $r->end_at->format('M d, Y g:i A'))
            ->line('Please pick up the items from the lab custodian at the start time.')
            ->action('View reservation', route('reservations.show', $r));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'approved',
            'reservation_id' => $this->reservation->id,
            'message' => "{$this->reservation->reference_no} was approved.",
            'url' => route('reservations.show', $this->reservation, absolute: false),
        ];
    }
}
