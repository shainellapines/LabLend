<x-app-layout>
    <x-slot name="title">Edit User</x-slot>

    @php($isSelf = $user->is(Auth::user()))

    <x-page-header title="Edit {{ $user->name }}" description="Fields marked * are required." />

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <x-input-label for="name" value="Name *" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required maxlength="255" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="email" value="Email *" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required maxlength="255" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="id_number" value="ID number *" />
                    <x-text-input id="id_number" name="id_number" type="text" class="mt-1 block w-full font-mono" :value="old('id_number', $user->id_number)" required maxlength="30" />
                    <x-input-error :messages="$errors->get('id_number')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="department" value="Department / Program" />
                    <x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="old('department', $user->department)" maxlength="255" />
                    <x-input-error :messages="$errors->get('department')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="phone" value="Phone" />
                    <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $user->phone)" maxlength="20" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>
            </div>

            <fieldset class="border-t border-line pt-6">
                <legend class="text-sm font-semibold text-ink">Role *</legend>
                <p class="mt-1 text-xs text-muted">Faculty can reserve for up to {{ config('lablend.max_hours.faculty') }} hours; students up to {{ config('lablend.max_hours.student') }} hours and {{ config('lablend.student_max_active') }} active reservations.</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                    @foreach ($roles as $role)
                        @php($locked = $isSelf && $role !== App\Enums\UserRole::Admin)
                        <label @class([
                            'flex items-center gap-2 rounded-lg border border-line p-3 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50',
                            'cursor-pointer' => ! $locked,
                            'cursor-not-allowed opacity-50' => $locked,
                        ])>
                            <input type="radio" name="role" value="{{ $role->value }}" @checked(old('role', $user->role->value) === $role->value) @disabled($locked)
                                   class="border-line text-primary-600 focus:ring-primary-500">
                            <x-status-badge :status="$role" />
                        </label>
                    @endforeach
                </div>
                @if ($isSelf)
                    <p class="mt-2 text-xs text-muted">You cannot remove your own admin role.</p>
                @endif
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </fieldset>

            <div class="flex justify-end gap-3 border-t border-line pt-6">
                <x-link-button :href="route('admin.users.show', $user)" variant="secondary">Cancel</x-link-button>
                <x-primary-button>Save Changes</x-primary-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
