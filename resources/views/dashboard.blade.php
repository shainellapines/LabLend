<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $isAdmin = Auth::user()->isAdmin();
    @endphp

    {{-- ===== Welcome banner (branding gradient) ===== --}}
    <section class="relative mb-6 overflow-hidden rounded-xl bg-brand px-6 py-7 text-white shadow-card sm:px-8">
        {{-- Decorative rings --}}
        <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full border border-white/10"></div>
        <div class="pointer-events-none absolute -right-4 -top-10 h-40 w-40 rounded-full border border-white/10"></div>

        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-primary-200">{{ now()->format('l, F j, Y') }}</p>
                <h2 class="mt-1 text-2xl font-semibold sm:text-3xl">{{ $greeting }}, {{ Str::before(Auth::user()->name, ' ') }}</h2>
                <p class="mt-2 max-w-xl text-sm text-white/70">
                    @if ($isAdmin)
                        Here is what is happening in the lab today: requests to review, items out, and returns due.
                    @else
                        Reserve lab equipment and rooms, and keep track of everything you borrowed.
                    @endif
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap gap-2">
                @if (Route::has('reservations.create'))
                    <a href="{{ route('reservations.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-primary-800 shadow-sm hover:bg-primary-50">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        New Reservation
                    </a>
                @endif
                @if (Route::has('equipment.index'))
                    <a href="{{ route('equipment.index') }}" class="inline-flex items-center rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">
                        Browse equipment
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- ===== KPI row (operational) =====
         Placeholder values: no reservations exist yet. Real counts come from ReportService in step 3.5. --}}
    @if ($isAdmin)
        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <x-stat-card label="Pending requests" :value="0" tone="amber" hint="Waiting for your approval"
                icon="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            <x-stat-card label="Items borrowed" :value="0" tone="azure" hint="Released and not yet returned"
                icon="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            <x-stat-card label="Overdue" :value="0" tone="red" hint="Past their return time"
                icon="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            <x-stat-card label="Open damage reports" :value="0" tone="red" hint="Reported, not yet resolved"
                icon="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            <x-stat-card label="Under maintenance" :value="0" tone="gray" hint="Not available for booking"
                icon="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
        </div>
    @else
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <x-stat-card label="Upcoming reservations" :value="0" tone="azure" hint="Pending or approved"
                icon="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
            <x-stat-card label="Items with me" :value="0" tone="green" hint="Released to you right now"
                icon="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            <x-stat-card label="Overdue" :value="0" tone="red" hint="Overdue items block new reservations"
                icon="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        </div>
    @endif

    {{-- ===== Analytical (left) + operational (right) ===== --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2"
                :title="$isAdmin ? 'Reservations per day' : 'My upcoming reservations'"
                :subtitle="$isAdmin ? 'Last 14 days' : 'Next 7 days'">
            @if ($isAdmin)
                <x-empty-state title="No reservation data yet"
                               description="This chart fills in automatically once reservations are being made." />
            @else
                <x-empty-state title="No reservations yet"
                               description="Your upcoming reservations will appear here." />
            @endif
        </x-card>

        <x-card :title="$isAdmin ? 'Today\'s schedule' : 'Items with me'"
                :subtitle="$isAdmin ? 'Releases and returns due today' : 'Return them on time to keep borrowing'">
            <x-empty-state :title="$isAdmin ? 'Nothing scheduled today' : 'Nothing borrowed'"
                           description="All clear for now." />
        </x-card>

        @if ($isAdmin)
            <x-card class="lg:col-span-2" title="Most-borrowed items" subtitle="Top 5, last 30 days">
                <x-empty-state title="No borrowing history yet"
                               description="The most-used equipment will be ranked here." />
            </x-card>
        @endif

        <x-card title="Status legend" subtitle="Colors used across LabLend" @class(['lg:col-span-3' => ! $isAdmin])>
            <div class="flex flex-wrap gap-2">
                <x-status-badge label="Pending" color="amber" />
                <x-status-badge label="Approved" color="blue" />
                <x-status-badge label="Released" color="indigo" />
                <x-status-badge label="Returned" color="green" />
                <x-status-badge label="Rejected" color="red" />
                <x-status-badge label="Cancelled" color="gray" />
                <x-status-badge label="Overdue" color="red-solid" />
            </div>
        </x-card>
    </div>
</x-app-layout>
