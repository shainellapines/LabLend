<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    <x-page-header title="Welcome, {{ Auth::user()->name }}"
                   description="Reserve lab equipment and rooms, and keep track of what you borrowed." />

    {{-- Placeholder content until the real dashboards are built in step 3.5. --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="My upcoming reservations">
            <x-empty-state title="No reservations yet"
                           description="Your upcoming reservations will appear here." />
        </x-card>

        <x-card title="Status legend">
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
