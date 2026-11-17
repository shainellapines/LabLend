<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FR-N1: sent to the borrower when the admin rejects their reservation, including the reason.
 */
class ReservationRejected extends Notification
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
            ->subject("Not approved: {$r->reference_no}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your reservation **{$r->reference_no}** for \"{$r->purpose}\" was not approved.")
            ->line("Reason: {$r->rejection_reason}")
            ->line('You can make a new reservation for another time or with other items.')
            ->action('Make a new reservation', route('reservations.create'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rejected',
            'reservation_id' => $this->reservation->id,
            'message' => "{$this->reservation->reference_no} was rejected: {$this->reservation->rejection_reason}",
            'url' => route('reservations.show', $this->reservation, absolute: false),
        ];
    }
}
