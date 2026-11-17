<x-app-layout>
    <x-slot name="title">{{ $reservation->reference_no }}</x-slot>

    @php
        $isAdmin = Auth::user()->isAdmin();
        $dateFormat = 'M d, Y g:i A';
        $status = $reservation->status;
    @endphp

    {{-- ===== Header ===== --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-medium text-muted">Reservation</p>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <h2 class="font-mono text-2xl font-semibold text-ink">{{ $reservation->reference_no }}</h2>
                @if ($reservation->isOverdue())
                    <x-status-badge label="Overdue" color="red-solid" class="text-sm" />
                @else
                    <x-status-badge :status="$status" class="text-sm" />
                @endif
            </div>
            <p class="mt-1 text-sm text-muted">{{ $reservation->purpose }}</p>
        </div>

        <div class="flex shrink-0 gap-2">
            {{-- FR-R10: printable slip with signature lines --}}
            <x-link-button :href="route('reservations.slip', $reservation)" variant="secondary" target="_blank">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" /></svg>
                Print slip
            </x-link-button>
            @can('update', $reservation)
                <x-link-button :href="route('reservations.edit', $reservation)" variant="secondary">Edit</x-link-button>
            @endcan
        </div>
    </div>

    {{-- Errors from a refused action (invalid status change, conflict found on approval, ...) --}}
    @if ($errors->any())
        <div class="mb-6 flex gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
            <div class="space-y-1">@foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>
        </div>
    @endif

    @if ($reservation->isOverdue())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <strong>Overdue:</strong> this was due back on {{ $reservation->end_at->format($dateFormat) }}.
        </div>
    @elseif ($status === App\Enums\ReservationStatus::Pending)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Waiting for the lab custodian to approve. You can still edit it while it is pending.
        </div>
    @elseif ($status === App\Enums\ReservationStatus::Rejected && $reservation->rejection_reason)
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <strong>Rejected:</strong> {{ $reservation->rejection_reason }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ===== Left: schedule + items/room ===== --}}
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Schedule">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs text-muted">Start</p>
                        <p class="font-medium text-ink">{{ $reservation->start_at->format($dateFormat) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted">End</p>
                        <p class="font-medium text-ink">{{ $reservation->end_at->format($dateFormat) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted">Duration</p>
                        <p class="font-medium text-ink">{{ $reservation->start_at->diffForHumans($reservation->end_at, ['syntax' => Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2]) }}</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Equipment" :subtitle="$reservation->equipment->count() . ' item(s)'" :padding="false">
                @if ($reservation->equipment->isEmpty())
                    <p class="px-6 py-4 text-sm text-muted">No equipment, room only.</p>
                @else
                    <x-data-table>
                        <x-slot name="head">
                            <th>Item</th>
                            <th>Asset tag</th>
                            <th class="hidden sm:table-cell">Condition out → in</th>
                        </x-slot>
                        @foreach ($reservation->equipment as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('equipment.show', $item) }}" class="font-medium text-ink hover:text-primary-700">{{ $item->name }}</a>
                                    <p class="text-xs text-muted">{{ $item->category->name }}</p>
                                </td>
                                <td><span class="rounded-md bg-primary-50 px-2 py-1 font-mono text-xs font-semibold text-primary-700">{{ $item->asset_tag }}</span></td>
                                <td class="hidden text-sm capitalize text-muted sm:table-cell">
                                    {{ $item->pivot->condition_out ?? '—' }} → {{ $item->pivot->condition_in ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </x-data-table>
                @endif
            </x-card>

            <x-card title="Room">
                @if ($reservation->room)
                    <a href="{{ route('rooms.show', $reservation->room) }}" class="flex items-center justify-between gap-4 rounded-lg border border-line p-3 hover:border-primary-200">
                        <span>
                            <span class="block font-medium text-ink">{{ $reservation->room->name }}</span>
                            <span class="block text-xs text-muted">{{ $reservation->room->code }} · {{ $reservation->room->location }}</span>
                        </span>
                        <x-status-badge :status="$reservation->room->type" />
                    </a>
                @else
                    <p class="text-sm text-muted">No room, equipment only.</p>
                @endif
            </x-card>
        </div>

        {{-- ===== Right: actions + borrower + timeline ===== --}}
        <div class="space-y-6">
            @include('reservations._actions')

            <x-card title="Borrower">
                <p class="font-medium text-ink">{{ $reservation->user->name }}</p>
                <p class="text-sm text-muted">{{ $reservation->user->role->label() }} · {{ $reservation->user->id_number }}</p>
                @if ($isAdmin || $reservation->user->is(Auth::user()))
                    <p class="mt-2 text-sm text-muted">{{ $reservation->user->email }}</p>
                    @if ($reservation->user->phone)<p class="text-sm text-muted">{{ $reservation->user->phone }}</p>@endif
                @endif
            </x-card>

            <x-card title="Timeline">
                @php
                    $steps = [
                        ['Requested', $reservation->created_at, null],
                        ['Approved', $reservation->approved_at, $reservation->approver?->name],
                        ['Released', $reservation->released_at, null],
                        ['Returned', $reservation->returned_at, null],
                    ];
                @endphp
                <ol class="relative space-y-5 border-l border-line pl-5">
                    @foreach ($steps as [$label, $when, $by])
                        <li class="relative">
                            <span @class([
                                'absolute -left-[1.6rem] top-1 h-3 w-3 rounded-full ring-4 ring-white',
                                'bg-primary-600' => $when,
                                'bg-line' => ! $when,
                            ])></span>
                            <p @class(['text-sm font-medium', 'text-ink' => $when, 'text-muted' => ! $when])>{{ $label }}</p>
                            <p class="text-xs text-muted">{{ $when ? $when->format($dateFormat) . ($by ? ' · ' . $by : '') : 'Not yet' }}</p>
                        </li>
                    @endforeach
                </ol>
                @if (in_array($status, [App\Enums\ReservationStatus::Rejected, App\Enums\ReservationStatus::Cancelled]))
                    <p class="mt-5 border-t border-line pt-4 text-sm text-muted">This reservation was <strong>{{ strtolower($status->label()) }}</strong>.</p>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
