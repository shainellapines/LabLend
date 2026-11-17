<?php

namespace Tests\Feature;

use App\Models\DamageReport;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboards (FR-H1 admin, FR-H2 borrower) and the ReportService numbers behind them.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    // ================= ReportService =================

    public function test_admin_stats_count_the_right_things(): void
    {
        Reservation::factory()->count(2)->sequence(fn ($s) => ['start_at' => now()->addDays($s->index + 1), 'end_at' => now()->addDays($s->index + 1)->addHour()])->create(); // 2 pending
        $out = Reservation::factory()->released()->at(now()->subHour(), now()->addHour())->create();
        $out->equipment()->attach(Equipment::factory()->count(2)->create()); // 2 items out
        Reservation::factory()->overdue()->create()->equipment()->attach(Equipment::factory()->create()); // 1 more item out, overdue
        DamageReport::factory()->create();                               // open
        DamageReport::factory()->create(['status' => 'resolved']);       // not counted
        Equipment::factory()->underMaintenance()->create();

        $stats = app(ReportService::class)->adminStats();

        $this->assertSame(2, $stats['pending']);
        $this->assertSame(3, $stats['borrowedItems']);
        $this->assertSame(1, $stats['overdue']);
        $this->assertSame(1, $stats['openDamage']);
        $this->assertSame(1, $stats['underMaintenance']);
    }

    public function test_reservations_per_day_fills_missing_days_with_zero(): void
    {
        Reservation::factory()->create(['created_at' => now()->subDays(2)]);
        Reservation::factory()->count(3)->create(['created_at' => now()]);
        Reservation::factory()->create(['created_at' => now()->subDays(20)]); // outside the 14 days

        $series = app(ReportService::class)->reservationsPerDay(14);

        $this->assertCount(14, $series);
        $this->assertSame(3, $series[today()->toDateString()]);
        $this->assertSame(1, $series[today()->subDays(2)->toDateString()]);
        $this->assertSame(0, $series[today()->subDay()->toDateString()]);
        $this->assertSame(4, array_sum($series));
    }

    public function test_top_borrowed_items_counts_only_handed_over_reservations_in_30_days(): void
    {
        $popular = Equipment::factory()->create(['asset_tag' => 'PRJ-0001']);
        $once = Equipment::factory()->create(['asset_tag' => 'LAP-0001']);
        $never = Equipment::factory()->create(['asset_tag' => 'CBL-0001']);

        foreach (range(1, 3) as $i) {
            Reservation::factory()->returned()->at(now()->subDays($i), now()->subDays($i)->addHour())->create()->equipment()->attach($popular);
        }
        Reservation::factory()->returned()->at(now()->subDays(5), now()->subDays(5)->addHour())->create()->equipment()->attach($once);
        Reservation::factory()->returned()->at(now()->subDays(40), now()->subDays(40)->addHour())->create()->equipment()->attach($once); // too old
        Reservation::factory()->create()->equipment()->attach($never); // only pending: not borrowed

        $top = app(ReportService::class)->topBorrowedItems(30, 5);

        $this->assertSame(['PRJ-0001', 'LAP-0001'], $top->pluck('asset_tag')->all());
        $this->assertSame([3, 1], $top->pluck('borrow_count')->all());
    }

    // ================= Admin dashboard (FR-H1) =================

    public function test_admin_dashboard_shows_stats_schedule_and_charts(): void
    {
        $admin = User::factory()->admin()->create();
        $borrower = User::factory()->create(['name' => 'Juan Dela Cruz']);
        $start = now()->setTime(23, 0);
        Reservation::factory()->for($borrower)->at($start, $start->copy()->addMinutes(30))->approved()->create(); // to release today
        $item = Equipment::factory()->create(['asset_tag' => 'PRJ-0001']);
        Reservation::factory()->returned()->at(now()->subDays(3), now()->subDays(3)->addHour())->create()->equipment()->attach($item);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pending requests')
            ->assertSee("Today's schedule")
            ->assertSee('To release (1)')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('Reservation requests per day')
            ->assertSee('x-data="chart(', false)
            ->assertSee('PRJ-0001'); // in the top-items chart data / accessible table
    }

    // ================= Borrower dashboard (FR-H2) =================

    public function test_borrower_dashboard_shows_own_upcoming_and_borrowed_items(): void
    {
        $student = User::factory()->create();
        Reservation::factory()->for($student)->create(['purpose' => 'My thesis defense']);
        Reservation::factory()->create(['purpose' => 'Someone else']);
        Reservation::factory()->for($student)->released()->at(now()->subHour(), now()->addHours(2))->create(['reference_no' => 'RSV-2026-0700']);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My thesis defense')
            ->assertDontSee('Someone else')
            ->assertSee('RSV-2026-0700')
            ->assertDontSee('Pending requests'); // admin tiles are not shown
    }

    public function test_borrower_with_overdue_item_sees_a_warning(): void
    {
        $student = User::factory()->create();
        Reservation::factory()->for($student)->overdue()->create();

        $this->actingAs($student)->get(route('dashboard'))
            ->assertSee('You have 1 overdue reservation.');
    }
}
