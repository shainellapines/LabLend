{{--
    Search box for list pages. Sends a GET request to the same page (?search=...), so the URL can be bookmarked.
    Extra filters (selects) can be placed in the slot.
    Usage: <x-filter-bar :action="route('admin.categories.index')" placeholder="Search name or code" />
--}}
@props(['action', 'placeholder' => 'Search...'])

<form method="GET" action="{{ $action }}" class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center">
    <div class="relative flex-1">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
        </svg>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ $placeholder }}"
               class="w-full rounded-lg border-line pl-9 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
    </div>

    {{ $slot }}

    <div class="flex gap-2">
        <button type="submit" class="rounded-lg bg-onyx px-4 py-2 text-sm font-semibold text-white hover:bg-charcoal">Search</button>
        @if (request()->query())
            <a href="{{ $action }}" class="rounded-lg px-3 py-2 text-sm font-medium text-muted hover:text-ink">Reset</a>
        @endif
    </div>
</form>
