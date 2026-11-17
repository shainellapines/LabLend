<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * FR-N4 (optional): lablend:expire-pending.
 */
class ExpirePendingTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_pending_reservations_that_already_started_are_cancelled(): void
    {
        Notification::fake();
        $expired = Reservation::factory()->at(now()->subHour(), now()->addHour())->create();          // pending, started
        $future = Reservation::factory()->create();                                                     // pending, tomorrow
        $approved = Reservation::factory()->approved()->at(now()->subHour(), now()->addHour())->create(); // started but approved

        $this->artisan('lablend:expire-pending')
            ->expectsOutputToContain('Cancelled 1 expired pending reservation(s).')
            ->assertSuccessful();

        $this->assertSame(ReservationStatus::Cancelled, $expired->fresh()->status);
        $this->assertStringContainsString('not approved before the start time', $expired->fresh()->remarks);
        $this->assertSame(ReservationStatus::Pending, $future->fresh()->status);
        $this->assertSame(ReservationStatus::Approved, $approved->fresh()->status);
    }

    public function test_command_runs_every_hour(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'lablend:expire-pending'));

        $this->assertNotNull($event);
        $this->assertSame('0 * * * *', $event->expression);
    }
}
