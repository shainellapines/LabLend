<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    @include('dashboard._banner', ['subtitle' => 'Here is what is happening in the lab today: requests to review, items out, and returns due.'])

    {{-- ===== KPI row (operational, FR-H1). Each tile opens the list behind the number. ===== --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card label="Pending requests" :value="$stats['pending']" tone="amber" hint="Waiting for your approval"
            :href="route('admin.reservations.index', ['status' => 'pending'])"
            icon="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        <x-stat-card label="Items borrowed" :value="$stats['borrowedItems']" tone="azure" hint="Released and not yet returned"
            :href="route('admin.reservations.index', ['status' => 'released'])"
            icon="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        <x-stat-card label="Overdue" :value="$stats['overdue']" :tone="$stats['overdue'] ? 'red' : 'green'" hint="Past their return time"
            :href="route('admin.reservations.index', ['status' => 'overdue'])"
            icon="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        <x-stat-card label="Open damage reports" :value="$stats['openDamage']" tone="red" hint="Reported, not yet resolved"
            :href="route('damage-reports.index', ['status' => 'open'])"
            icon="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        <x-stat-card label="Under maintenance" :value="$stats['underMaintenance']" tone="gray" hint="Not available for booking"
            :href="route('equipment.index', ['status' => 'under_maintenance'])"
            icon="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ===== Analytical: requests per day (line) ===== --}}
        <x-card class="lg:col-span-2" title="Reservation requests per day" subtitle="Last 14 days">
            @if (array_sum($perDayChart['values']) === 0)
                <x-empty-state title="No requests in the last 14 days" />
            @else
                <x-chart type="line" label="Requests" :labels="$perDayChart['labels']" :values="$perDayChart['values']" class="h-64" />
            @endif
        </x-card>

        {{-- ===== Operational: today's hand-overs and returns ===== --}}
        <x-card title="Today's schedule" subtitle="Hand-overs and returns due today" :padding="false">
            @php
                $groups = [
                    ['To release', $today['releases'], 'start_at', 'release'],
                    ['Due back', $today['returns'], 'end_at', 'receive'],
                ];
            @endphp
            @if ($today['releases']->isEmpty() && $today['returns']->isEmpty())
                <x-empty-state title="Nothing scheduled today" description="No hand-overs or returns are due." />
            @else
                <div class="divide-y divide-line">
                    @foreach ($groups as [$heading, $list, $timeField, $action])
                        @continue($list->isEmpty())
                        <div class="px-6 py-4">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-muted">{{ $heading }} ({{ $list->count() }})</p>
                            <ul class="space-y-2">
                                @foreach ($list as $reservation)
                                    <li>
                                        <a href="{{ route('reservations.show', [$reservation, 'action' => $action]) }}"
                                           class="flex items-center justify-between gap-3 rounded-lg border border-line px-3 py-2 hover:border-primary-200">
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-medium text-ink">{{ $reservation->user->name }}</span>
                                                <span class="block truncate text-xs text-muted">{{ $reservation->summary() }}</span>
                                            </span>
                                            <span class="shrink-0 text-sm font-semibold text-primary-700">{{ $reservation->{$timeField}->format('g:i A') }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        {{-- ===== Analytical: most-borrowed items (bar) ===== --}}
        <x-card class="lg:col-span-2" title="Most-borrowed items" subtitle="Times handed over, last 30 days">
            @if (empty($topChart['values']))
                <x-empty-state title="No borrowing in the last 30 days" />
            @else
                <x-chart type="bar" label="Times borrowed" :labels="$topChart['labels']" :values="$topChart['values']" class="h-56" />
            @endif
        </x-card>

        <x-card title="Status legend" subtitle="Colors used across LabLend">
            <div class="flex flex-wrap gap-2">
                @foreach (App\Enums\ReservationStatus::cases() as $status)
                    <x-status-badge :status="$status" />
                @endforeach
                <x-status-badge label="Overdue" color="red-solid" />
            </div>
        </x-card>
    </div>
</x-app-layout>
