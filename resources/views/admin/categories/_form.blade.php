{{-- Shared by create and edit. old() keeps what the user typed when validation fails. --}}
<div class="space-y-6">
    <div class="grid gap-6 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <x-input-label for="name" value="Name *" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          :value="old('name', $category->name)" required autofocus maxlength="255" placeholder="e.g. Projectors" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="code" value="Code *" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full font-mono uppercase"
                          :value="old('code', $category->code)" required maxlength="10" placeholder="PRJ" />
            <p class="mt-1 text-xs text-muted">Letters/numbers, max 10. Used for asset tags.</p>
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="description" value="Description" />
        <x-textarea id="description" name="description" class="mt-1 block w-full" maxlength="1000"
                    placeholder="What kind of items belong here?">{{ old('description', $category->description) }}</x-textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>
</div>
