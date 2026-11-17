<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Notifications\Notification;

/**
 * FR-N1: tells every admin (bell only, no email) that a new request is waiting for approval.
 */
class NewReservationRequest extends Notification
{
    public function __construct(public Reservation $reservation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_request',
            'reservation_id' => $this->reservation->id,
            'message' => "New request {$this->reservation->reference_no} from {$this->reservation->user->name}.",
            'url' => route('reservations.show', $this->reservation, absolute: false),
        ];
    }
}
