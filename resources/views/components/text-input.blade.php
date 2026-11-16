@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-line focus:border-primary-500 focus:ring-primary-500 rounded-lg shadow-sm']) }}>
