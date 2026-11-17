@use('App\Enums\ReservationStatus', 'S')

<x-app-layout>
    <x-slot name="title">Requests</x-slot>


    <x-page-header title="Requests" description="Approve, hand over, and receive reservations. Pending requests are shown first." />

    {{-- Status chips (quick filters). Keep the search and date range when switching. --}}
    @php
        $chips = [
            'pending' => ['Pending', 'amber'],
            'approved' => ['Approved', 'blue'],
            'released' => ['Borrowed', 'indigo'],
            'overdue' => ['Overdue', 'red'],
            'returned' => ['Returned', 'green'],
            'all' => ['All', 'gray'],
        ];
    @endphp
    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        @foreach ($chips as $key => [$label, $color])
            @php
                $count = $key === 'all' ? $counts->except('overdue')->sum() : ($counts[$key] ?? 0);
                $active = $filters['status'] === $key;
            @endphp
            <a href="{{ route('admin.reservations.index', array_merge(request()->only('search', 'from', 'to'), ['status' => $key])) }}"
               @class([
                   'flex shrink-0 items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition',
                   'border-primary-600 bg-primary-600 text-white' => $active,
                   'border-line bg-white text-ink hover:border-primary-200' => ! $active,
               ])>
                {{ $label }}
                <span @class([
                    'rounded-full px-1.5 text-xs font-semibold',
                    'bg-white/20 text-white' => $active,
                    'bg-red-600 text-white' => ! $active && $key === 'overdue' && $count > 0,
                    'bg-canvas text-muted' => ! $active && ! ($key === 'overdue' && $count > 0),
                ])>{{ $count }}</span>
            </a>
        @endforeach
    </div>

    <x-card :padding="false">
        <x-filter-bar :action="route('admin.reservations.index')" placeholder="Search reference, borrower, item, or room">
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
            <label class="flex items-center gap-2 text-sm text-muted">
                From <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </label>
            <label class="flex items-center gap-2 text-sm text-muted">
                To <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </label>
        </x-filter-bar>

        @if ($reservations->isEmpty())
            <x-empty-state title="No reservations here"
                           :description="$filters['status'] === 'pending' && ! request()->hasAny(['search', 'from', 'to']) ? 'All caught up: there are no requests waiting for approval.' : 'Try another status, search, or date range.'" />
        @else
            <x-data-table>
                <x-slot name="head">
                    <th>Reference</th>
                    <th>Borrower</th>
                    <th>Schedule</th>
                    <th class="hidden lg:table-cell">Items · Room</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </x-slot>
                @foreach ($reservations as $reservation)
                    <tr>
                        <td>
                            <a href="{{ route('reservations.show', $reservation) }}" class="font-mono text-sm font-semibold text-primary-700 hover:text-primary-800">{{ $reservation->reference_no }}</a>
                            <p class="max-w-[10rem] truncate text-xs text-muted">{{ $reservation->purpose }}</p>
                        </td>
                        <td>
                            <p class="whitespace-nowrap font-medium text-ink">{{ $reservation->user->name }}</p>
                            <p class="text-xs text-muted">{{ $reservation->user->role->label() }} · {{ $reservation->user->id_number }}</p>
                        </td>
                        <td class="whitespace-nowrap">
                            <p class="font-medium text-ink">{{ $reservation->start_at->format('M d, Y') }}</p>
                            <p class="text-xs text-muted">{{ $reservation->start_at->format('g:i A') }} – {{ $reservation->end_at->format($reservation->end_at->isSameDay($reservation->start_at) ? 'g:i A' : 'M d, g:i A') }}</p>
                        </td>
                        <td class="hidden max-w-xs lg:table-cell">
                            <p class="truncate text-muted">{{ $reservation->summary() }}</p>
                            @if ($reservation->hasUnavailableItems())
                                {{-- FR-D3: an item in this request went under maintenance / was retired after it was made --}}
                                <p class="mt-1 inline-flex items-center gap-1 rounded bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-800">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                    Item no longer available
                                </p>
                            @endif
                        </td>
                        <td>
                            @if ($reservation->isOverdue())
                                <x-status-badge label="Overdue" color="red-solid" />
                            @else
                                <x-status-badge :status="$reservation->status" />
                            @endif
                        </td>
                        <td class="whitespace-nowrap text-right">
                            {{-- Quick actions. Approve is one click; the others need input, so they open the modal on the reservation page. --}}
                            <div class="flex items-center justify-end gap-2">
                                @if ($reservation->status->canTransitionTo(S::Approved))
                                    <form method="POST" action="{{ route('admin.reservations.approve', $reservation) }}">
                                        @csrf @method('PATCH')
                                        <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-500">Approve</button>
                                    </form>
                                    <a href="{{ route('reservations.show', [$reservation, 'action' => 'reject']) }}" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Reject</a>
                                @elseif ($reservation->status->canTransitionTo(S::Released))
                                    <a href="{{ route('reservations.show', [$reservation, 'action' => 'release']) }}" class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-500">Release</a>
                                @elseif ($reservation->status->canTransitionTo(S::Returned))
                                    <a href="{{ route('reservations.show', [$reservation, 'action' => 'receive']) }}" class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-500">Receive</a>
                                @else
                                    <a href="{{ route('reservations.show', $reservation) }}" class="text-sm font-medium text-primary-600 hover:text-primary-800">View</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>

            <div class="flex flex-col gap-3 border-t border-line px-4 py-3 text-sm text-muted sm:flex-row sm:items-center sm:justify-between">
                <span>Showing {{ $reservations->firstItem() }}–{{ $reservations->lastItem() }} of {{ $reservations->total() }}</span>
                @if ($reservations->hasPages())
                    {{ $reservations->links() }}
                @endif
            </div>
        @endif
    </x-card>
</x-app-layout>
