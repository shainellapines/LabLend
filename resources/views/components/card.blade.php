{{--
    Usage: <x-card title="Optional title" subtitle="Optional"> ...content... </x-card>
    :padding="false" for tables that go edge to edge. Optional <x-slot name="actions"> appears at the right of the title.
--}}
@props(['title' => null, 'subtitle' => null, 'padding' => true])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-line bg-white shadow-card']) }}>
    @if ($title)
        <div class="flex items-start justify-between gap-4 border-b border-line px-6 py-4">
            <div>
                <h3 class="text-sm font-semibold text-ink">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-muted">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="shrink-0">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div @class(['p-6' => $padding])>
        {{ $slot }}
    </div>
</div>
