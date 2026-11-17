<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Http\Requests\ReservationRequest;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\ReservationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Borrower side of reservations: create, view, edit (FR-R1, FR-R9).
 * All business rules are in ReservationService; this controller only connects the pieces.
 */
class ReservationController extends Controller
{
    public function __construct(private ReservationService $reservations)
    {
    }

    /** FR-R8: "My Reservations" with Upcoming / Active / History tabs (?tab=...). */
    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['upcoming', 'active', 'history']) ? $request->query('tab') : 'upcoming';
        $mine = fn () => $request->user()->reservations();

        $reservations = $mine()->forTab($tab)
            ->with('equipment', 'room') // eager load for the "items · room" summary
            ->orderBy('start_at', $tab === 'history' ? 'desc' : 'asc')
            ->paginate(15)
            ->withQueryString();

        $counts = collect(['upcoming', 'active', 'history'])->mapWithKeys(fn ($t) => [$t => $mine()->forTab($t)->count()]);

        return view('reservations.index', compact('reservations', 'tab', 'counts'));
    }

    /** ?equipment=5 or ?room=2 pre-selects an item/room (from the "Reserve" buttons on their pages). */
    public function create(Request $request): View
    {
        $this->authorize('create', Reservation::class);

        $start = now()->addDay()->setTime(13, 0); // sensible default: tomorrow 1:00–3:00 PM
        $reservation = new Reservation([
            'start_at' => $start,
            'end_at' => $start->copy()->addHours(2),
            'room_id' => $request->integer('room') ?: null,
        ]);

        return view('reservations.create', $this->formData($reservation) + [
            'selectedItems' => array_filter([$request->integer('equipment')]),
        ]);
    }

    public function store(ReservationRequest $request): RedirectResponse
    {
        $reservation = $this->reservations->create($request->user(), $request->validated());

        return redirect()->route('reservations.show', $reservation)
            ->with('success', "Reservation {$reservation->reference_no} was submitted and is waiting for approval.");
    }

    public function show(Reservation $reservation): View
    {
        $this->authorize('view', $reservation);

        $reservation->load('user', 'approver', 'room', 'equipment.category', 'damageReports');

        return view('reservations.show', compact('reservation'));
    }

    public function edit(Reservation $reservation): View
    {
        $this->authorize('update', $reservation);

        return view('reservations.edit', $this->formData($reservation) + [
            'selectedItems' => $reservation->equipment()->pluck('equipment.id')->all(),
        ]);
    }

    public function update(ReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        $this->reservations->update($reservation, $request->validated());

        return redirect()->route('reservations.show', $reservation)
            ->with('success', "Reservation {$reservation->reference_no} was updated.");
    }

    /** FR-R4: borrowers cancel their own pending/approved reservations (the service refuses other statuses). */
    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('cancel', $reservation);

        $this->reservations->transition($reservation, ReservationStatus::Cancelled, $request->user());

        return back()->with('success', "{$reservation->reference_no} was cancelled.");
    }

    /** FR-R10: printable reservation slip (PDF) with signature lines. Anyone who may view the reservation may print it. */
    public function slip(Reservation $reservation): Response
    {
        $this->authorize('view', $reservation);

        try {
            return Pdf::loadView('pdf.reservation-slip', [
                'reservation' => $reservation->load('user', 'approver', 'room', 'equipment.category'),
            ])->setPaper('a4')->stream("{$reservation->reference_no}.pdf");
        } catch (\Throwable $e) {
            report($e); // section 9.5
            return back()->with('error', 'The slip could not be created. Please try again.');
        }
    }

    /** Items grouped by category, and rooms, for the pickers. Only reservable ones are offered. */
    private function formData(Reservation $reservation): array
    {
        return [
            'reservation' => $reservation,
            'itemsByCategory' => Equipment::reservable()->with('category')->orderBy('asset_tag')->get()->groupBy('category.name'),
            'rooms' => Room::reservable()->orderBy('name')->get(),
            'maxHours' => config('lablend.max_hours.' . auth()->user()->role->value),
        ];
    }
}
