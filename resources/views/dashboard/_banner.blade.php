{{-- Welcome banner (branding gradient), shared by the admin and borrower dashboards. Expects $subtitle. --}}
@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp

<section class="relative mb-6 overflow-hidden rounded-xl bg-brand px-6 py-7 text-white shadow-card sm:px-8">
    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full border border-white/10"></div>
    <div class="pointer-events-none absolute -right-4 -top-10 h-40 w-40 rounded-full border border-white/10"></div>

    <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-primary-200">{{ now()->format('l, F j, Y') }}</p>
            <h2 class="mt-1 text-2xl font-semibold sm:text-3xl">{{ $greeting }}, {{ Str::before(Auth::user()->name, ' ') }}</h2>
            <p class="mt-2 max-w-xl text-sm text-white/70">{{ $subtitle }}</p>
        </div>

        <div class="flex shrink-0 flex-wrap gap-2">
            <a href="{{ route('reservations.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-primary-800 shadow-sm hover:bg-primary-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                New Reservation
            </a>
            <a href="{{ route('equipment.index') }}" class="inline-flex items-center rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">
                Browse equipment
            </a>
        </div>
    </div>
</section>
