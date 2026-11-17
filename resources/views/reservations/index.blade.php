<x-app-layout>
    <x-slot name="title">My Reservations</x-slot>

    <x-page-header title="My Reservations" description="Everything you have requested, borrowed, and returned.">
        <x-slot name="actions">
            <x-link-button :href="route('reservations.create')">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                New Reservation
            </x-link-button>
        </x-slot>
    </x-page-header>

    <x-card :padding="false">
        {{-- Tabs --}}
        <nav class="flex gap-1 overflow-x-auto border-b border-line px-4" aria-label="Tabs">
            @foreach (['upcoming' => 'Upcoming', 'active' => 'Borrowed now', 'history' => 'History'] as $key => $label)
                <a href="{{ route('reservations.index', ['tab' => $key]) }}"
                   @class([
                       '-mb-px flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-3 text-sm font-medium',
                       'border-primary-600 text-primary-700' => $tab === $key,
                       'border-transparent text-muted hover:text-ink' => $tab !== $key,
                   ])>
                    {{ $label }}
                    <span @class(['rounded-full px-2 py-0.5 text-xs', 'bg-primary-50 text-primary-700' => $tab === $key, 'bg-canvas text-muted' => $tab !== $key])>{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </nav>

        @if ($reservations->isEmpty())
            @php
                $empty = [
                    'upcoming' => ['No upcoming reservations', 'Reserve equipment or a room for a class, defense, or event.'],
                    'active' => ['Nothing borrowed right now', 'Items handed over to you will appear here until you return them.'],
                    'history' => ['No history yet', 'Returned, cancelled, and rejected reservations will be listed here.'],
                ][$tab];
            @endphp
            <x-empty-state :title="$empty[0]" :description="$empty[1]">
                @if ($tab === 'upcoming')
                    <x-link-button :href="route('reservations.create')">New Reservation</x-link-button>
                @endif
            </x-empty-state>
        @else
            <x-data-table>
                <x-slot name="head">
                    <th>Reference</th>
                    <th>Schedule</th>
                    <th class="hidden md:table-cell">Items · Room</th>
                    <th>Status</th>
                    <th class="text-right"><span class="sr-only">Open</span></th>
                </x-slot>
                @foreach ($reservations as $reservation)
                    <tr>
                        <td>
                            <a href="{{ route('reservations.show', $reservation) }}" class="font-mono text-sm font-semibold text-primary-700 hover:text-primary-800">{{ $reservation->reference_no }}</a>
                            <p class="max-w-[12rem] truncate text-xs text-muted">{{ $reservation->purpose }}</p>
                        </td>
                        <td class="whitespace-nowrap">
                            <p class="font-medium text-ink">{{ $reservation->start_at->format('M d, Y') }}</p>
                            <p class="text-xs text-muted">{{ $reservation->start_at->format('g:i A') }} – {{ $reservation->end_at->format($reservation->end_at->isSameDay($reservation->start_at) ? 'g:i A' : 'M d, g:i A') }}</p>
                        </td>
                        <td class="hidden max-w-xs truncate text-muted md:table-cell">{{ $reservation->summary() }}</td>
                        <td>
                            @if ($reservation->isOverdue())
                                <x-status-badge label="Overdue" color="red-solid" />
                            @else
                                <x-status-badge :status="$reservation->status" />
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('reservations.show', $reservation) }}" class="text-sm font-medium text-primary-600 hover:text-primary-800">View</a>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>

            @if ($reservations->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $reservations->links() }}</div>
            @endif
        @endif
    </x-card>
</x-app-layout>
