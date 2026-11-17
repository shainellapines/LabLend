<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EquipmentCondition;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReceiveReservationRequest;
use App\Http\Requests\RejectReservationRequest;
use App\Http\Requests\ReleaseReservationRequest;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin actions on a reservation (FR-R4–R6). Each one is a single call to ReservationService::transition(),
 * which checks that the status change is allowed. index() is the admin "Requests" list.
 */
class ReservationActionController extends Controller
{
    public function __construct(private ReservationService $reservations)
    {
    }

    /**
     * FR-R8: the admin "Requests" list. Defaults to Pending so the custodian sees what needs a decision first.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Reservation::class);

        $filters = $request->only('status', 'search', 'from', 'to') + ['status' => 'pending'];

        $reservations = Reservation::filter($filters)
            ->with('user', 'equipment', 'room') // eager load: no N+1 in the table
            ->orderBy('start_at', in_array($filters['status'], ['pending', 'approved']) ? 'asc' : 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'filters' => $filters,
            'counts' => Reservation::toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')
                ->put('overdue', Reservation::overdue()->count()),
        ]);
    }

    public function approve(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('manage', $reservation);

        $this->reservations->transition($reservation, ReservationStatus::Approved, $request->user());

        return back()->with('success', "{$reservation->reference_no} was approved.");
    }

    public function reject(RejectReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        $this->reservations->transition($reservation, ReservationStatus::Rejected, $request->user(), $request->validated());

        return redirect()->route('reservations.show', $reservation)->with('success', "{$reservation->reference_no} was rejected.");
    }

    public function release(ReleaseReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        $this->reservations->transition($reservation, ReservationStatus::Released, $request->user(), $request->validated());

        return redirect()->route('reservations.show', $reservation)->with('success', "{$reservation->reference_no} was released to {$reservation->user->name}.");
    }

    public function receive(ReceiveReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        $this->reservations->transition($reservation, ReservationStatus::Returned, $request->user(), $request->validated());

        $damaged = collect($request->validated('conditions', []))->filter(fn ($c) => $c === EquipmentCondition::Damaged->value);
        if ($damaged->isNotEmpty()) {
            // FR-R6: step 2.7 changes this to open the pre-filled "Create damage report" form.
            return redirect()->route('reservations.show', $reservation)->with('error', "{$reservation->reference_no} was returned with {$damaged->count()} damaged item(s). Please file a damage report.");
        }

        return redirect()->route('reservations.show', $reservation)->with('success', "{$reservation->reference_no} was returned.");
    }
}
