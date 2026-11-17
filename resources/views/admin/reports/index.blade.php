<x-app-layout>
    <x-slot name="title">Reports</x-slot>

    <x-page-header title="Reports" description="Usage, overdue items, and damage for any period. Export each report to PDF or CSV." />

    {{-- Date range (applies to usage and damage; overdue is always "right now") --}}
    <x-card class="mb-6">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div>
                <x-input-label for="from" value="From" />
                <input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="mt-1 rounded-lg border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div>
                <x-input-label for="to" value="To" />
                <input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="mt-1 rounded-lg border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div class="flex gap-2">
                <x-primary-button>Apply</x-primary-button>
                @foreach ([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $days => $label)
                    <a href="{{ route('admin.reports.index', ['from' => today()->subDays($days - 1)->toDateString(), 'to' => today()->toDateString()]) }}"
                       class="hidden rounded-lg px-3 py-2 text-sm font-medium text-muted hover:bg-canvas hover:text-ink md:inline-block">{{ $label }}</a>
                @endforeach
            </div>
        </form>
        <x-input-error :messages="$errors->get('to')" class="mt-2" />
    </x-card>

    {{-- Headline numbers --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Times items were borrowed" :value="$totalBorrows" tone="azure" :hint="$from->format('M d') . ' – ' . $to->format('M d, Y')"
            icon="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
        <x-stat-card label="Items never borrowed" :value="$neverUsed" tone="gray" hint="In this period"
            icon="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        <x-stat-card label="Overdue now" :value="$overdue->count()" :tone="$overdue->count() ? 'red' : 'green'" hint="Released and past due"
            icon="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        <x-stat-card label="Damage reports" :value="$damage['reports']->count()" tone="amber" hint="Filed in this period"
            icon="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ===== Usage report ===== --}}
        <x-card class="lg:col-span-2" title="Usage report" subtitle="Top 10 items by times borrowed (all items in the export)" :padding="false">
            <x-slot name="actions"><x-export-buttons type="usage" :from="$from" :to="$to" /></x-slot>
            @if ($totalBorrows === 0)
                <x-empty-state title="No borrowing in this period" />
            @else
                @php($max = max(1, $topItems->max('borrow_count')))
                <x-data-table>
                    <x-slot name="head"><th>Item</th><th class="hidden sm:table-cell">Category</th><th class="w-1/3">Times borrowed</th></x-slot>
                    @foreach ($topItems as $item)
                        <tr>
                            <td>
                                <a href="{{ route('equipment.show', $item) }}" class="font-mono text-xs font-semibold text-primary-700 hover:text-primary-800">{{ $item->asset_tag }}</a>
                                <p class="text-xs text-muted">{{ $item->name }}</p>
                            </td>
                            <td class="hidden text-muted sm:table-cell">{{ $item->category->name }}</td>
                            <td>
                                {{-- Inline bar: one hue, value labeled in text color --}}
                                <div class="flex items-center gap-2">
                                    <div class="h-2 flex-1 rounded-full bg-canvas">
                                        <div class="h-2 rounded-full bg-primary-600" style="width: {{ round($item->borrow_count / $max * 100) }}%"></div>
                                    </div>
                                    <span class="w-6 text-right text-sm font-semibold tabular-nums text-ink">{{ $item->borrow_count }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>

        <x-card title="By category" :subtitle="$from->format('M d') . ' – ' . $to->format('M d, Y')" :padding="false">
            <x-data-table>
                <x-slot name="head"><th>Category</th><th class="text-right">Items</th><th class="text-right">Borrowed</th></x-slot>
                @foreach ($byCategory as $row)
                    <tr>
                        <td class="font-medium text-ink">{{ $row['category'] }}</td>
                        <td class="text-right tabular-nums text-muted">{{ $row['items'] }}</td>
                        <td class="text-right font-semibold tabular-nums text-ink">{{ $row['borrow_count'] }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        </x-card>

        {{-- ===== Overdue report ===== --}}
        <x-card class="lg:col-span-2" title="Overdue report" subtitle="As of now" :padding="false">
            <x-slot name="actions"><x-export-buttons type="overdue" :from="$from" :to="$to" /></x-slot>
            @if ($overdue->isEmpty())
                <x-empty-state title="Nothing is overdue" description="Every borrowed item is within its return time." />
            @else
                <x-data-table>
                    <x-slot name="head"><th>Reference</th><th>Borrower</th><th class="hidden md:table-cell">Items · Room</th><th>Was due</th></x-slot>
                    @foreach ($overdue as $reservation)
                        <tr>
                            <td><a href="{{ route('reservations.show', $reservation) }}" class="font-mono text-sm font-semibold text-primary-700 hover:text-primary-800">{{ $reservation->reference_no }}</a></td>
                            <td>
                                <p class="font-medium text-ink">{{ $reservation->user->name }}</p>
                                <p class="text-xs text-muted">{{ $reservation->user->email }}</p>
                            </td>
                            <td class="hidden max-w-xs truncate text-muted md:table-cell">{{ $reservation->summary() }}</td>
                            <td class="whitespace-nowrap">
                                <p class="text-ink">{{ $reservation->end_at->format('M d, g:i A') }}</p>
                                <x-status-badge :label="$reservation->end_at->diffForHumans(['parts' => 1])" color="red-solid" />
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
            @endif
        </x-card>

        {{-- ===== Damage summary ===== --}}
        <x-card title="Damage summary" :subtitle="$from->format('M d') . ' – ' . $to->format('M d, Y')">
            <x-slot name="actions"><x-export-buttons type="damage" :from="$from" :to="$to" /></x-slot>
            <p class="text-xs font-semibold uppercase tracking-wider text-muted">By severity</p>
            <dl class="mt-2 grid grid-cols-3 gap-2 text-center">
                @foreach ($damage['bySeverity'] as $label => $count)
                    <div class="rounded-lg bg-canvas px-2 py-3">
                        <dd class="text-xl font-semibold tabular-nums text-ink">{{ $count }}</dd>
                        <dt class="text-xs text-muted">{{ $label }}</dt>
                    </div>
                @endforeach
            </dl>
            <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-muted">By status</p>
            <dl class="mt-2 grid grid-cols-3 gap-2 text-center">
                @foreach ($damage['byStatus'] as $label => $count)
                    <div class="rounded-lg bg-canvas px-2 py-3">
                        <dd class="text-xl font-semibold tabular-nums text-ink">{{ $count }}</dd>
                        <dt class="text-xs text-muted">{{ $label }}</dt>
                    </div>
                @endforeach
            </dl>
            <a href="{{ route('damage-reports.index') }}" class="mt-4 inline-block text-sm font-medium text-primary-600 hover:text-primary-800">All damage reports →</a>
        </x-card>
    </div>
</x-app-layout>
