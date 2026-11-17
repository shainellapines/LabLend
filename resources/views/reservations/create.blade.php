<x-app-layout>
    <x-slot name="title">New Reservation</x-slot>

    <x-page-header title="New Reservation" description="Your request is checked for conflicts and then sent to the lab custodian for approval." />

    <x-card class="max-w-4xl">
        <form method="POST" action="{{ route('reservations.store') }}">
            @csrf

            @include('reservations._form')

            <div class="mt-8 flex justify-end gap-3 border-t border-line pt-6">
                <x-link-button :href="url()->previous() === url()->current() ? route('dashboard') : url()->previous()" variant="secondary">Cancel</x-link-button>
                <x-primary-button>Submit Request</x-primary-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
