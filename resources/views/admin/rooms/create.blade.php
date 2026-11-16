<x-app-layout>
    <x-slot name="title">New Room</x-slot>

    <x-page-header title="New Room" description="Fields marked * are required." />

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.rooms.store') }}">
            @csrf

            @include('admin.rooms._form')

            <div class="mt-8 flex justify-end gap-3 border-t border-line pt-6">
                <x-link-button :href="route('rooms.index')" variant="secondary">Cancel</x-link-button>
                <x-primary-button>Add Room</x-primary-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
