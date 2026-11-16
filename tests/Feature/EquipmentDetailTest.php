<?php

namespace Tests\Feature;

use App\Enums\DamageSeverity;
use App\Models\DamageReport;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Equipment detail page with computed availability (FR-E3, FR-E6).
 */
class EquipmentDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_is_found_by_asset_tag(): void
    {
        $item = Equipment::factory()->create(['asset_tag' => 'PRJ-0004', 'name' => 'Epson Projector']);

        $this->assertSame(url('/equipment/PRJ-0004'), route('equipment.show', $item)); // route() uses the tag

        $this->actingAs(User::factory()->create())
            ->get('/equipment/PRJ-0004')
            ->assertOk()
            ->assertSee('Epson Projector')
            ->assertSee('Available to reserve');
    }

    public function test_unknown_asset_tag_gives_404(): void
    {
        $this->actingAs(User::factory()->create())->get('/equipment/NOPE-0001')->assertNotFound();
    }

    public function test_guest_is_sent_to_login_and_back(): void
    {
        $item = Equipment::factory()->create(['asset_tag' => 'PRJ-0004']);

        // FR-E4: scanning the QR label while logged out -> login -> back to the item page.
        $this->get('/equipment/PRJ-0004')->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/equipment/PRJ-0004');
    }

    // ---------- Computed availability ----------

    public function test_available_item(): void
    {
        $this->assertSame('available', Equipment::factory()->create()->currentAvailability()['state']);
    }

    public function test_borrowed_item_shows_who_has_it(): void
    {
        $item = Equipment::factory()->create();
        $borrower = User::factory()->create(['name' => 'Juan Dela Cruz']);
        $start = now()->subHour();
        Reservation::factory()->for($borrower)->at($start, now()->addHour())->released()->create()->equipment()->attach($item);

        $availability = $item->currentAvailability();
        $this->assertSame('borrowed', $availability['state']);
        $this->assertTrue($availability['reservation']->user->is($borrower));

        $this->actingAs(User::factory()->create())
            ->get(route('equipment.show', $item))
            ->assertSee('Borrowed by Juan Dela Cruz');
    }

    public function test_overdue_item(): void
    {
        $item = Equipment::factory()->create();
        Reservation::factory()->overdue()->create()->equipment()->attach($item);

        $this->assertSame('overdue', $item->currentAvailability()['state']);
    }

    public function test_reserved_right_now_but_not_picked_up(): void
    {
        $item = Equipment::factory()->create();
        Reservation::factory()->at(now()->subMinutes(30), now()->addHour())->approved()->create()->equipment()->attach($item);

        $this->assertSame('reserved', $item->currentAvailability()['state']);
    }

    public function test_future_approved_reservation_does_not_make_it_unavailable_now(): void
    {
        $item = Equipment::factory()->create();
        Reservation::factory()->approved()->create()->equipment()->attach($item); // tomorrow

        $this->assertSame('available', $item->currentAvailability()['state']);
        $this->assertCount(1, $item->upcomingReservations()->get());
    }

    public function test_stored_status_wins_over_reservations(): void
    {
        $this->assertSame('maintenance', Equipment::factory()->underMaintenance()->create()->currentAvailability()['state']);
        $this->assertSame('retired', Equipment::factory()->retired()->create()->currentAvailability()['state']);
    }

    // ---------- History, damage, privacy ----------

    public function test_history_shows_conditions_and_damage_reports_are_listed(): void
    {
        $item = Equipment::factory()->create();
        Reservation::factory()->returned()->create(['reference_no' => 'RSV-2026-0100'])
            ->equipment()->attach($item, ['condition_out' => 'good', 'condition_in' => 'damaged']);
        DamageReport::factory()->for($item)->create(['description' => 'Cracked lens', 'severity' => DamageSeverity::Major]);

        $this->actingAs(User::factory()->create())
            ->get(route('equipment.show', $item))
            ->assertSee('RSV-2026-0100')
            ->assertSee('damaged')
            ->assertSee('Cracked lens');
    }

    public function test_only_admin_sees_borrower_contact_details_and_edit_button(): void
    {
        $item = Equipment::factory()->create();
        $borrower = User::factory()->create(['email' => 'borrower@example.com']);
        Reservation::factory()->for($borrower)->approved()->create()->equipment()->attach($item);

        $this->actingAs(User::factory()->create())
            ->get(route('equipment.show', $item))
            ->assertSee($borrower->name)
            ->assertDontSee('borrower@example.com')
            ->assertDontSee(route('admin.equipment.edit', $item));

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('equipment.show', $item))
            ->assertSee('borrower@example.com')
            ->assertSee(route('admin.equipment.edit', $item));
    }

    public function test_deleted_item_page_still_shows_its_history(): void
    {
        $item = Equipment::factory()->create(['asset_tag' => 'PRJ-0009']);
        $item->delete();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/equipment/PRJ-0009')
            ->assertOk()
            ->assertSee('This item was deleted')
            ->assertDontSee(route('admin.equipment.edit', $item));
    }
}
