<x-app-layout>
    <x-slot name="title">Edit Item</x-slot>

    <x-page-header title="Edit {{ $equipment->asset_tag }}" description="{{ $equipment->name }} · Fields marked * are required." />

    <x-card class="max-w-4xl">
        <form method="POST" action="{{ route('admin.equipment.update', $equipment) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('admin.equipment._form')

            <div class="mt-8 flex justify-end gap-3 border-t border-line pt-6">
                <x-link-button :href="route('equipment.show', $equipment)" variant="secondary">Cancel</x-link-button>
                <x-primary-button>Save Changes</x-primary-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
