<x-app-layout>
    <x-slot name="title">Edit Reservation</x-slot>

    <x-page-header title="Edit {{ $reservation->reference_no }}" description="You can change a reservation while it is still pending. It is checked for conflicts again." />

    <x-card class="max-w-4xl">
        <form method="POST" action="{{ route('reservations.update', $reservation) }}">
            @csrf
            @method('PUT')

            @include('reservations._form')

            <div class="mt-8 flex justify-end gap-3 border-t border-line pt-6">
                <x-link-button :href="route('reservations.show', $reservation)" variant="secondary">Cancel</x-link-button>
                <x-primary-button>Save Changes</x-primary-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
