<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ReservationOverdue;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * FR-N3: lablend:send-overdue-reminders + the daily schedule, and FR-R3: overdue borrowers are blocked.
 */
class OverdueRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_one_notification_per_overdue_reservation(): void
    {
        Notification::fake();
        $first = Reservation::factory()->overdue()->create();
        $second = Reservation::factory()->overdue()->create();
        Reservation::factory()->released()->at(now()->subHour(), now()->addHour())->create(); // out, but not due yet
        Reservation::factory()->returned()->at(now()->subDays(2), now()->subDays(2)->addHour())->create(); // returned

        $this->artisan('lablend:send-overdue-reminders')
            ->expectsOutputToContain('Sent 2 overdue reminder(s).')
            ->assertSuccessful();

        Notification::assertSentTo($first->user, ReservationOverdue::class);
        Notification::assertSentTo($second->user, ReservationOverdue::class);
        Notification::assertCount(2);
        $this->assertNotNull($first->fresh()->overdue_notified_at);
    }

    public function test_reminder_is_sent_at_most_once_per_day(): void
    {
        Notification::fake();
        $reservation = Reservation::factory()->overdue()->create();

        $this->artisan('lablend:send-overdue-reminders');
        $this->artisan('lablend:send-overdue-reminders')->expectsOutputToContain('Sent 0 overdue reminder(s).');
        Notification::assertSentToTimes($reservation->user, ReservationOverdue::class, 1);

        // Reminded yesterday -> reminded again today.
        $reservation->forceFill(['overdue_notified_at' => now()->subDay()])->save();
        $this->artisan('lablend:send-overdue-reminders');
        Notification::assertSentToTimes($reservation->user, ReservationOverdue::class, 2);
    }

    public function test_command_is_scheduled_daily_at_8_am(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'lablend:send-overdue-reminders'));

        $this->assertNotNull($event, 'The overdue reminder command is not scheduled.');
        $this->assertSame('0 8 * * *', $event->expression); // cron for "every day at 08:00"
    }

    public function test_reminded_borrower_is_blocked_from_reserving(): void
    {
        $borrower = User::factory()->create();
        Reservation::factory()->for($borrower)->overdue()->create();
        $start = now()->addDay()->setTime(13, 0);

        $this->actingAs($borrower)->post(route('reservations.store'), [
            'purpose' => 'Another class',
            'start_at' => $start->format('Y-m-d\TH:i'),
            'end_at' => $start->copy()->addHours(2)->format('Y-m-d\TH:i'),
            'room_id' => Room::factory()->create()->id,
        ])->assertSessionHasErrors('reservation');
    }
}
