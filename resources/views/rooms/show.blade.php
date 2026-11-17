<x-app-layout>
    <x-slot name="title">{{ $room->name }}</x-slot>

    @php
        $isAdmin = Auth::user()->can('update', $room);
    @endphp

    {{-- ===== Header ===== --}}
    <div class="mb-6">
        <a href="{{ route('rooms.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-ink">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            Rooms
        </a>

        <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-semibold text-ink">{{ $room->name }}</h2>
                    <span class="rounded-md bg-primary-50 px-2 py-1 font-mono text-sm font-semibold text-primary-700">{{ $room->code }}</span>
                    <x-status-badge :status="$room->type" />
                </div>
                <p class="mt-1 text-sm text-muted">{{ $room->location ?: 'No location set' }}</p>
            </div>

            <div class="flex shrink-0 items-center gap-3">
                @if ($isAdmin)
                    <x-confirm-delete :action="route('admin.rooms.destroy', $room)" title="Delete room"
                                      message="Delete &quot;{{ $room->name }}&quot;? Past reservations are kept but will no longer show this room." />
                    <x-link-button :href="route('admin.rooms.edit', $room)" variant="secondary">Edit</x-link-button>
                @endif
                @if ($room->is_active)
                    <x-link-button :href="route('reservations.create', ['room' => $room->id])">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Reserve this room
                    </x-link-button>
                @endif
            </div>
        </div>
    </div>

    @unless ($room->is_active)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            This room is <strong>inactive</strong> and cannot be reserved.
        </div>
    @endunless

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ===== Left: right-now panel + details ===== --}}
        <div class="space-y-6">
            <div @class([
                'rounded-xl border p-5',
                'border-indigo-200 bg-indigo-50' => $current,
                'border-green-200 bg-green-50' => ! $current && $room->is_active,
                'border-line bg-slate-50' => ! $current && ! $room->is_active,
            ])>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Right now</p>
                @if ($current)
                    <p class="mt-1 text-lg font-semibold text-ink">In use by {{ $current->user->name }}</p>
                    <p class="mt-1 text-sm text-muted">Until {{ $current->end_at->format('g:i A') }} · {{ $current->purpose }}</p>
                @elseif ($room->is_active)
                    <p class="mt-1 text-lg font-semibold text-ink">Free</p>
                    <p class="mt-1 text-sm text-muted">No reservation at this moment.</p>
                @else
                    <p class="mt-1 text-lg font-semibold text-ink">Not available</p>
                @endif
            </div>

            <x-card title="Details">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-muted">Type</dt><dd class="font-medium text-ink">{{ $room->type->label() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Capacity</dt><dd class="font-medium text-ink">{{ $room->capacity ? $room->capacity . ' people' : '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Location</dt><dd class="text-right font-medium text-ink">{{ $room->location ?: '—' }}</dd></div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted">Bookable</dt>
                        <dd><x-status-badge :label="$room->is_active ? 'Active' : 'Inactive'" :color="$room->is_active ? 'green' : 'gray'" /></dd>
                    </div>
                </dl>
                @if ($room->description)
                    <p class="mt-4 border-t border-line pt-4 text-sm text-muted">{{ $room->description }}</p>
                @endif
            </x-card>
        </div>

        {{-- ===== Right: 7-day agenda (FR-RM2) ===== --}}
        <x-card class="lg:col-span-2" title="Next 7 days" subtitle="Pending, approved, and in-use reservations" :padding="false">
            <ol class="divide-y divide-line">
                @for ($i = 0; $i < 7; $i++)
                    @php
                        $day = today()->addDays($i);
                        $bookings = $days->get($day->toDateString(), collect());
                    @endphp
                    <li class="flex gap-4 px-6 py-4">
                        <div @class(['w-14 shrink-0 text-center', 'text-primary-700' => $i === 0])>
                            <p class="text-xs font-semibold uppercase text-muted">{{ $i === 0 ? 'Today' : $day->format('D') }}</p>
                            <p class="text-2xl font-semibold leading-tight text-ink">{{ $day->format('j') }}</p>
                            <p class="text-xs text-muted">{{ $day->format('M') }}</p>
                        </div>

                        <div class="min-w-0 flex-1 space-y-2">
                            @forelse ($bookings as $reservation)
                                <div class="flex flex-col gap-2 rounded-lg border border-line bg-canvas/60 px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-ink">
                                            {{ $reservation->start_at->format('g:i A') }} – {{ $reservation->end_at->format($reservation->end_at->isSameDay($reservation->start_at) ? 'g:i A' : 'M d, g:i A') }}
                                        </p>
                                        <p class="truncate text-xs text-muted">
                                            {{ $reservation->purpose }} · {{ $reservation->user->name }}
                                            @if ($isAdmin) · {{ $reservation->user->email }} @endif
                                        </p>
                                    </div>
                                    <x-status-badge :status="$reservation->status" class="self-start sm:self-center" />
                                </div>
                            @empty
                                <p class="py-2 text-sm text-muted">Free all day</p>
                            @endforelse
                        </div>
                    </li>
                @endfor
            </ol>
        </x-card>
    </div>
</x-app-layout>
