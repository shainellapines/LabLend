{{--
    Date + time picker (the browser's native datetime-local input).
    Usage: <x-datetime-input name="start_at" :value="old('start_at', $reservation->start_at)" />
    The value is shown as 2026-11-20T13:00, which Laravel's "date" rule and Carbon both understand.
--}}
@props(['name', 'value' => null])

@php
    $formatted = $value instanceof \DateTimeInterface ? $value->format('Y-m-d\TH:i') : $value;
@endphp

<input type="datetime-local" name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" value="{{ $formatted }}"
       {{ $attributes->merge(['class' => 'border-line focus:border-primary-500 focus:ring-primary-500 rounded-lg shadow-sm text-sm']) }}>
