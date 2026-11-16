{{-- Usage: <x-card title="Optional title"> ...content... </x-card>   (padding="false" for tables that go edge to edge) --}}
@props(['title' => null, 'padding' => true])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-line bg-white shadow-sm']) }}>
    @if ($title)
        <div class="border-b border-line px-6 py-4">
            <h3 class="text-base font-semibold text-ink">{{ $title }}</h3>
        </div>
    @endif

    <div @class(['p-6' => $padding])>
        {{ $slot }}
    </div>
</div>
