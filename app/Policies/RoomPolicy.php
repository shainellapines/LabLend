<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

/**
 * Everyone who is logged in can browse rooms; only the admin can change them (FR-RM1).
 */
class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Room $room): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Room $room): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->isAdmin();
    }
}
