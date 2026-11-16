<x-app-layout>
    <x-slot name="title">Edit Category</x-slot>

    <x-page-header title="Edit {{ $category->name }}" description="Fields marked * are required." />

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.categories.update', $category) }}">
            @csrf
            @method('PUT')

            @include('admin.categories._form')

            <div class="mt-8 flex justify-end gap-3 border-t border-line pt-6">
                <x-link-button :href="route('admin.categories.index')" variant="secondary">Cancel</x-link-button>
                <x-primary-button>Save Changes</x-primary-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
