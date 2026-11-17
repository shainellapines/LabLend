{{--
    Shared by create and edit. Expects: $reservation, $itemsByCategory, $rooms, $selectedItems, $maxHours.
    Live "unavailable" hints for the chosen time are added in step 3.4; for now conflicts are checked on submit.
--}}
@php
    $selected = collect(old('equipment_ids', $selectedItems))->map(fn ($id) => (int) $id)->all();
@endphp

{{-- Errors that are not about one field (overdue block, student limit) --}}
@if ($errors->has('reservation'))
    <div class="mb-6 flex gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
        <div>@foreach ($errors->get('reservation') as $message)<p>{{ $message }}</p>@endforeach</div>
    </div>
@endif

{{-- reservationAvailability is defined in resources/js/app.js (live "unavailable" hints, section 10.4) --}}
<div class="space-y-8"
     x-data="reservationAvailability({ url: @js(route('reservations.availability')), ignore: @js($reservation->id) })">
    {{-- ===== 1. When ===== --}}
    <section>
        <div class="flex items-center gap-3">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-primary-600 text-xs font-semibold text-white">1</span>
            <h3 class="text-sm font-semibold text-ink">When and why</h3>
        </div>

        <div class="mt-4 grid gap-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="purpose" value="Purpose *" />
                <x-text-input id="purpose" name="purpose" type="text" class="mt-1 block w-full" maxlength="255" required
                              :value="old('purpose', $reservation->purpose)" placeholder="e.g. IT 312 class presentation" />
                <x-input-error :messages="$errors->get('purpose')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="start_at" value="Start *" />
                <x-datetime-input name="start_at" class="mt-1 block w-full" required x-on:change="schedule()"
                                  :value="old('start_at', $reservation->start_at)"
                                  :min="now()->addMinutes(config('lablend.min_lead_minutes'))->format('Y-m-d\TH:i')" />
                <x-input-error :messages="$errors->get('start_at')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="end_at" value="End *" />
                <x-datetime-input name="end_at" class="mt-1 block w-full" required x-on:change="schedule()" :value="old('end_at', $reservation->end_at)" />
                <x-input-error :messages="$errors->get('end_at')" class="mt-2" />
            </div>
        </div>

        <p class="mt-3 flex items-start gap-2 text-xs text-muted">
            <svg class="mt-px h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
            Start at least {{ config('lablend.min_lead_minutes') }} minutes from now and at most {{ config('lablend.max_days_ahead') }} days ahead.
            Your limit is {{ $maxHours }} hours per reservation.
        </p>

        <p class="mt-2 text-xs" aria-live="polite">
            <span x-cloak x-show="status === 'loading'" class="text-muted">Checking availability…</span>
            <span x-cloak x-show="status === 'done' && takenCount === 0" class="font-medium text-green-700">Everything is free at this time.</span>
            <span x-cloak x-show="status === 'done' && takenCount > 0" class="font-medium text-amber-700"
                  x-text="takenCount + ' item(s)/room(s) are already reserved at this time and are marked below.'"></span>
            <span x-cloak x-show="status === 'error'" class="text-muted">Could not check availability; it will be checked when you submit.</span>
        </p>
    </section>

    {{-- ===== 2. Items ===== --}}
    <section class="border-t border-line pt-8"
             x-data="{ q: '', count: {{ count($selected) }} }"
             @change="count = $el.querySelectorAll('input[name=\'equipment_ids[]\']:checked').length">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-primary-600 text-xs font-semibold text-white">2</span>
                <h3 class="text-sm font-semibold text-ink">Equipment</h3>
                <span x-cloak class="rounded-full bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-700" x-text="count + ' selected'"></span>
            </div>
            <input type="search" x-model="q" placeholder="Filter items..."
                   class="w-full rounded-lg border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:w-64">
        </div>

        <x-input-error :messages="array_merge($errors->get('equipment_ids'), $errors->get('equipment_ids.*'))" class="mt-3" />

        <div class="mt-4 max-h-96 space-y-5 overflow-y-auto rounded-lg border border-line p-4">
            @forelse ($itemsByCategory as $categoryName => $items)
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-muted">{{ $categoryName }}</p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($items as $item)
                            <label x-show="q === '' || $el.dataset.search.includes(q.toLowerCase())"
                                   data-search="{{ strtolower($item->asset_tag . ' ' . $item->name . ' ' . $categoryName) }}"
                                   :class="isTaken('equipment', {{ $item->id }}) ? '!border-red-300 !bg-red-50' : ''"
                                   :title="taken.equipment[{{ $item->id }}] ?? ''"
                                   class="flex cursor-pointer items-center gap-3 rounded-lg border border-line px-3 py-2 transition hover:border-primary-200 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                                {{-- A taken item cannot be newly ticked; an already-ticked one stays ticked so the user sees why it fails. --}}
                                <input type="checkbox" name="equipment_ids[]" value="{{ $item->id }}" @checked(in_array($item->id, $selected))
                                       :disabled="isTaken('equipment', {{ $item->id }}) && ! $el.checked"
                                       class="rounded border-line text-primary-600 focus:ring-primary-500 disabled:opacity-40">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-ink">{{ $item->name }}</span>
                                    <span class="block font-mono text-xs text-muted">{{ $item->asset_tag }}</span>
                                    <span x-show="isTaken('equipment', {{ $item->id }})" x-text="taken.equipment[{{ $item->id }}]"
                                          class="block text-xs font-medium text-red-600" style="display: none;"></span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-muted">No equipment is available for reservation right now.</p>
            @endforelse
        </div>
    </section>

    {{-- ===== 3. Room ===== --}}
    <section class="border-t border-line pt-8">
        <div class="flex items-center gap-3">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-primary-600 text-xs font-semibold text-white">3</span>
            <h3 class="text-sm font-semibold text-ink">Room <span class="font-normal text-muted">(optional)</span></h3>
        </div>

        <div class="mt-4 max-w-md">
            <x-select id="room_id" name="room_id" class="block w-full">
                <option value="">No room, equipment only</option>
                @foreach ($rooms as $room)
                    @php($roomLabel = $room->name . ' (' . $room->code . ')' . ($room->capacity ? ' · ' . $room->capacity . ' seats' : ''))
                    <option value="{{ $room->id }}" @selected((int) old('room_id', $reservation->room_id) === $room->id)
                            :disabled="isTaken('rooms', {{ $room->id }}) && ! $el.selected"
                            x-text="@js($roomLabel) + (isTaken('rooms', {{ $room->id }}) ? ' — already reserved' : '')">
                        {{ $roomLabel }}
                    </option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('room_id')" class="mt-2" />
            <p x-show="isTaken('rooms', $root.querySelector('[name=room_id]').value)" style="display: none;"
               class="mt-2 text-xs font-medium text-red-600">This room is already reserved at the chosen time.</p>
        </div>
        <p class="mt-3 text-xs text-muted">Pick at least one item or a room.</p>
    </section>
</div>
