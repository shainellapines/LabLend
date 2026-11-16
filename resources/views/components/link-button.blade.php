{{-- A link that looks like a button, for "+ New ..." and "Cancel". variant: primary | secondary --}}
@props(['href', 'variant' => 'primary'])

@php
    $classes = [
        'primary' => 'bg-primary-600 text-white hover:bg-primary-500 focus:ring-primary-500',
        'secondary' => 'border border-line bg-white text-ink hover:bg-canvas focus:ring-primary-500',
    ][$variant];
@endphp

<a href="{{ $href }}"
   {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 $classes"]) }}>
    {{ $slot }}
</a>
