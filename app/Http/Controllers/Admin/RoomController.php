<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoomType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin CRUD for rooms (FR-RM1). Same pattern as CategoryController.
 */
class RoomController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', Room::class);

        return view('admin.rooms.create', [
            'room' => new Room(['is_active' => true, 'type' => RoomType::ComputerLab]),
            'types' => RoomType::cases(),
        ]);
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        $room = Room::create($request->validated());

        return redirect()->route('rooms.show', $room)->with('success', "Room \"{$room->name}\" was added.");
    }

    public function edit(Room $room): View
    {
        $this->authorize('update', $room);

        return view('admin.rooms.edit', ['room' => $room, 'types' => RoomType::cases()]);
    }

    public function update(RoomRequest $request, Room $room): RedirectResponse
    {
        $room->update($request->validated());

        return redirect()->route('rooms.show', $room)->with('success', "Room \"{$room->name}\" was updated.");
    }

    public function destroy(Room $room): RedirectResponse
    {
        $this->authorize('delete', $room);

        // FR-RM2: rooms with pending/approved/released reservations cannot be deleted.
        if ($room->reservations()->active()->exists()) {
            return back()->with('error', "\"{$room->name}\" has active reservations and cannot be deleted. Mark it inactive instead.");
        }

        $room->delete(); // past reservations keep their record; their room_id becomes null (nullOnDelete)

        return redirect()->route('rooms.index')->with('success', "Room \"{$room->name}\" was deleted.");
    }
}
