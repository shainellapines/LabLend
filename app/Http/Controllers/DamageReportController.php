<?php

namespace App\Http\Controllers;

use App\Enums\DamageSeverity;
use App\Enums\DamageStatus;
use App\Http\Requests\DamageReportRequest;
use App\Models\DamageReport;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Services\DamageReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Damage reports: anyone can file one (FR-D1); borrowers see their own, the admin sees all.
 * Status updates and deletion are in Admin\DamageReportController.
 */
class DamageReportController extends Controller
{
    public function __construct(private DamageReportService $damageReports)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', DamageReport::class);

        $reports = DamageReport::query()
            ->unless($request->user()->isAdmin(), fn ($q) => $q->where('reported_by', $request->user()->id))
            ->filter($request->only('search', 'status', 'severity'))
            ->with('equipment', 'reporter', 'reservation')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('damage-reports.index', [
            'reports' => $reports,
            'statuses' => DamageStatus::cases(),
            'severities' => DamageSeverity::cases(),
        ]);
    }

    /** ?equipment=5&reservation=12 pre-fills the form (used after a "damaged" return, FR-R6, and from item pages). */
    public function create(Request $request): View
    {
        $this->authorize('create', DamageReport::class);

        $reservation = $request->filled('reservation') ? Reservation::with('equipment', 'user')->find($request->integer('reservation')) : null;
        if ($reservation && $request->user()->cannot('view', $reservation)) {
            $reservation = null; // never pre-fill someone else's reservation
        }

        return view('damage-reports.create', [
            'equipment' => Equipment::with('category')->orderBy('asset_tag')->get(),
            'selectedItem' => $request->integer('equipment') ?: null,
            'reservation' => $reservation,
            'myReservations' => $reservation ? collect() : $request->user()->reservations()
                ->whereIn('status', ['released', 'returned'])->latest('start_at')->limit(20)->get(),
            'severities' => DamageSeverity::cases(),
        ]);
    }

    public function store(DamageReportRequest $request): RedirectResponse
    {
        $photoPath = $request->file('photo')?->store('damage-reports', 'public');

        $report = $this->damageReports->create($request->user(), $request->safe()->except('photo'), $photoPath);

        // FR-R6: after a return with several damaged items, go straight to the next item's report.
        if ($report->reservation && $next = $this->damageReports->nextUnreportedDamagedItem($report->reservation)) {
            return redirect()->route('damage-reports.create', ['equipment' => $next->id, 'reservation' => $report->reservation_id])
                ->with('success', "Report saved for {$report->equipment->asset_tag}. Now report {$next->asset_tag}.");
        }

        return redirect()->route('damage-reports.show', $report)->with('success', "Damage report for {$report->equipment->asset_tag} was filed.");
    }

    public function show(DamageReport $damageReport): View
    {
        $this->authorize('view', $damageReport);

        return view('damage-reports.show', [
            'report' => $damageReport->load('equipment.category', 'reporter', 'reservation.user'),
            'statuses' => DamageStatus::cases(),
        ]);
    }
}
