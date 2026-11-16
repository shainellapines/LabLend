{{-- LabLend mark: a box (equipment) on a branding-gradient tile. The size comes from the class passed in, e.g. class="h-9 w-9". --}}
@php($gradientId = 'lablend-logo-' . uniqid())

<svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <defs>
        <linearGradient id="{{ $gradientId }}" x1="0" y1="40" x2="40" y2="0" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#000000" />
            <stop offset="0.45" stop-color="#004A6B" />
            <stop offset="0.75" stop-color="#00638E" />
            <stop offset="1" stop-color="#8CB9CC" />
        </linearGradient>
    </defs>
    <rect width="40" height="40" rx="10" fill="url(#{{ $gradientId }})" />
    <path d="M20 9.5l9 5v10.5l-9 5-9-5V14.5l9-5z" stroke="#FFFFFF" stroke-width="2" stroke-linejoin="round" />
    <path d="M11 14.5l9 5 9-5M20 19.5V30" stroke="#FFFFFF" stroke-width="2" stroke-linejoin="round" />
</svg>
