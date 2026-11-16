{{-- Shared by create and edit. --}}
<div class="space-y-6">
    <div class="grid gap-6 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <x-input-label for="name" value="Name *" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          :value="old('name', $room->name)" required autofocus maxlength="255" placeholder="e.g. Computer Lab 2" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="code" value="Code *" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full font-mono uppercase"
                          :value="old('code', $room->code)" required maxlength="20" placeholder="LAB-2" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-3">
        <div>
            <x-input-label for="type" value="Type *" />
            <x-select id="type" name="type" class="mt-1 block w-full" required>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type', $room->type?->value) === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('type')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="capacity" value="Capacity" />
            <x-text-input id="capacity" name="capacity" type="number" min="1" max="1000" class="mt-1 block w-full"
                          :value="old('capacity', $room->capacity)" placeholder="e.g. 40" />
            <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="location" value="Location" />
            <x-text-input id="location" name="location" type="text" class="mt-1 block w-full"
                          :value="old('location', $room->location)" maxlength="255" placeholder="e.g. IT Building, 2nd floor" />
            <x-input-error :messages="$errors->get('location')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="description" value="Description" />
        <x-textarea id="description" name="description" class="mt-1 block w-full" rows="3" maxlength="2000"
                    placeholder="Equipment in the room, rules, etc.">{{ old('description', $room->description) }}</x-textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <label class="flex items-start gap-3 rounded-lg border border-line p-4">
        <input type="checkbox" name="is_active" value="1" class="mt-0.5 rounded border-line text-primary-600 focus:ring-primary-500"
               @checked(old('is_active', $room->is_active))>
        <span>
            <span class="block text-sm font-medium text-ink">Active</span>
            <span class="block text-xs text-muted">Inactive rooms stay on the list but cannot be reserved (e.g. during renovation).</span>
        </span>
    </label>
</div>
