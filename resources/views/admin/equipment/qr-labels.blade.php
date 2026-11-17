<x-app-layout>
    <x-slot name="title">Print QR Labels</x-slot>

    <x-page-header title="Print QR Labels" description="Print a sheet of labels (3 per row on A4). Stick one on each item; scanning it opens the item's page." />

    <div class="mb-6 flex gap-3 rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-800">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
        <p>The QR codes contain the address you are using right now (<strong>{{ url('/') }}</strong>). For phones to open them, open LabLend through your computer's network address (e.g. <span class="font-mono">http://192.168.1.10:8000</span>) before printing.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Option 1: a whole category --}}
        <x-card title="A whole category">
            <form method="GET" action="{{ route('admin.equipment.qr-labels.pdf') }}" target="_blank" class="space-y-4"
                  x-data="{ loading: false }" @submit="loading = true; setTimeout(() => loading = false, 4000)">
                <x-select name="category" class="block w-full" required>
                    <option value="">Choose a category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->equipment_count }} items)</option>
                    @endforeach
                </x-select>
                <x-primary-button class="w-full justify-center" x-bind:disabled="loading">
                    <span x-show="! loading">Create PDF</span>
                    <span x-show="loading" style="display: none;">Creating…</span>
                </x-primary-button>
            </form>
        </x-card>

        {{-- Option 2: hand-picked items --}}
        <x-card title="Selected items" class="lg:col-span-2">
            <form method="GET" action="{{ route('admin.equipment.qr-labels.pdf') }}" target="_blank"
                  x-data="{ count: 0, loading: false }"
                  @change="count = $el.querySelectorAll('input[name=\'ids[]\']:checked').length"
                  @submit="loading = true; setTimeout(() => loading = false, 4000)">
                <x-input-error :messages="$errors->get('category')" class="mb-3" />

                <div class="max-h-[28rem] space-y-5 overflow-y-auto rounded-lg border border-line p-4">
                    @foreach ($itemsByCategory as $categoryName => $items)
                        <div x-data>
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase tracking-wider text-muted">{{ $categoryName }}</p>
                                <button type="button" class="text-xs font-medium text-primary-600 hover:text-primary-800"
                                        @click="$el.closest('div[x-data]').querySelectorAll('input[type=checkbox]').forEach(c => c.checked = true); $el.closest('form').dispatchEvent(new Event('change'))">
                                    Select all
                                </button>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($items as $item)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-line px-3 py-2 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                                        <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="rounded border-line text-primary-600 focus:ring-primary-500">
                                        <span class="min-w-0">
                                            <span class="block font-mono text-xs font-semibold text-ink">{{ $item->asset_tag }}</span>
                                            <span class="block truncate text-xs text-muted">{{ $item->name }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center justify-between gap-4">
                    <p class="text-sm text-muted"><span x-text="count"></span> selected</p>
                    <x-primary-button x-bind:disabled="count === 0 || loading">
                        <span x-show="! loading">Create PDF</span>
                        <span x-show="loading" style="display: none;">Creating…</span>
                    </x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
