<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DamageReport;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-H3 (reports page + PDF/CSV export) and FR-R10 (reservation slip PDF).
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function borrow(Equipment $item, int $daysAgo): void
    {
        Reservation::factory()->returned()->at(now()->subDays($daysAgo), now()->subDays($daysAgo)->addHour())->create()->equipment()->attach($item);
    }

    public function test_reports_page_shows_usage_overdue_and_damage(): void
    {
        $projectors = Category::factory()->create(['name' => 'Projectors']);
        $item = Equipment::factory()->for($projectors)->create(['asset_tag' => 'PRJ-0001']);
        $this->borrow($item, 2);
        $this->borrow($item, 5);
        Reservation::factory()->overdue()->create(['reference_no' => 'RSV-2026-0077']);
        DamageReport::factory()->create(['severity' => 'major']);

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('PRJ-0001')
            ->assertSee('Projectors')
            ->assertSee('RSV-2026-0077')
            ->assertSee('Damage summary');
    }

    public function test_date_range_limits_usage(): void
    {
        $item = Equipment::factory()->create(['asset_tag' => 'PRJ-0001']);
        $this->borrow($item, 2);
        $this->borrow($item, 40);

        $csv = $this->actingAs($this->admin)->get(route('admin.reports.export', [
            'type' => 'usage', 'format' => 'csv',
            'from' => today()->subDays(10)->toDateString(), 'to' => today()->toDateString(),
        ]))->streamedContent();

        $this->assertStringContainsString('PRJ-0001', $csv);
        $this->assertMatchesRegularExpression('/PRJ-0001,.*,1\s*$/m', $csv); // only the borrow inside the range counts
    }

    public function test_usage_csv_export(): void
    {
        $item = Equipment::factory()->create(['asset_tag' => 'LAP-0002', 'name' => 'Lenovo ThinkPad']);
        $this->borrow($item, 3);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.export', ['type' => 'usage', 'format' => 'csv']));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('lablend-usage-report-', $response->headers->get('content-disposition'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('"Asset tag",Item,Category,"Times borrowed"', $csv);
        $this->assertStringContainsString('LAP-0002,"Lenovo ThinkPad"', $csv);
    }

    public function test_overdue_and_damage_pdf_exports(): void
    {
        Reservation::factory()->overdue()->create();
        DamageReport::factory()->create();

        foreach (['overdue', 'damage', 'usage'] as $type) {
            $response = $this->actingAs($this->admin)->get(route('admin.reports.export', ['type' => $type, 'format' => 'pdf']));

            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }
    }

    public function test_unknown_report_type_and_bad_dates_are_rejected(): void
    {
        $this->actingAs($this->admin)->get('/admin/reports/salaries/export?format=csv')->assertNotFound();

        $this->actingAs($this->admin)
            ->get(route('admin.reports.index', ['from' => '2026-11-20', 'to' => '2026-11-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_non_admins_cannot_see_reports(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.reports.export', ['type' => 'usage', 'format' => 'csv']))->assertForbidden();
    }

    // ================= FR-R10: reservation slip =================

    public function test_borrower_and_admin_can_print_the_slip_others_cannot(): void
    {
        $reservation = Reservation::factory()->approved()->create();
        $reservation->equipment()->attach(Equipment::factory()->create());

        foreach ([$reservation->user, $this->admin] as $user) {
            $response = $this->actingAs($user)->get(route('reservations.slip', $reservation));
            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }

        $this->actingAs(User::factory()->create())->get(route('reservations.slip', $reservation))->assertForbidden();
    }
}
