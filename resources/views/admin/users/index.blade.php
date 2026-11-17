<x-app-layout>
    <x-slot name="title">Users</x-slot>

    <x-page-header title="Users" description="Change roles and deactivate accounts. New registrations start as students." />

    <x-card :padding="false">
        <x-filter-bar :action="route('admin.users.index')" placeholder="Search name, email, or ID number">
            <x-select name="role" aria-label="Role" onchange="this.form.submit()">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </x-select>
            <x-select name="active" aria-label="Account status" onchange="this.form.submit()">
                <option value="">Active and inactive</option>
                <option value="1" @selected(request('active') === '1')>Active only</option>
                <option value="0" @selected(request('active') === '0')>Inactive only</option>
            </x-select>
        </x-filter-bar>

        @if ($users->isEmpty())
            <x-empty-state title="No matching users" description="Try another search or clear the filters." />
        @else
            <x-data-table>
                <x-slot name="head">
                    <th>User</th>
                    <th class="hidden md:table-cell">ID number</th>
                    <th>Role</th>
                    <th class="hidden lg:table-cell">Reservations</th>
                    <th>Account</th>
                    <th class="text-right">Actions</th>
                </x-slot>
                @foreach ($users as $user)
                    <tr @class(['opacity-60' => ! $user->is_active])>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                <div class="min-w-0">
                                    <a href="{{ route('admin.users.show', $user) }}" class="block truncate font-medium text-ink hover:text-primary-700">
                                        {{ $user->name }}@if ($user->is(Auth::user())) <span class="text-xs font-normal text-muted">(you)</span>@endif
                                    </a>
                                    <p class="truncate text-xs text-muted">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="hidden font-mono text-xs text-muted md:table-cell">{{ $user->id_number }}<br><span class="font-sans">{{ $user->department ?: '—' }}</span></td>
                        <td><x-status-badge :status="$user->role" /></td>
                        <td class="hidden lg:table-cell">
                            <span class="text-ink">{{ $user->reservations_count }}</span>
                            @if ($user->overdue_count > 0)
                                <x-status-badge :label="$user->overdue_count . ' overdue'" color="red-solid" class="ml-1" />
                            @endif
                        </td>
                        <td><x-status-badge :label="$user->is_active ? 'Active' : 'Inactive'" :color="$user->is_active ? 'green' : 'gray'" /></td>
                        <td class="whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-4">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-sm font-medium text-primary-600 hover:text-primary-800">Edit</a>
                                @can('toggleActive', $user)
                                    <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                                        @csrf @method('PATCH')
                                        <button @class(['text-sm font-medium', 'text-red-600 hover:text-red-700' => $user->is_active, 'text-green-700 hover:text-green-800' => ! $user->is_active])>
                                            {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>

            <div class="flex flex-col gap-3 border-t border-line px-4 py-3 text-sm text-muted sm:flex-row sm:items-center sm:justify-between">
                <span>Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }} users</span>
                @if ($users->hasPages())
                    {{ $users->links() }}
                @endif
            </div>
        @endif
    </x-card>
</x-app-layout>
