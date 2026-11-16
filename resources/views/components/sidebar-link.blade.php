@props(['route', 'label', 'icon'])

@php
    // Highlight the link for the route itself and its children (e.g. equipment.index and equipment.show).
    $active = request()->routeIs(\Illuminate\Support\Str::beforeLast($route, '.') . '*');
    // Modules that are not built yet have no route, so the link is shown greyed out instead of crashing.
    $exists = \Illuminate\Support\Facades\Route::has($route);
@endphp

@if ($exists)
    <a href="{{ route($route) }}"
       @class([
           'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
           'bg-primary-50 text-primary-700' => $active,
           'text-muted hover:bg-canvas hover:text-ink' => ! $active,
       ])>
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
        </svg>
        {{ $label }}
        {{ $slot }}
    </a>
@else
    <span class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-muted/50" title="Coming soon">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
        </svg>
        {{ $label }}
    </span>
@endif
