<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

/**
 * Who may do what with categories. Laravel finds this class automatically because it is
 * named <Model>Policy. Controllers call $this->authorize('update', $category) to use it.
 *
 * Categories are admin-only. The 'role:admin' middleware on /admin routes already blocks others;
 * the policy is a second check that also works for @can in Blade.
 */
class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }
}
