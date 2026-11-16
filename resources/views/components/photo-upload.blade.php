{{--
    File input with an instant preview (Alpine). The preview is only in the browser; nothing is uploaded until the form is saved.
    Usage: <x-photo-upload name="photo" :current="$equipment->photoUrl()" />
--}}
@props(['name' => 'photo', 'current' => null])

<div x-data="{ preview: @js($current) }" class="flex items-center gap-4">
    <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-line bg-canvas">
        <template x-if="preview">
            <img :src="preview" alt="Photo preview" class="h-full w-full object-cover">
        </template>
        <template x-if="! preview">
            <svg class="h-8 w-8 text-muted/50" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
        </template>
    </div>

    <div class="min-w-0">
        <input type="file" name="{{ $name }}" id="{{ $name }}" accept="image/jpeg,image/png,image/webp"
               @change="const file = $event.target.files[0]; preview = file ? URL.createObjectURL(file) : @js($current)"
               class="block w-full text-sm text-muted file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100">
        <p class="mt-1 text-xs text-muted">JPG, PNG, or WEBP, up to 2 MB.{{ $current ? ' Choosing a new photo replaces the current one.' : '' }}</p>
    </div>
</div>
