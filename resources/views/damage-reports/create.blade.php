<x-app-layout>
    <x-slot name="title">Report Damage</x-slot>

    <x-page-header title="Report Damage" description="Describe what happened. Major damage puts the item under maintenance; a lost item is retired." />

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('damage-reports.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            @if ($reservation)
                {{-- Came here from a return (FR-R6): the reservation is fixed --}}
                <input type="hidden" name="reservation_id" value="{{ $reservation->id }}">
                <div class="rounded-lg border border-line bg-canvas px-4 py-3 text-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted">Linked reservation</p>
                    <p class="mt-1 font-medium text-ink">{{ $reservation->reference_no }} · {{ $reservation->user->name }}</p>
                    <p class="text-xs text-muted">{{ $reservation->start_at->format('M d, Y g:i A') }} – {{ $reservation->end_at->format('g:i A') }}</p>
                </div>
            @endif

            <div>
                <x-input-label for="equipment_id" value="Item *" />
                <x-select id="equipment_id" name="equipment_id" class="mt-1 block w-full" required>
                    <option value="">Choose the damaged item</option>
                    @foreach (($reservation?->equipment ?? $equipment) as $item)
                        <option value="{{ $item->id }}" @selected((int) old('equipment_id', $selectedItem) === $item->id)>
                            {{ $item->asset_tag }} · {{ $item->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('equipment_id')" class="mt-2" />
            </div>

            @unless ($reservation)
                <div>
                    <x-input-label for="reservation_id" value="Related reservation (optional)" />
                    <x-select id="reservation_id" name="reservation_id" class="mt-1 block w-full">
                        <option value="">None</option>
                        @foreach ($myReservations as $mine)
                            <option value="{{ $mine->id }}" @selected((int) old('reservation_id') === $mine->id)>
                                {{ $mine->reference_no }} · {{ $mine->start_at->format('M d, Y') }} · {{ $mine->purpose }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('reservation_id')" class="mt-2" />
                </div>
            @endunless

            {{-- Severity as three big choice cards --}}
            <fieldset>
                <legend class="text-sm font-medium text-ink">Severity *</legend>
                @php
                    $help = [
                        'minor' => 'Still usable (scratches, loose part). No status change.',
                        'major' => 'Not usable until repaired. Item goes under maintenance.',
                        'lost' => 'Missing or beyond repair. Item is retired.',
                    ];
                @endphp
                <div class="mt-2 grid gap-3 sm:grid-cols-3">
                    @foreach ($severities as $severity)
                        <label class="flex cursor-pointer flex-col gap-1 rounded-lg border border-line p-3 transition hover:border-primary-200 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                            <span class="flex items-center gap-2">
                                <input type="radio" name="severity" value="{{ $severity->value }}" @checked(old('severity', 'minor') === $severity->value)
                                       class="border-line text-primary-600 focus:ring-primary-500">
                                <x-status-badge :status="$severity" />
                            </span>
                            <span class="text-xs text-muted">{{ $help[$severity->value] }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('severity')" class="mt-2" />
            </fieldset>

            <div>
                <x-input-label for="description" value="What happened? *" />
                <x-textarea id="description" name="description" rows="4" class="mt-1 block w-full" maxlength="2000" required
                            placeholder="e.g. The lens cover is cracked and the image is blurry.">{{ old('description') }}</x-textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div>
                <x-input-label value="Photo (optional)" />
                <div class="mt-2"><x-photo-upload name="photo" /></div>
                <x-input-error :messages="$errors->get('photo')" class="mt-2" />
            </div>

            <div class="flex justify-end gap-3 border-t border-line pt-6">
                <x-link-button :href="route('damage-reports.index')" variant="secondary">Cancel</x-link-button>
                <x-primary-button>Submit Report</x-primary-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
