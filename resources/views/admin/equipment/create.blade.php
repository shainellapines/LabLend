<x-app-layout>
    <x-slot name="title">New Item</x-slot>

    <x-page-header title="New Item" description="Fields marked * are required." />

    <x-card class="max-w-4xl">
        {{-- enctype="multipart/form-data" is required for file uploads --}}
        <form method="POST" action="{{ route('admin.equipment.store') }}" enctype="multipart/form-data">
            @csrf

            @include('admin.equipment._form')

            <div class="mt-8 flex justify-end gap-3 border-t border-line pt-6">
                <x-link-button :href="route('equipment.index')" variant="secondary">Cancel</x-link-button>
                <x-primary-button>Add Item</x-primary-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
