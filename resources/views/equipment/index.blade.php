<x-app-layout>
    <x-slot name="title">Equipment</x-slot>

    <x-page-header title="Equipment" description="All lab equipment. Only available items can be reserved.">
        <x-slot name="actions">
            @can('create', App\Models\Equipment::class)
                <x-link-button :href="route('admin.equipment.create')">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    New Item
                </x-link-button>
            @endcan
        </x-slot>
    </x-page-header>

    <x-card :padding="false">
        <x-filter-bar :action="route('equipment.index')" placeholder="Search name, asset tag, or serial no.">
            <x-select name="category" aria-label="Category" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </x-select>
            <x-select name="status" aria-label="Status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </x-select>
            <x-select name="condition" aria-label="Condition" onchange="this.form.submit()">
                <option value="">Any condition</option>
                @foreach ($conditions as $condition)
                    <option value="{{ $condition->value }}" @selected(request('condition') === $condition->value)>{{ $condition->label() }}</option>
                @endforeach
            </x-select>
        </x-filter-bar>

        @if ($equipment->isEmpty())
            @if (request()->query())
                <x-empty-state title="No matching items" description="Try another search or clear the filters." />
            @else
                <x-empty-state title="No equipment yet" description="Add the first item to start tracking it.">
                    @can('create', App\Models\Equipment::class)
                        <x-link-button :href="route('admin.equipment.create')">New Item</x-link-button>
                    @endcan
                </x-empty-state>
            @endif
        @else
            <x-data-table>
                <x-slot name="head">
                    <th>Item</th>
                    <th>Asset tag</th>
                    <th class="hidden lg:table-cell">Category</th>
                    <th class="hidden md:table-cell">Condition</th>
                    <th>Status</th>
                    <th class="hidden xl:table-cell">Location</th>
                    @can('create', App\Models\Equipment::class)
                        <th class="text-right">Actions</th>
                    @endcan
                </x-slot>

                @foreach ($equipment as $item)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-line bg-canvas">
                                    @if ($item->photoUrl())
                                        <img src="{{ $item->photoUrl() }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                    @else
                                        <svg class="h-5 w-5 text-muted/50" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('equipment.show', $item) }}" class="block truncate font-medium text-ink hover:text-primary-700">{{ $item->name }}</a>
                                    <p class="truncate text-xs text-muted">{{ collect([$item->brand, $item->model])->filter()->implode(' · ') ?: '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td><a href="{{ route('equipment.show', $item) }}" class="whitespace-nowrap rounded-md bg-primary-50 px-2 py-1 font-mono text-xs font-semibold text-primary-700">{{ $item->asset_tag }}</a></td>
                        <td class="hidden text-muted lg:table-cell">{{ $item->category->name }}</td>
                        <td class="hidden md:table-cell"><x-status-badge :status="$item->condition" /></td>
                        <td><x-status-badge :status="$item->status" /></td>
                        <td class="hidden text-muted xl:table-cell">{{ $item->location ?: '—' }}</td>
                        @can('create', App\Models\Equipment::class)
                            <td class="whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-4">
                                    <a href="{{ route('admin.equipment.edit', $item) }}" class="text-sm font-medium text-primary-600 hover:text-primary-800">Edit</a>
                                    <x-confirm-delete :action="route('admin.equipment.destroy', $item)"
                                                      title="Delete item"
                                                      message="Delete {{ $item->asset_tag }} &quot;{{ $item->name }}&quot;? Its reservation and damage history will be kept." />
                                </div>
                            </td>
                        @endcan
                    </tr>
                @endforeach
            </x-data-table>

            <div class="flex flex-col gap-3 border-t border-line px-4 py-3 text-sm text-muted sm:flex-row sm:items-center sm:justify-between">
                <span>Showing {{ $equipment->firstItem() }}–{{ $equipment->lastItem() }} of {{ $equipment->total() }} items</span>
                @if ($equipment->hasPages())
                    {{ $equipment->links() }}
                @endif
            </div>
        @endif
    </x-card>
</x-app-layout>
