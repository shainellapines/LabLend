<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'LabLend') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="flex min-h-screen bg-canvas">
            {{-- Left: brand panel (desktop only) --}}
            <aside class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-brand p-12 text-white lg:flex">
                <div class="pointer-events-none absolute -bottom-32 -right-32 h-96 w-96 rounded-full border border-white/10"></div>
                <div class="pointer-events-none absolute -bottom-16 -right-16 h-64 w-64 rounded-full border border-white/10"></div>

                <a href="/" class="relative flex items-center gap-3">
                    <x-application-logo class="h-10 w-10" />
                    <span class="text-xl font-semibold">LabLend</span>
                </a>

                <div class="relative max-w-md">
                    <h1 class="text-4xl font-semibold leading-tight">Reserve lab equipment and rooms, without double bookings.</h1>
                    <p class="mt-4 text-white/70">Always know who has which item, in what condition, and when it is due back.</p>

                    <ul class="mt-8 space-y-3 text-sm text-white/80">
                        @foreach (['Live conflict checking for every reservation', 'QR asset tags on every item', 'Automatic overdue reminders'] as $feature)
                            <li class="flex items-center gap-3">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                </span>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative text-xs text-white/50">IT Department &middot; Equipment &amp; Room Reservation System</p>
            </aside>

            {{-- Right: the form --}}
            <main class="flex w-full flex-col items-center justify-center px-4 py-10 sm:px-6 lg:w-1/2">
                <a href="/" class="mb-8 flex items-center gap-3 lg:hidden">
                    <x-application-logo class="h-10 w-10" />
                    <span class="text-xl font-semibold text-ink">LabLend</span>
                </a>

                <div class="w-full max-w-md rounded-xl border border-line bg-white p-6 shadow-card sm:p-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
