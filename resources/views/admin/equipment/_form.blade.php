{{--
    Shared by create and edit.
    Alpine: picking a category fills in the suggested asset tag ($suggestedTags), until the admin types their own.
--}}
<div x-data="{
        tags: @js($suggestedTags),
        tag: @js(old('asset_tag', $equipment->asset_tag)),
        touched: @js(filled(old('asset_tag', $equipment->asset_tag))),
     }"
     class="space-y-8">

    {{-- ===== Identification ===== --}}
    <section>
        <h3 class="text-sm font-semibold text-ink">Identification</h3>
        <div class="mt-4 grid gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="category_id" value="Category *" />
                <x-select id="category_id" name="category_id" class="mt-1 block w-full" required
                          x-on:change="if (! touched) tag = tags[$event.target.value] ?? ''">
                    <option value="">Choose a category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $equipment->category_id) == $category->id)>
                            {{ $category->name }} ({{ $category->code }})
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="asset_tag" value="Asset tag *" />
                <x-text-input id="asset_tag" name="asset_tag" type="text" class="mt-1 block w-full font-mono uppercase"
                              x-model="tag" x-on:input="touched = true" required maxlength="30" placeholder="PRJ-0006" />
                <p class="mt-1 text-xs text-muted">Suggested from the category. You can change it.</p>
                <x-input-error :messages="$errors->get('asset_tag')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="name" value="Name *" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                              :value="old('name', $equipment->name)" required maxlength="255" placeholder="e.g. Epson Projector" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="brand" value="Brand" />
                <x-text-input id="brand" name="brand" type="text" class="mt-1 block w-full" :value="old('brand', $equipment->brand)" maxlength="255" />
                <x-input-error :messages="$errors->get('brand')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="model" value="Model" />
                <x-text-input id="model" name="model" type="text" class="mt-1 block w-full" :value="old('model', $equipment->model)" maxlength="255" />
                <x-input-error :messages="$errors->get('model')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="serial_number" value="Serial number" />
                <x-text-input id="serial_number" name="serial_number" type="text" class="mt-1 block w-full font-mono" :value="old('serial_number', $equipment->serial_number)" maxlength="255" />
                <x-input-error :messages="$errors->get('serial_number')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="location" value="Storage location" />
                <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" :value="old('location', $equipment->location)" maxlength="255" placeholder="e.g. Lab 2 cabinet" />
                <x-input-error :messages="$errors->get('location')" class="mt-2" />
            </div>
        </div>
    </section>

    {{-- ===== Condition & status ===== --}}
    <section class="border-t border-line pt-8">
        <h3 class="text-sm font-semibold text-ink">Condition &amp; status</h3>
        <div class="mt-4 grid gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="condition" value="Condition *" />
                <x-select id="condition" name="condition" class="mt-1 block w-full" required>
                    @foreach ($conditions as $condition)
                        <option value="{{ $condition->value }}" @selected(old('condition', $equipment->condition?->value) === $condition->value)>{{ $condition->label() }}</option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('condition')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="status" value="Status *" />
                <x-select id="status" name="status" class="mt-1 block w-full" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $equipment->status?->value) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </x-select>
                <p class="mt-1 text-xs text-muted">Only "Available" items can be reserved.</p>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="description" value="Description / notes" />
                <x-textarea id="description" name="description" class="mt-1 block w-full" rows="3" maxlength="2000">{{ old('description', $equipment->description) }}</x-textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>
        </div>
    </section>

    {{-- ===== Photo ===== --}}
    <section class="border-t border-line pt-8">
        <h3 class="text-sm font-semibold text-ink">Photo</h3>
        <div class="mt-4">
            <x-photo-upload name="photo" :current="$equipment->photoUrl()" />
            <x-input-error :messages="$errors->get('photo')" class="mt-2" />
        </div>
    </section>
</div>
