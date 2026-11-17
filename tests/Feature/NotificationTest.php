<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Notifications\NewReservationRequest;
use App\Notifications\ReservationApproved;
use App\Notifications\ReservationOverdue;
use App\Notifications\ReservationRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * FR-N1 (which events send notifications) and FR-N2 (the bell).
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    // ================= FR-N1: who gets what =================

    public function test_new_request_notifies_active_admins_only(): void
    {
        Notification::fake(); // record notifications instead of sending them
        $admin = User::factory()->admin()->create();
        $inactiveAdmin = User::factory()->admin()->inactive()->create();
        $student = User::factory()->create();
        $start = now()->addDay()->setTime(13, 0);

        $this->actingAs($student)->post(route('reservations.store'), [
            'purpose' => 'Defense',
            'start_at' => $start->format('Y-m-d\TH:i'),
            'end_at' => $start->copy()->addHours(2)->format('Y-m-d\TH:i'),
            'room_id' => Room::factory()->create()->id,
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo($admin, NewReservationRequest::class, fn ($n, $channels) => $channels === ['database']);
        Notification::assertNotSentTo([$inactiveAdmin, $student], NewReservationRequest::class);
    }

    public function test_rejected_or_conflicting_requests_send_nothing(): void
    {
        Notification::fake();
        User::factory()->admin()->create();

        $this->actingAs(User::factory()->create())->post(route('reservations.store'), ['purpose' => 'x'])
            ->assertSessionHasErrors();

        Notification::assertNothingSent();
    }

    public function test_approval_emails_and_notifies_the_borrower(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $reservation = Reservation::factory()->create();
        $reservation->equipment()->attach(Equipment::factory()->create());

        $this->actingAs($admin)->patch(route('admin.reservations.approve', $reservation));

        Notification::assertSentTo($reservation->user, ReservationApproved::class,
            fn ($n, $channels) => $channels === ['mail', 'database'] && $n->reservation->is($reservation));
    }

    public function test_rejection_notification_includes_the_reason(): void
    {
        Notification::fake();
        $reservation = Reservation::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.reservations.reject', $reservation), ['rejection_reason' => 'Projector needed for an event']);

        Notification::assertSentTo($reservation->user, ReservationRejected::class, function ($n) use ($reservation) {
            $mail = $n->toMail($reservation->user);

            return str_contains(implode(' ', $mail->introLines), 'Projector needed for an event')
                && str_contains($n->toArray($reservation->user)['message'], 'Projector needed for an event');
        });
    }

    public function test_other_status_changes_do_not_notify(): void
    {
        Notification::fake();
        $reservation = Reservation::factory()->create();

        $this->actingAs($reservation->user)->patch(route('reservations.cancel', $reservation));

        Notification::assertNothingSent();
    }

    public function test_overdue_email_lists_the_items(): void
    {
        $reservation = Reservation::factory()->overdue()->create(['reference_no' => 'RSV-2026-0077']);
        $reservation->equipment()->attach(Equipment::factory()->create(['asset_tag' => 'PRJ-0002', 'name' => 'Epson Projector']));

        $mail = (new ReservationOverdue($reservation->fresh()))->toMail($reservation->user);

        $this->assertStringContainsString('RSV-2026-0077', $mail->subject);
        $this->assertStringContainsString('PRJ-0002 Epson Projector', implode(' ', $mail->introLines));

        // The real HTML email renders (Laravel's mail template, with LabLend as the app name).
        $html = (string) $mail->render();
        $this->assertStringContainsString('RSV-2026-0077', $html);
        $this->assertStringContainsString('View reservation', $html);
    }

    // ================= FR-N2: the bell =================

    public function test_bell_shows_unread_count_and_latest_messages(): void
    {
        $student = User::factory()->create();
        $reservation = Reservation::factory()->for($student)->approved()->create(['reference_no' => 'RSV-2026-0500']);
        $student->notify(new ReservationApproved($reservation));

        $this->actingAs($student)->get(route('dashboard'))
            ->assertSee('data-unread="1"', false)
            ->assertSee('RSV-2026-0500 was approved.');
    }

    public function test_clicking_a_notification_marks_it_read_and_opens_the_reservation(): void
    {
        $student = User::factory()->create();
        $reservation = Reservation::factory()->for($student)->approved()->create();
        $student->notify(new ReservationApproved($reservation));
        $notification = $student->notifications()->first();

        $this->actingAs($student)
            ->patch(route('notifications.read', $notification->id))
            ->assertRedirect(route('reservations.show', $reservation, absolute: false));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->actingAs($student)->get(route('dashboard'))->assertDontSee('data-unread=', false);
    }

    public function test_users_cannot_open_someone_elses_notification(): void
    {
        $owner = User::factory()->create();
        $owner->notify(new ReservationApproved(Reservation::factory()->for($owner)->create()));

        $this->actingAs(User::factory()->create())
            ->patch(route('notifications.read', $owner->notifications()->first()->id))
            ->assertNotFound();
    }

    public function test_mark_all_as_read(): void
    {
        $student = User::factory()->create();
        foreach (Reservation::factory()->for($student)->count(3)->create() as $reservation) {
            $student->notify(new ReservationApproved($reservation));
        }

        $this->actingAs($student)->patch(route('notifications.read-all'))->assertSessionHas('success');

        $this->assertSame(0, $student->unreadNotifications()->count());
    }
}
