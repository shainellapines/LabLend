<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'LabLend') }} · Equipment & Room Reservations</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-canvas font-sans text-ink antialiased">
        {{-- ===== Hero (branding gradient) ===== --}}
        <div class="relative overflow-hidden bg-brand text-white">
            <div class="pointer-events-none absolute -right-40 -top-40 h-[32rem] w-[32rem] rounded-full border border-white/10"></div>
            <div class="pointer-events-none absolute -right-20 -top-20 h-80 w-80 rounded-full border border-white/10"></div>

            <header class="relative mx-auto flex max-w-6xl items-center justify-between px-4 py-5 sm:px-6">
                <a href="/" class="flex items-center gap-3">
                    <x-application-logo class="h-9 w-9 rounded-[10px] ring-1 ring-white/20" />
                    <span class="text-lg font-semibold">LabLend</span>
                </a>

                <nav class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-primary-800 hover:bg-primary-50">Go to Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-white/80 hover:text-white">Log in</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-primary-800 hover:bg-primary-50">Register</a>
                    @endauth
                </nav>
            </header>

            <section class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 pb-28 pt-12 sm:px-6 sm:pt-16 lg:grid-cols-12">
                {{-- Left: message + actions --}}
                <div class="lg:col-span-7">
                    <p class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-primary-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-primary-300"></span>
                        IT Department · Equipment & Room Reservation
                    </p>
                    <h1 class="mt-5 text-4xl font-semibold leading-[1.1] tracking-tight sm:text-5xl">
                        Reserve lab equipment and rooms online, without double bookings.
                    </h1>
                    <p class="mt-5 max-w-xl text-base text-white/70 sm:text-lg">
                        LabLend replaces the paper logbook: always know who has which item, in what condition, and when it is due back.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-lg bg-white px-5 py-3 text-sm font-semibold text-primary-800 shadow-sm hover:bg-primary-50">Open LabLend</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-lg bg-white px-5 py-3 text-sm font-semibold text-primary-800 shadow-sm hover:bg-primary-50">Log in to reserve</a>
                            <a href="{{ route('register') }}" class="rounded-lg border border-white/30 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10">Create an account</a>
                        @endauth
                    </div>
                </div>

                {{-- Right: illustrative preview of the app (static example, not live data) --}}
                <div class="hidden lg:col-span-5 lg:block" aria-hidden="true">
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-3 shadow-2xl backdrop-blur">
                        <div class="rounded-xl bg-white p-5 text-ink">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-muted">Reservation</p>
                                    <p class="font-mono text-sm font-semibold">RSV-2026-0042</p>
                                </div>
                                <x-status-badge label="Approved" color="blue" />
                            </div>

                            <div class="mt-4 rounded-lg bg-canvas p-3 text-sm">
                                <p class="text-xs text-muted">Schedule</p>
                                <p class="font-medium">Nov 20, 2026 · 1:00 PM – 3:00 PM</p>
                            </div>

                            <ul class="mt-4 space-y-2 text-sm">
                                @foreach ([['PRJ-0004', 'Epson Projector', 'Available', 'green'], ['HDM-0012', 'HDMI Cable 5m', 'Available', 'green'], ['LAB-2', 'Computer Lab 2', 'Reserved', 'indigo']] as [$tag, $name, $state, $color])
                                    <li class="flex items-center justify-between rounded-lg border border-line px-3 py-2">
                                        <span class="flex items-center gap-3">
                                            <span class="rounded bg-primary-50 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-primary-700">{{ $tag }}</span>
                                            {{ $name }}
                                        </span>
                                        <x-status-badge :label="$state" :color="$color" />
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-4 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                Conflicts are checked before you submit.
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        {{-- ===== Features (z-10 so the cards sit ON TOP of the hero they overlap) ===== --}}
        <main class="relative z-10 mx-auto -mt-14 max-w-6xl px-4 pb-16 sm:px-6">
            <div class="grid gap-4 md:grid-cols-3">
                @foreach ([
                    ['No double bookings', 'Every request is checked against existing reservations before it is saved.', 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008z'],
                    ['QR asset tracking', 'Scan any item\'s label to see its status, current borrower, and full history.', 'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5zM13.5 14.25h.75v.75h-.75v-.75zM18 14.25h.75v.75H18v-.75zM15.75 16.5h.75v.75h-.75v-.75zM13.5 18.75h.75v.75h-.75v-.75zM18 18.75h.75v.75H18v-.75z'],
                    ['Accountability', 'Condition is recorded at release and return, damage is reported, and overdue borrowers get reminders.', 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
                ] as [$heading, $text, $icon])
                    <div class="rounded-xl border border-line bg-white p-6 shadow-card">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                        </span>
                        <h2 class="mt-4 text-base font-semibold text-ink">{{ $heading }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $text }}</p>
                    </div>
                @endforeach
            </div>

            {{-- ===== How it works (mirrors the reservation status flow) ===== --}}
            <section class="mt-16">
                <h2 class="text-center text-2xl font-semibold tracking-tight">How it works</h2>
                <p class="mt-2 text-center text-sm text-muted">From request to return, every step is recorded.</p>

                <ol class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['Request', 'Pick a time slot and the items or room you need.'],
                        ['Approve', 'The lab custodian reviews and approves the request.'],
                        ['Release', 'Items are handed over and their condition is noted.'],
                        ['Return', 'Items come back, are checked, and become available again.'],
                    ] as $i => [$step, $text])
                        <li class="rounded-xl border border-line bg-white p-5 shadow-card">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-sm font-semibold text-white">{{ $i + 1 }}</span>
                            <h3 class="mt-3 font-semibold">{{ $step }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-muted">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>
        </main>

        <footer class="border-t border-line bg-white">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-6 text-xs text-muted sm:flex-row sm:px-6">
                <span class="flex items-center gap-2">
                    <x-application-logo class="h-5 w-5" />
                    LabLend · IT Department
                </span>
                <span>Supports UN SDG 4 (Quality Education) and SDG 12 (Responsible Consumption)</span>
            </div>
        </footer>
    </body>
</html>
