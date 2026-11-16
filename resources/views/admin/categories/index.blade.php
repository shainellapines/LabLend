<x-app-layout>
    <x-slot name="title">Categories</x-slot>

    <x-page-header title="Categories" description="Group equipment by type. The code is used as the asset tag prefix (e.g. PRJ-0004).">
        <x-slot name="actions">
            @can('create', App\Models\Category::class)
                <x-link-button :href="route('admin.categories.create')">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    New Category
                </x-link-button>
            @endcan
        </x-slot>
    </x-page-header>

    <x-card :padding="false">
        <x-filter-bar :action="route('admin.categories.index')" placeholder="Search by name or code" />

        @if ($categories->isEmpty())
            @if (request('search'))
                <x-empty-state title="No matching categories" description="Nothing matches &quot;{{ request('search') }}&quot;. Try another search." />
            @else
                <x-empty-state title="No categories yet" description="Create the first category, e.g. Projectors (PRJ) or Laptops (LAP).">
                    <x-link-button :href="route('admin.categories.create')">New Category</x-link-button>
                </x-empty-state>
            @endif
        @else
            <x-data-table>
                <x-slot name="head">
                    <th class="w-28">Code</th>
                    <th>Name</th>
                    <th class="hidden md:table-cell">Description</th>
                    <th class="text-right">Actions</th>
                </x-slot>

                @foreach ($categories as $category)
                    <tr>
                        <td>
                            <span class="rounded-md bg-primary-50 px-2 py-1 font-mono text-xs font-semibold text-primary-700">{{ $category->code }}</span>
                        </td>
                        <td class="font-medium text-ink">{{ $category->name }}</td>
                        <td class="hidden max-w-md truncate text-muted md:table-cell">{{ $category->description ?: '—' }}</td>
                        <td class="whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-4">
                                @can('update', $category)
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="text-sm font-medium text-primary-600 hover:text-primary-800">Edit</a>
                                @endcan
                                @can('delete', $category)
                                    <x-confirm-delete :action="route('admin.categories.destroy', $category)"
                                                      title="Delete category"
                                                      message="Delete the category &quot;{{ $category->name }}&quot; ({{ $category->code }})?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>

            @if ($categories->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $categories->links() }}</div>
            @endif
        @endif
    </x-card>
</x-app-layout>
