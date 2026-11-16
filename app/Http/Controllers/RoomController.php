<?php

namespace App\Http\Controllers;

use App\Enums\RoomType;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Room pages every logged-in user can see. Changes are in Admin\RoomController.
 */
class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Room::class);

        $rooms = Room::filter($request->only('search', 'type'))
            ->withCount('upcomingReservations') // "N bookings in the next 7 days" without a query per room
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('rooms.index', ['rooms' => $rooms, 'types' => RoomType::cases()]);
    }

    /** FR-RM2: room details + reservations for the next 7 days, grouped by day. */
    public function show(Room $room): View
    {
        $this->authorize('view', $room);

        $upcoming = $room->upcomingReservations()->with('user')->get();

        return view('rooms.show', [
            'room' => $room,
            'current' => $room->currentReservation(),
            // ['2026-11-17' => [reservations...], ...] for the 7-day agenda
            'days' => $upcoming->groupBy(fn ($reservation) => $reservation->start_at->toDateString()),
        ]);
    }
}
