{{--
    Usage:
    <x-empty-state title="No equipment yet" description="Add the first item to get started.">
        <a href="..." class="...">+ New Item</a>      (optional call-to-action)
    </x-empty-state>
--}}
@props(['title', 'description' => null])

<div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <svg class="h-12 w-12 text-line" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
    </svg>
    <h3 class="mt-4 text-base font-semibold text-ink">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-muted">{{ $description }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
