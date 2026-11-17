<x-app-layout>
    <x-slot name="title">{{ $equipment->asset_tag }}</x-slot>

    @php
        $isAdmin = Auth::user()->can('update', $equipment);
        $current = $availability['reservation'];
        $dateFormat = 'M d, Y g:i A';
    @endphp

    {{-- ===== Header ===== --}}
    <div class="mb-6">
        <a href="{{ route('equipment.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-ink">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            Equipment
        </a>

        <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-semibold text-ink">{{ $equipment->name }}</h2>
                    <span class="rounded-md bg-primary-50 px-2 py-1 font-mono text-sm font-semibold text-primary-700">{{ $equipment->asset_tag }}</span>
                </div>
                <p class="mt-1 text-sm text-muted">{{ $equipment->category->name }}{{ $equipment->brand ? ' · ' . $equipment->brand : '' }}{{ $equipment->model ? ' ' . $equipment->model : '' }}</p>
            </div>

            <div class="flex shrink-0 gap-2">
                @if ($equipment->status === App\Enums\EquipmentStatus::Available && ! $equipment->trashed())
                    <x-link-button :href="route('reservations.create', ['equipment' => $equipment->id])">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Reserve this item
                    </x-link-button>
                @endif
                @unless ($equipment->trashed())
                    <x-link-button :href="route('damage-reports.create', ['equipment' => $equipment->id])" variant="secondary">Report damage</x-link-button>
                @endunless
                @if ($isAdmin && ! $equipment->trashed())
                    <x-link-button :href="route('admin.equipment.edit', $equipment)" variant="secondary">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                        Edit
                    </x-link-button>
                @endif
            </div>
        </div>
    </div>

    @if ($equipment->trashed())
        <div class="mb-6 rounded-lg border border-line bg-slate-100 px-4 py-3 text-sm text-ink">
            This item was deleted on {{ $equipment->deleted_at->format($dateFormat) }}. Its history is kept for records.
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ===== Left: photo + details ===== --}}
        <div class="space-y-6">
            <x-card :padding="false" class="overflow-hidden">
                <div class="flex aspect-[4/3] items-center justify-center bg-canvas">
                    @if ($equipment->photoUrl())
                        <img src="{{ $equipment->photoUrl() }}" alt="{{ $equipment->name }}" class="h-full w-full object-cover">
                    @else
                        <div class="text-center text-muted">
                            <svg class="mx-auto h-12 w-12 text-muted/40" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                            <p class="mt-2 text-xs">No photo</p>
                        </div>
                    @endif
                </div>
            </x-card>

            <x-card title="Details">
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        'Category' => $equipment->category->name,
                        'Brand' => $equipment->brand,
                        'Model' => $equipment->model,
                        'Serial no.' => $equipment->serial_number,
                        'Location' => $equipment->location,
                        'Added' => $equipment->created_at->format('M d, Y'),
                    ] as $label => $value)
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">{{ $label }}</dt>
                            <dd class="text-right font-medium text-ink {{ $label === 'Serial no.' ? 'font-mono' : '' }}">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted">Condition</dt>
                        <dd><x-status-badge :status="$equipment->condition" /></dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted">Status</dt>
                        <dd><x-status-badge :status="$equipment->status" /></dd>
                    </div>
                </dl>

                @if ($equipment->description)
                    <p class="mt-4 border-t border-line pt-4 text-sm text-muted">{{ $equipment->description }}</p>
                @endif
            </x-card>
        </div>

        {{-- ===== Right: availability, upcoming, history, damage ===== --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Current availability --}}
            @php
                $panel = [
                    'available' => 'border-green-200 bg-green-50',
                    'borrowed' => 'border-indigo-200 bg-indigo-50',
                    'reserved' => 'border-indigo-200 bg-indigo-50',
                    'overdue' => 'border-red-200 bg-red-50',
                    'maintenance' => 'border-amber-200 bg-amber-50',
                    'retired' => 'border-line bg-slate-50',
                    'deleted' => 'border-line bg-slate-50',
                ][$availability['state']];
            @endphp
            <div class="rounded-xl border p-5 {{ $panel }}">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted">Right now</p>
                        <p class="mt-1 text-lg font-semibold text-ink">
                            @switch($availability['state'])
                                @case('available') Available to reserve @break
                                @case('borrowed') Borrowed by {{ $current->user->name }} @break
                                @case('overdue') Overdue: still with {{ $current->user->name }} @break
                                @case('reserved') Reserved by {{ $current->user->name }} @break
                                @case('maintenance') Under maintenance @break
                                @case('retired') Retired: no longer lent out @break
                                @case('deleted') Deleted @break
                            @endswitch
                        </p>
                        @if ($current)
                            <p class="mt-1 text-sm text-muted">
                                {{ $availability['state'] === 'overdue' ? 'Was due' : 'Until' }} {{ $current->end_at->format($dateFormat) }}
                                · {{ $current->reference_no }}
                                @if ($isAdmin)
                                    · {{ $current->user->email }}{{ $current->user->phone ? ' · ' . $current->user->phone : '' }}
                                @endif
                            </p>
                        @endif
                    </div>
                    <x-status-badge :label="$availability['label']" :color="$availability['color']" class="self-start text-sm sm:self-center" />
                </div>
            </div>

            {{-- Upcoming reservations --}}
            <x-card title="Upcoming reservations" subtitle="Pending and approved bookings for this item" :padding="false">
                @if ($upcoming->isEmpty())
                    <x-empty-state title="No upcoming reservations" description="This item is free for now." />
                @else
                    <x-data-table>
                        <x-slot name="head">
                            <th>Schedule</th>
                            <th>Borrower</th>
                            <th class="hidden sm:table-cell">Reference</th>
                            <th>Status</th>
                        </x-slot>
                        @foreach ($upcoming as $reservation)
                            <tr>
                                <td class="whitespace-nowrap">
                                    <p class="font-medium text-ink">{{ $reservation->start_at->format('M d, Y') }}</p>
                                    <p class="text-xs text-muted">{{ $reservation->start_at->format('g:i A') }} – {{ $reservation->end_at->format($reservation->end_at->isSameDay($reservation->start_at) ? 'g:i A' : 'M d, g:i A') }}</p>
                                </td>
                                <td>
                                    <p class="text-ink">{{ $reservation->user->name }}</p>
                                    @if ($isAdmin)<p class="text-xs text-muted">{{ $reservation->user->email }}</p>@endif
                                </td>
                                <td class="hidden font-mono text-xs text-muted sm:table-cell">{{ $reservation->reference_no }}</td>
                                <td><x-status-badge :status="$reservation->status" /></td>
                            </tr>
                        @endforeach
                    </x-data-table>
                @endif
            </x-card>

            {{-- Borrowing history --}}
            <x-card title="Borrowing history" subtitle="Who had this item and in what condition" :padding="false">
                @if ($history->isEmpty())
                    <x-empty-state title="Never borrowed yet" description="Hand-overs and returns will be listed here." />
                @else
                    <x-data-table>
                        <x-slot name="head">
                            <th>Borrowed</th>
                            <th>Borrower</th>
                            <th class="hidden md:table-cell">Condition out → in</th>
                            <th>Status</th>
                        </x-slot>
                        @foreach ($history as $reservation)
                            <tr>
                                <td class="whitespace-nowrap">
                                    <p class="font-medium text-ink">{{ $reservation->start_at->format('M d, Y') }}</p>
                                    <p class="text-xs text-muted">{{ $reservation->reference_no }}</p>
                                </td>
                                <td>
                                    <p class="text-ink">{{ $reservation->user->name }}</p>
                                    @if ($isAdmin)<p class="text-xs text-muted">{{ $reservation->user->email }}</p>@endif
                                </td>
                                <td class="hidden md:table-cell">
                                    <span class="text-sm capitalize text-muted">{{ $reservation->pivot->condition_out ?? '—' }}</span>
                                    <span class="text-muted">→</span>
                                    <span @class(['text-sm capitalize', 'font-semibold text-red-600' => $reservation->pivot->condition_in === 'damaged', 'text-muted' => $reservation->pivot->condition_in !== 'damaged'])>
                                        {{ $reservation->pivot->condition_in ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($reservation->isOverdue())
                                        <x-status-badge label="Overdue" color="red-solid" />
                                    @else
                                        <x-status-badge :status="$reservation->status" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-data-table>

                    @if ($history->hasPages())
                        <div class="border-t border-line px-4 py-3">{{ $history->links() }}</div>
                    @endif
                @endif
            </x-card>

            {{-- Damage reports --}}
            <x-card title="Damage reports" :subtitle="$damageReports->count() . ' report(s)'" :padding="false">
                @if ($damageReports->isEmpty())
                    <x-empty-state title="No damage reported" description="Good news: this item has a clean record." />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($damageReports as $report)
                            <li class="px-6 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-status-badge :status="$report->severity" />
                                    <x-status-badge :status="$report->status" />
                                    <span class="text-xs text-muted">{{ $report->created_at->format($dateFormat) }} · reported by {{ $report->reporter->name }}</span>
                                </div>
                                <p class="mt-2 text-sm text-ink">{{ $report->description }}</p>
                                @if ($report->resolution_notes)
                                    <p class="mt-1 text-sm text-muted">Resolution: {{ $report->resolution_notes }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
