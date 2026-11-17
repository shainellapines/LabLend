<x-app-layout>
    <x-slot name="title">Damage Reports</x-slot>

    @php($isAdmin = Auth::user()->isAdmin())

    <x-page-header title="Damage Reports"
                   :description="$isAdmin ? 'All reported damage and losses. Major damage puts the item under maintenance automatically.' : 'Damage you have reported.'">
        <x-slot name="actions">
            <x-link-button :href="route('damage-reports.create')">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Report Damage
            </x-link-button>
        </x-slot>
    </x-page-header>

    <x-card :padding="false">
        <x-filter-bar :action="route('damage-reports.index')" placeholder="Search item tag, name, or description">
            <x-select name="status" aria-label="Status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </x-select>
            <x-select name="severity" aria-label="Severity" onchange="this.form.submit()">
                <option value="">Any severity</option>
                @foreach ($severities as $severity)
                    <option value="{{ $severity->value }}" @selected(request('severity') === $severity->value)>{{ $severity->label() }}</option>
                @endforeach
            </x-select>
        </x-filter-bar>

        @if ($reports->isEmpty())
            @if (request()->query())
                <x-empty-state title="No matching reports" description="Try another search or clear the filters." />
            @else
                <x-empty-state title="No damage reported" description="If something you borrowed broke or went missing, report it here.">
                    <x-link-button :href="route('damage-reports.create')">Report Damage</x-link-button>
                </x-empty-state>
            @endif
        @else
            <x-data-table>
                <x-slot name="head">
                    <th>Item</th>
                    <th class="hidden md:table-cell">Description</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th class="hidden lg:table-cell">Reported</th>
                    <th class="text-right"><span class="sr-only">Open</span></th>
                </x-slot>
                @foreach ($reports as $report)
                    <tr>
                        <td>
                            <a href="{{ route('damage-reports.show', $report) }}" class="font-mono text-sm font-semibold text-primary-700 hover:text-primary-800">{{ $report->equipment->asset_tag }}</a>
                            <p class="max-w-[12rem] truncate text-xs text-muted">{{ $report->equipment->name }}</p>
                        </td>
                        <td class="hidden max-w-sm truncate text-muted md:table-cell">{{ $report->description }}</td>
                        <td><x-status-badge :status="$report->severity" /></td>
                        <td><x-status-badge :status="$report->status" /></td>
                        <td class="hidden whitespace-nowrap lg:table-cell">
                            <p class="text-ink">{{ $report->created_at->format('M d, Y') }}</p>
                            <p class="text-xs text-muted">by {{ $report->reporter->name }}</p>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('damage-reports.show', $report) }}" class="text-sm font-medium text-primary-600 hover:text-primary-800">View</a>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>

            @if ($reports->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $reports->links() }}</div>
            @endif
        @endif
    </x-card>
</x-app-layout>
