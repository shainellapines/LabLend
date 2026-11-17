{{--
    PDF + CSV download buttons for one report (FR-H3), with a short loading state (section 10.4).
    Usage: <x-export-buttons type="usage" :from="$from" :to="$to" />
--}}
@props(['type', 'from', 'to'])

<div class="flex gap-2">
    @foreach (['pdf' => 'PDF', 'csv' => 'CSV'] as $format => $label)
        <a href="{{ route('admin.reports.export', ['type' => $type, 'format' => $format, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
           x-data="{ loading: false }" @click="loading = true; setTimeout(() => loading = false, 3000)"
           :class="loading && 'pointer-events-none opacity-60'"
           class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-white px-3 py-1.5 text-xs font-semibold text-ink hover:border-primary-200 hover:text-primary-700">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
            <span x-text="loading ? 'Preparing…' : @js($label)">{{ $label }}</span>
        </a>
    @endforeach
</div>
