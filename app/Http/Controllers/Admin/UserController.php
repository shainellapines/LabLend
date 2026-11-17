<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FR-U2: admin lists, views, edits, deactivates and reactivates users, and changes their role.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::filter($request->only('search', 'role', 'active'))
            ->withCount([
                'reservations',
                'reservations as overdue_count' => fn ($q) => $q->overdue(),
            ])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => UserRole::cases()]);
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        return view('admin.users.show', [
            'user' => $user->loadCount(['reservations', 'damageReports', 'reservations as overdue_count' => fn ($q) => $q->overdue()]),
            'reservations' => $user->reservations()->with('equipment', 'room')->latest('start_at')->limit(10)->get(),
        ]);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', ['user' => $user, 'roles' => UserRole::cases()]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->except('role'));
        $user->role = UserRole::from($request->validated('role')); // role is not mass-assignable, so it is set explicitly
        $user->save();

        return redirect()->route('admin.users.show', $user)->with('success', "{$user->name} was updated.");
    }

    /** Deactivated users are logged out on their next click and cannot log in (EnsureUserIsActive). */
    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('toggleActive', $user);

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('success', $user->is_active ? "{$user->name} was reactivated." : "{$user->name} was deactivated and can no longer log in.");
    }
}
