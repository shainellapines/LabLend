{{-- A styled <select>. Put the <option> tags in the slot. --}}
@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'border-line focus:border-primary-500 focus:ring-primary-500 rounded-lg shadow-sm text-sm']) }}>
    {{ $slot }}
</select>
