<x-app-layout>
    <x-slot name="title">New Category</x-slot>

    <x-page-header title="New Category" description="Fields marked * are required." />

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf

            @include('admin.categories._form')

            <div class="mt-8 flex justify-end gap-3 border-t border-line pt-6">
                <x-link-button :href="route('admin.categories.index')" variant="secondary">Cancel</x-link-button>
                <x-primary-button>Create Category</x-primary-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
