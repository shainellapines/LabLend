<x-app-layout>
    <x-slot name="title">Edit Room</x-slot>

    <x-page-header title="Edit {{ $room->name }}" description="Fields marked * are required." />

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.rooms.update', $room) }}">
            @csrf
            @method('PUT')

            @include('admin.rooms._form')

            <div class="mt-8 flex justify-end gap-3 border-t border-line pt-6">
                <x-link-button :href="route('rooms.show', $room)" variant="secondary">Cancel</x-link-button>
                <x-primary-button>Save Changes</x-primary-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
