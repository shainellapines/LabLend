<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;

/**
 * Everyone who is logged in can browse equipment (FR-E3); only the admin can change it (FR-E1).
 */
class EquipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Equipment $equipment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Equipment $equipment): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Equipment $equipment): bool
    {
        return $user->isAdmin();
    }
}
