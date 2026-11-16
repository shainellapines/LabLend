{{--
    Usage:
    <x-page-header title="Equipment" description="All items in the inventory">
        <x-slot name="actions"> <a href="...">+ New Item</a> </x-slot>
    </x-page-header>
--}}
@props(['title', 'description' => null])

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-2xl font-semibold text-ink">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 text-sm text-muted">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
