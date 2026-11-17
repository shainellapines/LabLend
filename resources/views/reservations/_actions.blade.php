{{--
    Action buttons for one reservation (FR-R4–R6).
    A button is shown only if the user is allowed (@can = policy) AND the status flow allows it
    (canTransitionTo = enum). The server checks both again, so hiding a button is only for convenience.
--}}
@use('App\Enums\EquipmentCondition')
@use('App\Enums\ReservationStatus', 'S')

@php
    $can =fn (S $to) => $reservation->status->canTransitionTo($to);
    $isManager = Auth::user()->can('manage', $reservation);
    $showCancel = Auth::user()->can('cancel', $reservation) && $can(S::Cancelled);
    $hasActions = $showCancel || ($isManager && ($can(S::Approved) || $can(S::Rejected) || $can(S::Released) || $can(S::Returned)));
@endphp

@if ($hasActions)
    <x-card title="Actions">
        <div class="flex flex-col gap-2">
            @if ($isManager && $can(S::Approved))
                <form method="POST" action="{{ route('admin.reservations.approve', $reservation) }}">
                    @csrf @method('PATCH')
                    <button class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Approve
                    </button>
                </form>
            @endif

            @if ($isManager && $can(S::Released))
                <button type="button" x-data @click="$dispatch('open-modal', 'release')"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                    Release (hand over)
                </button>
            @endif

            @if ($isManager && $can(S::Returned))
                <button type="button" x-data @click="$dispatch('open-modal', 'receive')"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    Receive (return)
                </button>
            @endif

            @if ($isManager && $can(S::Rejected))
                <button type="button" x-data @click="$dispatch('open-modal', 'reject')"
                        class="w-full rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">
                    Reject
                </button>
            @endif

            @if ($showCancel)
                <button type="button" x-data @click="$dispatch('open-modal', 'cancel')"
                        class="w-full rounded-lg border border-line bg-white px-4 py-2 text-sm font-semibold text-muted hover:bg-canvas hover:text-ink">
                    Cancel reservation
                </button>
            @endif
        </div>
    </x-card>

    {{-- ===== Modals (re-open automatically if their form had a validation error) ===== --}}

    @if ($isManager && $can(S::Rejected))
        <x-modal name="reject" :show="$errors->has('rejection_reason')" maxWidth="md" focusable>
            <form method="POST" action="{{ route('admin.reservations.reject', $reservation) }}" class="p-6">
                @csrf @method('PATCH')
                <h2 class="text-lg font-semibold text-ink">Reject {{ $reservation->reference_no }}</h2>
                <p class="mt-1 text-sm text-muted">The borrower will see this reason.</p>
                <x-input-label for="rejection_reason" value="Reason *" class="mt-4" />
                <x-textarea id="rejection_reason" name="rejection_reason" rows="3" class="mt-1 block w-full" maxlength="255" required
                            placeholder="e.g. The projector is needed for a department event.">{{ old('rejection_reason') }}</x-textarea>
                <x-input-error :messages="$errors->get('rejection_reason')" class="mt-2" />
                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Back</x-secondary-button>
                    <x-danger-button>Reject</x-danger-button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($isManager && ($can(S::Released) || $can(S::Returned)))
        @php
            $releasing = $can(S::Released);
            $conditions = $releasing ? EquipmentCondition::forEquipment() : EquipmentCondition::cases();
        @endphp
        <x-modal :name="$releasing ? 'release' : 'receive'" :show="$errors->has('conditions.*')" maxWidth="lg" focusable>
            <form method="POST" action="{{ route($releasing ? 'admin.reservations.release' : 'admin.reservations.receive', $reservation) }}" class="p-6">
                @csrf @method('PATCH')
                <h2 class="text-lg font-semibold text-ink">{{ $releasing ? 'Release' : 'Receive' }} {{ $reservation->reference_no }}</h2>
                <p class="mt-1 text-sm text-muted">
                    {{ $releasing ? 'Check each item before handing it to ' . $reservation->user->name . '.' : 'Check each item as it comes back. Choose "Damaged" to file a damage report.' }}
                </p>

                <div class="mt-4 space-y-3">
                    @forelse ($reservation->equipment as $item)
                        @php
                            // Default: at release, the item's current condition; at return, how it went out.
                            $default = old("conditions.{$item->id}", $releasing ? $item->condition->value : ($item->pivot->condition_out ?? 'good'));
                        @endphp
                        <div class="flex items-center justify-between gap-4 rounded-lg border border-line px-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-ink">{{ $item->name }}</p>
                                <p class="font-mono text-xs text-muted">{{ $item->asset_tag }}</p>
                            </div>
                            <x-select name="conditions[{{ $item->id }}]" aria-label="Condition of {{ $item->asset_tag }}" class="w-32 shrink-0">
                                @foreach ($conditions as $condition)
                                    <option value="{{ $condition->value }}" @selected($default === $condition->value)>{{ $condition->label() }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <x-input-error :messages="$errors->get('conditions.' . $item->id)" />
                    @empty
                        <p class="text-sm text-muted">Room only: no items to check.</p>
                    @endforelse
                </div>

                @unless ($releasing)
                    <x-input-label for="remarks" value="Remarks" class="mt-4" />
                    <x-textarea id="remarks" name="remarks" rows="2" class="mt-1 block w-full" maxlength="2000"
                                placeholder="e.g. Returned 30 minutes late; cable slightly frayed.">{{ old('remarks') }}</x-textarea>
                @endunless

                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Back</x-secondary-button>
                    <x-primary-button>{{ $releasing ? 'Confirm release' : 'Confirm return' }}</x-primary-button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($showCancel)
        <x-modal name="cancel" maxWidth="md" focusable>
            <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" class="p-6">
                @csrf @method('PATCH')
                <h2 class="text-lg font-semibold text-ink">Cancel {{ $reservation->reference_no }}?</h2>
                <p class="mt-2 text-sm text-muted">The items and room will be free for others again. This cannot be undone.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Keep it</x-secondary-button>
                    <x-danger-button>Yes, cancel</x-danger-button>
                </div>
            </form>
        </x-modal>
    @endif
@endif
