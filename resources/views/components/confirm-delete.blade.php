{{--
    A "Delete" button that opens a confirmation modal; the DELETE request is only sent after "Yes, delete".
    Usage:
    <x-confirm-delete :action="route('admin.categories.destroy', $category)"
                      message="Delete the category &quot;{{ $category->name }}&quot;?" />
--}}
@props([
    'action',
    'message' => 'Are you sure you want to delete this record?',
    'title' => 'Confirm delete',
    'button' => 'Delete',
])

@php
    // Each modal needs a unique name so several delete buttons can exist on one page (e.g. in a table).
    $modalName = 'confirm-delete-' . md5($action);
@endphp

<button type="button"
        x-data
        @click="$dispatch('open-modal', '{{ $modalName }}')"
        {{ $attributes->merge(['class' => 'text-sm font-medium text-red-600 hover:text-red-700']) }}>
    {{ $button }}
</button>

<x-modal :name="$modalName" maxWidth="md" focusable>
    <form method="POST" action="{{ $action }}" class="p-6">
        @csrf
        @method('DELETE')

        <h2 class="text-lg font-semibold text-ink">{{ $title }}</h2>
        <p class="mt-2 text-sm text-muted">{{ $message }}</p>
        <p class="mt-1 text-sm text-muted">This action cannot be undone.</p>

        <div class="mt-6 flex justify-end gap-3">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancel</x-secondary-button>
            <x-danger-button>Yes, delete</x-danger-button>
        </div>
    </form>
</x-modal>
