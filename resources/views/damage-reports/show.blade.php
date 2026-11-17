<x-app-layout>
    <x-slot name="title">Damage Report</x-slot>

    @php($item = $report->equipment)

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('damage-reports.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-ink">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                Damage Reports
            </a>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-semibold text-ink">{{ $item->name }}</h2>
                <a href="{{ route('equipment.show', $item) }}" class="rounded-md bg-primary-50 px-2 py-1 font-mono text-sm font-semibold text-primary-700 hover:bg-primary-100">{{ $item->asset_tag }}</a>
                <x-status-badge :status="$report->severity" class="text-sm" />
                <x-status-badge :status="$report->status" class="text-sm" />
            </div>
            <p class="mt-1 text-sm text-muted">Reported {{ $report->created_at->format('M d, Y g:i A') }} by {{ $report->reporter->name }}</p>
        </div>

        @can('delete', $report)
            <x-confirm-delete :action="route('admin.damage-reports.destroy', $report)" title="Delete report"
                              message="Delete this damage report? The item's status will not change." />
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="What happened">
                <p class="whitespace-pre-line text-sm text-ink">{{ $report->description }}</p>
                @if ($report->photoUrl())
                    <a href="{{ $report->photoUrl() }}" target="_blank" class="mt-4 block">
                        <img src="{{ $report->photoUrl() }}" alt="Damage photo" class="max-h-80 rounded-lg border border-line object-contain">
                    </a>
                @endif
            </x-card>

            @if ($report->resolution_notes)
                <x-card title="Resolution" :subtitle="$report->resolved_at ? 'Resolved ' . $report->resolved_at->format('M d, Y g:i A') : null">
                    <p class="whitespace-pre-line text-sm text-ink">{{ $report->resolution_notes }}</p>
                </x-card>
            @endif

            {{-- FR-D2: admin moves the report forward --}}
            @can('update', $report)
                @if ($report->status !== App\Enums\DamageStatus::Resolved)
                    <x-card title="Update status" subtitle="Open → Under repair → Resolved">
                        <form method="POST" action="{{ route('admin.damage-reports.update', $report) }}" class="space-y-4"
                              x-data="{ status: @js(old('status', $report->status->value)) }">
                            @csrf @method('PATCH')

                            <div class="grid gap-3 sm:grid-cols-3">
                                @foreach ($statuses as $status)
                                    @continue(! $report->status->canMoveTo($status))
                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-line p-3 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                                        <input type="radio" name="status" value="{{ $status->value }}" x-model="status" class="border-line text-primary-600 focus:ring-primary-500">
                                        <x-status-badge :status="$status" />
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('status')" />

                            <div>
                                <x-input-label for="resolution_notes" value="Notes" />
                                <x-textarea id="resolution_notes" name="resolution_notes" rows="3" class="mt-1 block w-full" maxlength="2000"
                                            placeholder="e.g. Sent to the supplier for repair / Replaced the lens cover.">{{ old('resolution_notes', $report->resolution_notes) }}</x-textarea>
                                <x-input-error :messages="$errors->get('resolution_notes')" class="mt-2" />
                            </div>

                            {{-- FR-D3: when resolving, the admin may put the item back into circulation --}}
                            <label x-show="status === 'resolved'" class="flex items-start gap-3 rounded-lg border border-line p-3">
                                <input type="checkbox" name="make_available" value="1" @checked(old('make_available', true))
                                       class="mt-0.5 rounded border-line text-primary-600 focus:ring-primary-500">
                                <span>
                                    <span class="block text-sm font-medium text-ink">Mark {{ $item->asset_tag }} as available again</span>
                                    <span class="block text-xs text-muted">Currently: {{ $item->status->label() }}. Leave unchecked if it should stay out of circulation.</span>
                                </span>
                            </label>

                            <div class="flex justify-end">
                                <x-primary-button>Save</x-primary-button>
                            </div>
                        </form>
                    </x-card>
                @endif
            @endcan
        </div>

        <div class="space-y-6">
            <x-card title="Item">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-muted">Category</dt><dd class="font-medium text-ink">{{ $item->category->name }}</dd></div>
                    <div class="flex items-center justify-between gap-4"><dt class="text-muted">Status now</dt><dd><x-status-badge :status="$item->status" /></dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Location</dt><dd class="text-right font-medium text-ink">{{ $item->location ?: '—' }}</dd></div>
                </dl>
            </x-card>

            <x-card title="Reservation">
                @if ($report->reservation)
                    <a href="{{ route('reservations.show', $report->reservation) }}" class="font-mono text-sm font-semibold text-primary-700 hover:text-primary-800">{{ $report->reservation->reference_no }}</a>
                    <p class="text-sm text-muted">{{ $report->reservation->user->name }} · {{ $report->reservation->start_at->format('M d, Y') }}</p>
                @else
                    <p class="text-sm text-muted">Not linked to a reservation.</p>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
