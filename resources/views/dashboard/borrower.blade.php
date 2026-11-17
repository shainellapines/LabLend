<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    @include('dashboard._banner', ['subtitle' => 'Reserve lab equipment and rooms, and keep track of everything you borrowed.'])

    {{-- FR-H2: overdue warning first, because overdue blocks new reservations --}}
    @if ($overdue > 0)
        <div class="mb-6 flex gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
            <p><strong>You have {{ $overdue }} overdue {{ Str::plural('reservation', $overdue) }}.</strong> Please return the items to the lab custodian. You cannot make new reservations until they are returned.</p>
        </div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Upcoming reservations" :value="$upcomingCount" tone="azure" hint="Pending or approved"
            :href="route('reservations.index')"
            icon="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
        <x-stat-card label="Items with me" :value="$itemsWithMe" tone="green" hint="Released to you right now"
            :href="route('reservations.index', ['tab' => 'active'])"
            icon="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        <x-stat-card label="Overdue" :value="$overdue" :tone="$overdue ? 'red' : 'green'" hint="Overdue items block new reservations"
            :href="route('reservations.index', ['tab' => 'active'])"
            icon="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="My upcoming reservations" subtitle="Next 5" :padding="false">
            <x-slot name="actions">
                <a href="{{ route('reservations.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-800">View all</a>
            </x-slot>
            @if ($upcoming->isEmpty())
                <x-empty-state title="No upcoming reservations" description="Reserve equipment or a room for your next class or event.">
                    <x-link-button :href="route('reservations.create')">New Reservation</x-link-button>
                </x-empty-state>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($upcoming as $reservation)
                        <li>
                            <a href="{{ route('reservations.show', $reservation) }}" class="flex items-center gap-4 px-6 py-3 hover:bg-canvas">
                                <div class="w-12 shrink-0 text-center">
                                    <p class="text-xs font-semibold uppercase text-muted">{{ $reservation->start_at->format('M') }}</p>
                                    <p class="text-xl font-semibold leading-tight text-ink">{{ $reservation->start_at->format('j') }}</p>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-ink">{{ $reservation->purpose }}</p>
                                    <p class="truncate text-xs text-muted">{{ $reservation->start_at->format('g:i A') }} – {{ $reservation->end_at->format('g:i A') }} · {{ $reservation->summary() }}</p>
                                </div>
                                <x-status-badge :status="$reservation->status" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card title="Items with me" subtitle="Return them on time to keep borrowing" :padding="false">
            @if ($active->isEmpty())
                <x-empty-state title="Nothing borrowed" description="All clear for now." />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($active as $reservation)
                        <li class="px-6 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <a href="{{ route('reservations.show', $reservation) }}" class="font-mono text-sm font-semibold text-primary-700 hover:text-primary-800">{{ $reservation->reference_no }}</a>
                                @if ($reservation->isOverdue())
                                    <x-status-badge label="Overdue" color="red-solid" />
                                @else
                                    <span class="text-xs text-muted">Due {{ $reservation->end_at->format('M d, g:i A') }}</span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-muted">{{ $reservation->summary() }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-app-layout>
