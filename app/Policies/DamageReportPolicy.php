<?php

namespace App\Policies;

use App\Models\DamageReport;
use App\Models\User;

/**
 * Anyone can report damage (FR-D1) and see their own reports; only the admin manages them (FR-D2).
 */
class DamageReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // the list itself only shows the user's own reports unless they are admin
    }

    public function view(User $user, DamageReport $report): bool
    {
        return $user->isAdmin() || (int) $report->reported_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DamageReport $report): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, DamageReport $report): bool
    {
        return $user->isAdmin();
    }
}
