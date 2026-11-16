<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' · ' : '' }}{{ config('app.name', 'LabLend') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-canvas text-ink">
        {{-- sidebarOpen only matters on small screens; on large screens the sidebar is always visible. --}}
        <div x-data="{ sidebarOpen: false }" class="min-h-screen">
            @include('layouts.sidebar')

            <div class="lg:pl-64">
                @include('layouts.topbar')

                <main class="px-4 py-6 sm:px-6 lg:px-8">
                    <x-flash />

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
