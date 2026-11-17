<?php

namespace App\Policies;

use App\Models\User;

/**
 * FR-U2: only the admin manages users, and an admin can never lock themselves out.
 * ($user = the logged-in admin, $model = the account being managed.)
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /** Deactivate / reactivate. Not allowed on your own account. */
    public function toggleActive(User $user, User $model): bool
    {
        return $user->isAdmin() && ! $user->is($model);
    }
}
