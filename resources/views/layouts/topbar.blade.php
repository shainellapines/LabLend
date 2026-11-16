<header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-line bg-white px-4 sm:px-6 lg:px-8">
    <!-- Hamburger (mobile only) -->
    <button type="button" @click="sidebarOpen = true" class="rounded-lg p-2 text-muted hover:bg-canvas lg:hidden">
        <span class="sr-only">Open menu</span>
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>

    {{-- Page title: each page sets it with a "title" slot. (Blade comment on purpose: Blade would compile
         a component tag even inside an HTML comment.) --}}
    <h1 class="flex-1 truncate text-lg font-semibold text-ink">{{ $title ?? '' }}</h1>

    <!-- Notification bell (placeholder; unread count + dropdown come in step 3.1) -->
    <button type="button" class="rounded-lg p-2 text-muted hover:bg-canvas hover:text-ink" title="Notifications">
        <span class="sr-only">Notifications</span>
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
    </button>

    <!-- User menu -->
    <x-dropdown align="right" width="48">
        <x-slot name="trigger">
            <button class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-medium text-muted hover:bg-canvas hover:text-ink">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-primary-700">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </span>
                <span class="hidden sm:block">{{ Auth::user()->name }}</span>
                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </x-slot>

        <x-slot name="content">
            <div class="border-b border-line px-4 py-2">
                <div class="truncate text-sm font-medium text-ink">{{ Auth::user()->name }}</div>
                <div class="truncate text-xs text-muted">{{ Auth::user()->email }}</div>
            </div>

            <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                    Log Out
                </x-dropdown-link>
            </form>
        </x-slot>
    </x-dropdown>
</header>
