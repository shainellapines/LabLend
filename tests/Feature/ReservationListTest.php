<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-R8: "My Reservations" tabs and the admin Requests list.
 */
class ReservationListTest extends TestCase
{
    use RefreshDatabase;

    // ================= My Reservations =================

    public function test_tabs_split_reservations_by_status(): void
    {
        $student = User::factory()->create();
        Reservation::factory()->for($student)->create(['purpose' => 'Pending upcoming']);
        Reservation::factory()->for($student)->approved()->create(['purpose' => 'Approved upcoming']);
        Reservation::factory()->for($student)->overdue()->create(['purpose' => 'Overdue borrowed']);
        Reservation::factory()->for($student)->returned()->create(['purpose' => 'Returned one']);
        Reservation::factory()->for($student)->at(now()->subDays(2), now()->subDays(2)->addHour())->create(['purpose' => 'Expired pending']);

        $this->actingAs($student)->get(route('reservations.index'))
            ->assertOk()
            ->assertSee('Pending upcoming')->assertSee('Approved upcoming')
            ->assertDontSee('Overdue borrowed')->assertDontSee('Returned one')->assertDontSee('Expired pending');

        $this->actingAs($student)->get(route('reservations.index', ['tab' => 'active']))
            ->assertSee('Overdue borrowed')->assertSee('Overdue')->assertDontSee('Pending upcoming');

        $this->actingAs($student)->get(route('reservations.index', ['tab' => 'history']))
            ->assertSee('Returned one')->assertSee('Expired pending')->assertDontSee('Approved upcoming');
    }

    public function test_users_only_see_their_own_reservations(): void
    {
        $student = User::factory()->create();
        Reservation::factory()->for($student)->create(['purpose' => 'Mine']);
        Reservation::factory()->create(['purpose' => 'Someone else']);

        $this->actingAs($student)->get(route('reservations.index'))
            ->assertSee('Mine')->assertDontSee('Someone else');
    }

    // ================= Admin Requests list =================

    public function test_requests_list_defaults_to_pending(): void
    {
        $admin = User::factory()->admin()->create();
        Reservation::factory()->create(['purpose' => 'Waiting one']);
        Reservation::factory()->approved()->create(['purpose' => 'Already approved']);

        $this->actingAs($admin)->get(route('admin.reservations.index'))
            ->assertOk()
            ->assertSee('Waiting one')
            ->assertDontSee('Already approved');

        $this->actingAs($admin)->get(route('admin.reservations.index', ['status' => 'all']))
            ->assertSee('Waiting one')->assertSee('Already approved');
    }

    public function test_requests_can_be_filtered_by_overdue_and_searched(): void
    {
        $admin = User::factory()->admin()->create();
        $paolo = User::factory()->create(['name' => 'Paolo Ramos']);
        Reservation::factory()->for($paolo)->overdue()->create(['reference_no' => 'RSV-2026-0077']);
        Reservation::factory()->released()->at(now()->subHour(), now()->addHour())->create(['reference_no' => 'RSV-2026-0080']);

        $this->actingAs($admin)->get(route('admin.reservations.index', ['status' => 'overdue']))
            ->assertSee('RSV-2026-0077')->assertDontSee('RSV-2026-0080');

        // Search by borrower name, reference no., item tag, and room name.
        $item = Equipment::factory()->create(['asset_tag' => 'PRJ-0042']);
        $room = Room::factory()->create(['name' => 'Audio-Visual Room']);
        $withItem = Reservation::factory()->create(['reference_no' => 'RSV-2026-0101']);
        $withItem->equipment()->attach($item);
        Reservation::factory()->for($room)->create(['reference_no' => 'RSV-2026-0102']);

        $search = fn (string $term) => $this->actingAs($admin)->get(route('admin.reservations.index', ['status' => 'all', 'search' => $term]));
        $search('Paolo')->assertSee('RSV-2026-0077')->assertDontSee('RSV-2026-0080');
        $search('0080')->assertSee('RSV-2026-0080')->assertDontSee('RSV-2026-0077');
        $search('PRJ-0042')->assertSee('RSV-2026-0101')->assertDontSee('RSV-2026-0102');
        $search('Audio-Visual')->assertSee('RSV-2026-0102')->assertDontSee('RSV-2026-0101');
    }

    public function test_requests_can_be_filtered_by_date_range(): void
    {
        $admin = User::factory()->admin()->create();
        $soon = now()->addDays(2)->setTime(9, 0);
        $later = now()->addDays(9)->setTime(9, 0);
        Reservation::factory()->at($soon, $soon->copy()->addHour())->create(['reference_no' => 'RSV-2026-0201']);
        Reservation::factory()->at($later, $later->copy()->addHour())->create(['reference_no' => 'RSV-2026-0202']);

        $this->actingAs($admin)->get(route('admin.reservations.index', [
            'status' => 'all', 'from' => $later->toDateString(), 'to' => $later->toDateString(),
        ]))->assertSee('RSV-2026-0202')->assertDontSee('RSV-2026-0201');
    }

    public function test_quick_approve_from_the_list_returns_to_the_list(): void
    {
        $admin = User::factory()->admin()->create();
        $reservation = Reservation::factory()->create();
        $reservation->equipment()->attach(Equipment::factory()->create());

        $this->actingAs($admin)
            ->from(route('admin.reservations.index'))
            ->patch(route('admin.reservations.approve', $reservation))
            ->assertRedirect(route('admin.reservations.index'));

        $this->assertSame('approved', $reservation->fresh()->status->value);
    }

    public function test_warning_when_a_requested_item_is_no_longer_available(): void
    {
        $admin = User::factory()->admin()->create();
        $reservation = Reservation::factory()->create();
        $reservation->equipment()->attach(Equipment::factory()->underMaintenance()->create());

        $this->actingAs($admin)->get(route('admin.reservations.index'))
            ->assertSee('Item no longer available');
    }

    public function test_sidebar_shows_pending_count_for_admin_only(): void
    {
        Reservation::factory()->count(3)->sequence(fn ($s) => ['start_at' => now()->addDays($s->index + 1), 'end_at' => now()->addDays($s->index + 1)->addHour()])->create();

        $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))
            ->assertSee('title="3 pending"', false);

        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertDontSee('title="3 pending"', false);
    }

    public function test_non_admin_cannot_open_the_requests_list(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.reservations.index'))->assertForbidden();
    }

    public function test_quick_action_link_opens_the_matching_modal(): void
    {
        $admin = User::factory()->admin()->create();
        $reservation = Reservation::factory()->approved()->create();

        // The release modal is rendered with show=true when ?action=release is in the URL.
        $this->actingAs($admin)
            ->get(route('reservations.show', [$reservation, 'action' => 'release']))
            ->assertSee("show: true", false);
    }
}
