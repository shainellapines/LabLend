<x-app-layout>
    <x-slot name="title">{{ $user->name }}</x-slot>

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-ink">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                Users
            </a>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-semibold text-ink">{{ $user->name }}</h2>
                <x-status-badge :status="$user->role" />
                <x-status-badge :label="$user->is_active ? 'Active' : 'Inactive'" :color="$user->is_active ? 'green' : 'gray'" />
            </div>
            <p class="mt-1 text-sm text-muted">{{ $user->email }} · {{ $user->id_number }}</p>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @can('toggleActive', $user)
                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                    @csrf @method('PATCH')
                    <button @class([
                        'rounded-lg border px-4 py-2 text-sm font-semibold',
                        'border-red-200 text-red-600 hover:bg-red-50' => $user->is_active,
                        'border-green-200 text-green-700 hover:bg-green-50' => ! $user->is_active,
                    ])>{{ $user->is_active ? 'Deactivate' : 'Reactivate' }}</button>
                </form>
            @endcan
            <x-link-button :href="route('admin.users.edit', $user)" variant="secondary">Edit</x-link-button>
        </div>
    </div>

    @unless ($user->is_active)
        <div class="mb-6 rounded-lg border border-line bg-slate-100 px-4 py-3 text-sm text-ink">This account is deactivated: the user cannot log in.</div>
    @endunless

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Reservations" :value="$user->reservations_count" tone="azure"
            icon="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
        <x-stat-card label="Overdue now" :value="$user->overdue_count" :tone="$user->overdue_count ? 'red' : 'green'"
            icon="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        <x-stat-card label="Damage reports filed" :value="$user->damage_reports_count" tone="amber"
            icon="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Profile">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-muted">Department</dt><dd class="font-medium text-ink">{{ $user->department ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Phone</dt><dd class="font-medium text-ink">{{ $user->phone ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Joined</dt><dd class="font-medium text-ink">{{ $user->created_at->format('M d, Y') }}</dd></div>
            </dl>
        </x-card>

        <x-card class="lg:col-span-2" title="Recent reservations" subtitle="Latest 10" :padding="false">
            @if ($reservations->isEmpty())
                <x-empty-state title="No reservations yet" />
            @else
                <x-data-table>
                    <x-slot name="head">
                        <th>Reference</th>
                        <th>Date</th>
                        <th class="hidden sm:table-cell">Items · Room</th>
                        <th>Status</th>
                    </x-slot>
                    @foreach ($reservations as $reservation)
                        <tr>
                            <td><a href="{{ route('reservations.show', $reservation) }}" class="font-mono text-sm font-semibold text-primary-700 hover:text-primary-800">{{ $reservation->reference_no }}</a></td>
                            <td class="whitespace-nowrap text-muted">{{ $reservation->start_at->format('M d, Y') }}</td>
                            <td class="hidden max-w-xs truncate text-muted sm:table-cell">{{ $reservation->summary() }}</td>
                            <td>
                                @if ($reservation->isOverdue())
                                    <x-status-badge label="Overdue" color="red-solid" />
                                @else
                                    <x-status-badge :status="$reservation->status" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>
    </div>
</x-app-layout>
