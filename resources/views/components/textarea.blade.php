@props(['disabled' => false])

<textarea @disabled($disabled) {{ $attributes->merge(['rows' => 4, 'class' => 'border-line focus:border-primary-500 focus:ring-primary-500 rounded-lg shadow-sm']) }}>{{ $slot }}</textarea>
