<x-app-layout>
    <x-slot name="title">Rooms</x-slot>

    <x-page-header title="Rooms" description="Computer labs, the AVR, and classrooms that can be reserved.">
        <x-slot name="actions">
            @can('create', App\Models\Room::class)
                <x-link-button :href="route('admin.rooms.create')">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    New Room
                </x-link-button>
            @endcan
        </x-slot>
    </x-page-header>

    <x-card :padding="false" class="mb-6">
        <x-filter-bar :action="route('rooms.index')" placeholder="Search name, code, or location">
            <x-select name="type" aria-label="Type" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </x-select>
        </x-filter-bar>
    </x-card>

    @if ($rooms->isEmpty())
        <x-card>
            @if (request()->query())
                <x-empty-state title="No matching rooms" description="Try another search or clear the filter." />
            @else
                <x-empty-state title="No rooms yet" description="Add the computer labs, AVR, and classrooms that can be reserved.">
                    @can('create', App\Models\Room::class)
                        <x-link-button :href="route('admin.rooms.create')">New Room</x-link-button>
                    @endcan
                </x-empty-state>
            @endif
        </x-card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($rooms as $room)
                <a href="{{ route('rooms.show', $room) }}"
                   @class([
                       'group flex flex-col rounded-xl border bg-white p-5 shadow-card transition hover:border-primary-200 hover:shadow-md',
                       'border-line' => $room->is_active,
                       'border-dashed border-line opacity-75' => ! $room->is_active,
                   ])>
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21" /></svg>
                        </span>
                        <div class="flex flex-wrap justify-end gap-1.5">
                            <x-status-badge :status="$room->type" />
                            @unless ($room->is_active)
                                <x-status-badge label="Inactive" color="gray" />
                            @endunless
                        </div>
                    </div>

                    <h3 class="mt-4 font-semibold text-ink group-hover:text-primary-700">{{ $room->name }}</h3>
                    <p class="font-mono text-xs text-muted">{{ $room->code }}</p>

                    <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-line pt-4 text-sm">
                        <div>
                            <dt class="text-xs text-muted">Capacity</dt>
                            <dd class="font-medium text-ink">{{ $room->capacity ? $room->capacity . ' people' : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted">Next 7 days</dt>
                            <dd class="font-medium text-ink">{{ $room->upcoming_reservations_count }} {{ Str::plural('booking', $room->upcoming_reservations_count) }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-xs text-muted">Location</dt>
                            <dd class="truncate font-medium text-ink">{{ $room->location ?: '—' }}</dd>
                        </div>
                    </dl>
                </a>
            @endforeach
        </div>

        @if ($rooms->hasPages())
            <div class="mt-6">{{ $rooms->links() }}</div>
        @endif
    @endif
</x-app-layout>
