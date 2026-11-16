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
           'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
           'bg-primary-600/20 text-white' => $active,
           'text-white/60 hover:bg-charcoal hover:text-white' => ! $active,
       ])>
        @if ($active)
            {{-- Azure bar on the left edge of the active link --}}
            <span class="absolute inset-y-1.5 left-0 w-1 rounded-r bg-primary-300"></span>
        @endif
        <svg @class(['h-5 w-5 shrink-0', 'text-primary-300' => $active]) fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
        </svg>
        <span class="flex-1">{{ $label }}</span>
        {{ $slot }}
    </a>
@else
    <span class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-white/25" title="Coming soon">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
        </svg>
        <span class="flex-1">{{ $label }}</span>
        <span class="rounded bg-charcoal px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white/40">Soon</span>
    </span>
@endif
