{{--
    A KPI tile for dashboards.
    Usage: <x-stat-card label="Pending requests" :value="$pending" tone="amber" hint="Waiting for approval"
                        icon="(heroicon path)" :href="route(...)" />
    tone: azure | amber | red | green | gray  (colors the icon tile)
--}}
@props(['label', 'value', 'icon', 'tone' => 'azure', 'hint' => null, 'href' => null])

@php
    $toneClasses = [
        'azure' => 'bg-primary-50 text-primary-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'red'   => 'bg-red-50 text-red-600',
        'green' => 'bg-green-50 text-green-600',
        'gray'  => 'bg-slate-100 text-slate-600',
    ][$tone] ?? 'bg-primary-50 text-primary-600';

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'group block rounded-xl border border-line bg-white p-5 shadow-card transition' . ($href ? ' hover:border-primary-200 hover:shadow-md' : '')]) }}>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-muted">{{ $label }}</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-ink">{{ $value }}</p>
        </div>
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $toneClasses }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
            </svg>
        </span>
    </div>
    @if ($hint)
        <p class="mt-3 text-xs text-muted">{{ $hint }}</p>
    @endif
</{{ $tag }}>
